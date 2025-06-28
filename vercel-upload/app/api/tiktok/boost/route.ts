import { NextRequest, NextResponse } from 'next/server';
import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import { eq, and, gte } from 'drizzle-orm';
import { z } from 'zod';
import { tiktokBoosts } from '@/shared/schema';

const sql = neon(process.env.DATABASE_URL!);
const db = drizzle(sql);

// Validation schema
const boostRequestSchema = z.object({
  url: z.string().url().refine(
    (url) => url.includes('tiktok.com') || url.includes('vt.tiktok.com'),
    { message: 'Must be a valid TikTok URL' }
  )
});

// Function to check if IP is VPN/Proxy
async function isVPNorProxy(ip: string): Promise<boolean> {
  try {
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
    
    const isKnownVPN = vpnPatterns.some(pattern => pattern.test(ip));
    if (isKnownVPN) return true;
    
    // Check datacenter patterns
    const datacenterPatterns = [
      /^(?:3[4-9]|4[0-9]|5[0-9])\./,  // AWS ranges (simplified)
      /^(?:13[4-9]|14[0-9])\./,       // Google Cloud (simplified)
      /^(?:20\.|40\.|52\.|104\.)/,    // Microsoft Azure (simplified)
    ];
    
    const isDatacenter = datacenterPatterns.some(pattern => pattern.test(ip));
    if (isDatacenter) return true;
    
    // Use free VPN detection API
    try {
      const response = await fetch(`http://ip-api.com/json/${ip}?fields=status,proxy,hosting`);
      const data = await response.json();
      
      if (data.status === 'success') {
        return data.proxy === true || data.hosting === true;
      }
    } catch (apiError) {
      console.error('IP API check failed:', apiError);
    }
    
    return false;
  } catch (error) {
    console.error('VPN check error:', error);
    return false; // Allow on error to avoid false positives
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { url } = boostRequestSchema.parse(body);
    const startTime = Date.now();
    
    // Get client IP
    const forwarded = request.headers.get('x-forwarded-for');
    const realIP = request.headers.get('x-real-ip');
    const ip = forwarded ? forwarded.split(',')[0].trim() : realIP || '127.0.0.1';
    
    // Check if IP is VPN/Proxy
    const isVPN = await isVPNorProxy(ip);
    if (isVPN) {
      return NextResponse.json({
        success: false,
        message: "VPN/Proxy tidak diizinkan. Harap gunakan koneksi internet asli.",
        error: "VPN_BLOCKED"
      }, { status: 403 });
    }
    
    // Check daily limit (5 per IP)
    const today = new Date();
    const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    const boostsToday = await db
      .select()
      .from(tiktokBoosts)
      .where(
        and(
          eq(tiktokBoosts.ipAddress, ip),
          gte(tiktokBoosts.createdAt, todayStart)
        )
      );
    
    if (boostsToday.length >= 5) {
      return NextResponse.json({
        success: false,
        message: `Batas harian tercapai (${boostsToday.length}/5 boost hari ini). Kembali lagi besok!`,
        data: {
          viewsAdded: 0,
          status: 'blocked',
          processingTime: '0s',
          boostsToday: boostsToday.length,
          boostsRemaining: 5 - boostsToday.length,
        }
      }, { status: 429 });
    }
    
    // Create boost record
    const boostData = {
      url,
      ipAddress: ip,
      status: 'pending' as const,
      viewsAdded: 0,
      processingTime: '0s'
    };
    
    const [newBoost] = await db
      .insert(tiktokBoosts)
      .values(boostData)
      .returning();
    
    try {
      // Simulate boost processing (demo mode for vercel deployment)
      const processingTime = `${((Date.now() - startTime) / 1000).toFixed(1)}s`;
      const viewsAdded = Math.floor(Math.random() * 5000) + 1000; // 1000-6000 views

      // Update boost record with success
      await db
        .update(tiktokBoosts)
        .set({
          status: 'completed',
          viewsAdded,
          processingTime,
        })
        .where(eq(tiktokBoosts.id, newBoost.id));

      const boostsRemaining = 5 - (boostsToday.length + 1);

      return NextResponse.json({
        success: true,
        message: "Boost berhasil! (Mode Demo)",
        data: {
          viewsAdded,
          status: 'completed',
          processingTime,
          videoTitle: "TikTok Video",
          orderId: `TK${newBoost.id.toString().padStart(6, '0')}`,
          boostsToday: boostsToday.length + 1,
          boostsRemaining,
        }
      });

    } catch (apiError: any) {
      // Update boost record with failure
      await db
        .update(tiktokBoosts)
        .set({
          status: 'failed',
          processingTime: `${((Date.now() - startTime) / 1000).toFixed(1)}s`,
        })
        .where(eq(tiktokBoosts.id, newBoost.id));

      console.error("Demo boost error:", apiError);

      return NextResponse.json({
        success: false,
        message: "Terjadi kesalahan dalam mode demo. Silakan coba lagi.",
        error: "Demo mode error"
      }, { status: 500 });
    }
    
  } catch (error) {
    console.error('Boost error:', error);
    
    if (error instanceof z.ZodError) {
      return NextResponse.json({
        success: false,
        message: 'URL TikTok tidak valid',
        error: error.errors[0].message
      }, { status: 400 });
    }
    
    return NextResponse.json({
      success: false,
      message: 'Terjadi kesalahan server',
      error: 'Internal server error'
    }, { status: 500 });
  }
}