import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { tiktokBoostSchema, type ApiResponse, type StatsResponse } from "../shared/schema";
import { z } from "zod";

// Function to check if IP is VPN/Proxy
async function isVPNorProxy(ip: string): Promise<boolean> {
  try {
    // Check against known VPN/proxy IP ranges and services
    const vpnChecks = [
      // Check if IP is from common VPN providers
      checkVPNProviders(ip),
      // Check if IP is datacenter/hosting provider
      checkDatacenter(ip),
      // Check IP geolocation consistency
      checkIPConsistency(ip)
    ];
    
    const results = await Promise.all(vpnChecks);
    return results.some(result => result);
  } catch (error) {
    console.error('VPN check error:', error);
    return false; // Allow on error to avoid false positives
  }
}

function checkVPNProviders(ip: string): boolean {
  // Skip localhost and development IPs
  if (ip === '127.0.0.1' || ip === '::1' || ip.startsWith('192.168.') || ip.startsWith('10.0.')) {
    return false;
  }
  
  // Known VPN provider IP patterns
  const vpnPatterns = [
    /^185\.159\./,     // NordVPN
    /^103\.214\./,     // ExpressVPN  
    /^91\.207\./,      // CyberGhost
    /^193\.29\./,      // Surfshark
    /^45\.83\./,       // ProtonVPN
    /^198\.8\./,       // Private Internet Access
  ];
  
  return vpnPatterns.some(pattern => pattern.test(ip));
}

function checkDatacenter(ip: string): boolean {
  // Check if IP belongs to known datacenter/hosting providers
  const datacenterPatterns = [
    /^(?:3[4-9]|4[0-9]|5[0-9])\./,  // AWS ranges (simplified)
    /^(?:13[4-9]|14[0-9])\./,       // Google Cloud (simplified)
    /^(?:20\.|40\.|52\.|104\.)/,    // Microsoft Azure (simplified)
  ];
  
  return datacenterPatterns.some(pattern => pattern.test(ip));
}

async function checkIPConsistency(ip: string): Promise<boolean> {
  try {
    // Use free VPN detection API
    const response = await fetch(`http://ip-api.com/json/${ip}?fields=status,proxy,hosting`);
    const data = await response.json();
    
    if (data.status === 'success') {
      return data.proxy === true || data.hosting === true;
    }
    return false;
  } catch (error) {
    console.error('IP API check failed:', error);
    return false;
  }
}

export async function registerRoutes(app: Express): Promise<Server> {
  
  // TikTok Boost API endpoint
  app.post("/api/tiktok/boost", async (req, res) => {
    try {
      const validatedData = tiktokBoostSchema.parse(req.body);
      const startTime = Date.now();
      const clientIP = req.ip || req.connection.remoteAddress || req.headers['x-forwarded-for'] || '127.0.0.1';
      const ipAddress = Array.isArray(clientIP) ? clientIP[0] : clientIP;

      // Check if IP is VPN/Proxy
      const isVPN = await isVPNorProxy(ipAddress);
      if (isVPN) {
        const response: ApiResponse = {
          success: false,
          message: "VPN/Proxy tidak diizinkan. Harap gunakan koneksi internet asli.",
          error: "VPN_BLOCKED"
        };
        return res.status(403).json(response);
      }

      // Check if IP can boost (5 boosts per day limit)
      const boostCheck = await storage.canBoost(ipAddress);
      
      if (!boostCheck.canBoost) {
        const response: ApiResponse = {
          success: false,
          message: boostCheck.reason || "Tidak dapat melakukan boost saat ini",
          data: {
            viewsAdded: 0,
            status: 'blocked',
            processingTime: '0s',
            boostsToday: boostCheck.boostsToday,
            boostsRemaining: 5 - boostCheck.boostsToday,
          }
        };
        return res.status(429).json(response);
      }

      // Create boost record
      const boost = await storage.createTiktokBoost({
        url: validatedData.url,
        ipAddress: ipAddress,
      });

      try {
        // Call N1Panel API
        const apiKey = process.env.N1PANEL_API_KEY;
        
        if (!apiKey) {
          throw new Error('N1Panel API key not configured');
        }
        
        // Use service ID 838 for faster TikTok views
        const orderResponse = await fetch("https://n1panel.com/api/v2", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            key: apiKey as string,
            action: 'add',
            service: '838',
            link: validatedData.url,
            quantity: '1000',
          }),
        });

        if (!orderResponse.ok) {
          const errorText = await orderResponse.text();
          throw new Error(`N1Panel API Error: ${orderResponse.status} - ${errorText}`);
        }

        const orderData = await orderResponse.json();
        const processingTime = `${((Date.now() - startTime) / 1000).toFixed(1)}s`;

        // Update boost record with success
        const updatedBoost = await storage.updateTiktokBoost(boost.id, {
          status: 'completed',
          viewsAdded: orderData.quantity || 1000,
          processingTime,
        });

        // Get updated boost stats
        const updatedBoostCheck = await storage.canBoost(ipAddress);

        const response: ApiResponse = {
          success: true,
          message: "Views berhasil ditambahkan!",
          data: {
            viewsAdded: orderData.quantity || 1000,
            status: 'completed',
            processingTime,
            videoTitle: orderData.title || "TikTok Video",
            orderId: orderData.order || boost.id.toString(),
            boostsToday: updatedBoostCheck.boostsToday,
            boostsRemaining: 5 - updatedBoostCheck.boostsToday,
          }
        };

        res.json(response);

      } catch (apiError: any) {
        // Update boost record with failure
        await storage.updateTiktokBoost(boost.id, {
          status: 'failed',
          processingTime: `${((Date.now() - startTime) / 1000).toFixed(1)}s`,
        });

        console.error("N1Panel API Error:", apiError);
        console.error("Error details:", {
          message: apiError.message,
          stack: apiError.stack,
          url: validatedData.url
        });

        const response: ApiResponse = {
          success: false,
          message: "Layanan boost sedang dalam perbaikan. Silakan coba lagi nanti.",
          error: apiError.message || "Terjadi kesalahan pada layanan eksternal"
        };

        res.status(503).json(response);
      }

    } catch (error: any) {
      console.error("TikTok boost error:", error);
      
      if (error instanceof z.ZodError) {
        const response: ApiResponse = {
          success: false,
          message: "Data tidak valid",
          error: error.errors[0]?.message || "Format URL tidak valid"
        };
        res.status(400).json(response);
      } else {
        const response: ApiResponse = {
          success: false,
          message: "Terjadi kesalahan server",
          error: error.message
        };
        res.status(500).json(response);
      }
    }
  });

  // Get today's stats
  app.get("/api/stats/today", async (req, res) => {
    try {
      const stats = await storage.getTodayStats();
      const response: StatsResponse = {
        videosToday: stats.videosToday,
        totalViews: stats.totalViews,
        successRate: stats.successRate,
        avgTime: stats.avgTime,
      };
      res.json(response);
    } catch (error) {
      console.error("Stats error:", error);
      res.status(500).json({ error: "Failed to get stats" });
    }
  });

  const httpServer = createServer(app);
  return httpServer;
}
