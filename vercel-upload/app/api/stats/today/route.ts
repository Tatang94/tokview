import { NextResponse } from "next/server";
import { neon } from "@neondatabase/serverless";
import { drizzle } from "drizzle-orm/neon-http";
import { gte } from "drizzle-orm";

// Import from local schema file
import { tiktokBoosts, type StatsResponse } from "@/shared/schema";



const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  throw new Error("DATABASE_URL environment variable is required");
}

const sql = neon(databaseUrl);
const db = drizzle(sql);

export async function GET() {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Get today's boosts
    const todayBoosts = await db.select()
      .from(tiktokBoosts)
      .where(gte(tiktokBoosts.createdAt, today));

    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum: number, boost: any) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter((boost: any) => boost.status === "completed").length;
    const successRate = videosToday > 0 ? Math.round((successfulBoosts / videosToday) * 100) : 0;

    // Calculate average processing time
    const completedBoosts = todayBoosts.filter((boost: any) => boost.processingTime);
    let avgTime = "0ms";
    
    if (completedBoosts.length > 0) {
      const totalTime = completedBoosts.reduce((sum: number, boost: any) => {
        const timeMs = parseInt(boost.processingTime?.replace('ms', '') || '0');
        return sum + timeMs;
      }, 0);
      const avgTimeMs = Math.round(totalTime / completedBoosts.length);
      avgTime = `${avgTimeMs}ms`;
    }

    const response: StatsResponse = {
      videosToday,
      totalViews,
      successRate,
      avgTime
    };

    return NextResponse.json(response);

  } catch (error: any) {
    console.error("Stats error:", error);
    return NextResponse.json(
      { error: "Failed to get stats" },
      { status: 500 }
    );
  }
}