import { NextResponse } from "next/server";
import { neon } from "@neondatabase/serverless";
import { drizzle } from "drizzle-orm/neon-http";
import { tiktokBoosts } from "../../../../shared/schema";
import { gte, sql, eq } from "drizzle-orm";
import type { StatsResponse } from "../../../../shared/schema";

const databaseUrl = process.env.DATABASE_URL;
if (!databaseUrl) {
  throw new Error("DATABASE_URL environment variable is required");
}

const sqlClient = neon(databaseUrl);
const db = drizzle(sqlClient);

export async function GET() {
  try {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Get today's boosts
    const todayBoosts = await db.select()
      .from(tiktokBoosts)
      .where(gte(tiktokBoosts.createdAt, today));

    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter(boost => boost.status === "completed").length;
    const successRate = videosToday > 0 ? Math.round((successfulBoosts / videosToday) * 100) : 0;

    // Calculate average processing time
    const completedBoosts = todayBoosts.filter(boost => boost.processingTime);
    let avgTime = "0ms";
    
    if (completedBoosts.length > 0) {
      const totalTime = completedBoosts.reduce((sum, boost) => {
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