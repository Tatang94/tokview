import { NextRequest, NextResponse } from "next/server";
import { neon } from "@neondatabase/serverless";
import { drizzle } from "drizzle-orm/neon-http";
import { tiktokBoosts, users } from "../../../../shared/schema";
import { tiktokBoostSchema, type ApiResponse } from "../../../../shared/schema";
import { eq, and, gte, sql } from "drizzle-orm";

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  throw new Error("DATABASE_URL environment variable is required");
}

const sqlClient = neon(databaseUrl);
const db = drizzle(sqlClient);

// VPN/Proxy detection functions
function isVPNorProxy(ip: string): boolean {
  const vpnProviders = [
    'nordvpn', 'expressvpn', 'surfshark', 'cyberghost', 'privateinternetaccess',
    'tunnelbear', 'protonvpn', 'hotspotshield', 'windscribe', 'ipvanish',
    'purevpn', 'vyprvpn', 'mullvad', 'perfectprivacy', 'hide.me'
  ];
  
  const datacenterRanges = [
    '192.168.', '10.', '172.16.', '172.17.', '172.18.', '172.19.',
    '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.',
    '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.',
    '127.', '169.254.', '224.', '239.', '255.255.255.255'
  ];
  
  const proxyPorts = ['8080', '3128', '1080', '8888', '9050'];
  
  return vpnProviders.some(provider => ip.includes(provider)) ||
         datacenterRanges.some(range => ip.startsWith(range)) ||
         proxyPorts.some(port => ip.includes(`:${port}`));
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const validation = tiktokBoostSchema.safeParse(body);
    
    if (!validation.success) {
      return NextResponse.json({
        success: false,
        message: "Data tidak valid",
        error: validation.error.errors.map((e: any) => e.message).join(", ")
      } as ApiResponse, { status: 400 });
    }

    const { url } = validation.data;
    const clientIP = request.headers.get('x-forwarded-for') || 
                    request.headers.get('x-real-ip') || 
                    '127.0.0.1';

    // VPN/Proxy detection
    if (isVPNorProxy(clientIP)) {
      return NextResponse.json({
        success: false,
        message: "Silakan matikan VPN atau proxy Anda untuk menggunakan layanan ini. Kami perlu memverifikasi lokasi Anda untuk keamanan.",
        error: "VPN_BLOCKED"
      } as ApiResponse, { status: 403 });
    }

    // Check daily limit per IP
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const todayBoosts = await db.select()
      .from(tiktokBoosts)
      .where(and(
        eq(tiktokBoosts.ipAddress, clientIP),
        gte(tiktokBoosts.createdAt, today)
      ));

    if (todayBoosts.length >= 5) {
      return NextResponse.json({
        success: false,
        message: "Anda sudah mencapai batas 5 boost per hari. Silakan coba lagi besok.",
        data: {
          viewsAdded: 0,
          status: "blocked",
          processingTime: "0ms",
          boostsToday: todayBoosts.length,
          boostsRemaining: 0
        }
      } as ApiResponse, { status: 429 });
    }

    const startTime = Date.now();

    // Create boost record
    const boost = await db.insert(tiktokBoosts).values({
      url,
      ipAddress: clientIP,
      status: "pending",
      viewsAdded: 0,
    }).returning();

    // Simulate API call to N1Panel
    const apiKey = "1f0195dc7cea14eefe8c40af25c5b4a6";
    const service = "14";
    const quantity = "1000";

    try {
      const apiResponse = await fetch("https://n1panel.me/api/v2", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          key: apiKey,
          action: "add",
          service: service,
          link: url,
          quantity: quantity,
        }),
      });

      if (!apiResponse.ok) {
        throw new Error(`API responded with status: ${apiResponse.status}`);
      }

      const result = await apiResponse.json();
      const processingTime = `${Date.now() - startTime}ms`;

      if (result.order) {
        // Success
        await db.update(tiktokBoosts)
          .set({
            status: "completed",
            viewsAdded: parseInt(quantity),
            processingTime,
            orderId: result.order.toString(),
          })
          .where(eq(tiktokBoosts.id, boost[0].id));

        const response: ApiResponse = {
          success: true,
          message: `Berhasil! ${quantity} views sedang diproses untuk video Anda.`,
          data: {
            viewsAdded: parseInt(quantity),
            status: "completed",
            processingTime,
            orderId: result.order.toString(),
            boostsToday: todayBoosts.length + 1,
            boostsRemaining: 4 - todayBoosts.length
          }
        };

        return NextResponse.json(response);
      } else {
        throw new Error(result.error || "Unknown API error");
      }
    } catch (apiError: any) {
      // API call failed
      const processingTime = `${Date.now() - startTime}ms`;
      
      await db.update(tiktokBoosts)
        .set({
          status: "failed",
          processingTime,
          errorMessage: apiError.message,
        })
        .where(eq(tiktokBoosts.id, boost[0].id));

      const response: ApiResponse = {
        success: false,
        message: "Gagal memproses request ke N1Panel API",
        error: apiError.message,
        data: {
          viewsAdded: 0,
          status: "failed",
          processingTime,
          boostsToday: todayBoosts.length,
          boostsRemaining: 5 - todayBoosts.length
        }
      };

      return NextResponse.json(response, { status: 500 });
    }

  } catch (error: any) {
    console.error("Boost endpoint error:", error);
    
    const response: ApiResponse = {
      success: false,
      message: "Terjadi kesalahan server",
      error: error.message
    };

    return NextResponse.json(response, { status: 500 });
  }
}