import type { Express } from "express";
import { createServer, type Server } from "http";
import { storage } from "./storage";
import { tiktokBoostSchema, type ApiResponse, type StatsResponse } from "@shared/schema";
import { z } from "zod";

export async function registerRoutes(app: Express): Promise<Server> {
  
  // TikTok Boost API endpoint
  app.post("/api/tiktok/boost", async (req, res) => {
    try {
      const validatedData = tiktokBoostSchema.parse(req.body);
      const startTime = Date.now();

      // Check if user can boost this video
      const boostCheck = await storage.canBoost(validatedData.url);
      
      if (!boostCheck.canBoost) {
        const response: ApiResponse = {
          success: false,
          message: boostCheck.reason || "Tidak dapat melakukan boost saat ini",
          data: {
            viewsAdded: 0,
            status: 'blocked',
            processingTime: '0s',
            nextBoostAt: boostCheck.nextBoostAt?.toISOString(),
            boostsToday: boostCheck.boostsToday,
            boostsRemaining: 3 - boostCheck.boostsToday,
          }
        };
        return res.status(429).json(response);
      }

      // Create boost record
      const boost = await storage.createTiktokBoost({
        url: validatedData.url,
      });

      try {
        // Call N1Panel API
        const apiKey = "ed7a9a71995857a4c332d78697e9cd2b";
        
        // First, get available TikTok services
        const servicesResponse = await fetch("https://n1panel.com/api/v2", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            key: apiKey,
            action: 'services'
          }),
        });

        if (!servicesResponse.ok) {
          throw new Error("Gagal mengambil daftar layanan");
        }

        const services = await servicesResponse.json();
        
        // Find cheapest TikTok Views service
        const tiktokViewServices = services.filter((service: any) => 
          service.name.toLowerCase().includes('tiktok') && 
          service.name.toLowerCase().includes('view')
        ).sort((a: any, b: any) => parseFloat(a.rate) - parseFloat(b.rate));

        if (tiktokViewServices.length === 0) {
          throw new Error("Layanan TikTok Views tidak tersedia");
        }

        const cheapestService = tiktokViewServices[0];
        
        // Place order for 1000 views
        const orderResponse = await fetch("https://n1panel.com/api/v2", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams({
            key: apiKey,
            action: 'add',
            service: cheapestService.service,
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

        // Calculate next boost time (8 hours from now)
        const nextBoostTime = new Date(Date.now() + 8 * 60 * 60 * 1000);
        
        // Update boost record with success
        const updatedBoost = await storage.updateTiktokBoost(boost.id, {
          status: 'completed',
          viewsAdded: orderData.quantity || 1000,
          processingTime,
          nextBoostAt: nextBoostTime,
        });

        // Get updated boost stats
        const updatedBoostCheck = await storage.canBoost(validatedData.url);

        const response: ApiResponse = {
          success: true,
          message: "Views berhasil ditambahkan!",
          data: {
            viewsAdded: orderData.quantity || 1000,
            status: 'completed',
            processingTime,
            videoTitle: orderData.title || "TikTok Video",
            orderId: orderData.order || boost.id.toString(),
            nextBoostAt: nextBoostTime.toISOString(),
            boostsToday: updatedBoostCheck.boostsToday,
            boostsRemaining: 3 - updatedBoostCheck.boostsToday,
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

        const response: ApiResponse = {
          success: false,
          message: "Gagal menambahkan views",
          error: apiError.message || "Terjadi kesalahan pada API N1Panel"
        };

        res.status(400).json(response);
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
