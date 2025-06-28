import { NextResponse } from 'next/server';
import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import { gte, sql, count, sum, avg } from 'drizzle-orm';
import { tiktokBoosts } from '@/shared/schema';

const dbConnection = neon(process.env.DATABASE_URL!);
const db = drizzle(dbConnection);

export async function GET() {
  try {
    // Get today's date range
    const today = new Date();
    const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // Get today's boosts with efficient query
    const todayBoosts = await db
      .select()
      .from(tiktokBoosts)
      .where(gte(tiktokBoosts.createdAt, todayStart));
    
    // Calculate stats
    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter(boost => boost.status === 'completed').length;
    const successRate = videosToday > 0 ? (successfulBoosts / videosToday) * 100 : 0;
    
    // Calculate average processing time from completed boosts
    const completedBoosts = todayBoosts.filter(boost => 
      boost.status === 'completed' && 
      boost.processingTime && 
      boost.processingTime !== '0s'
    );
    
    const processingTimes = completedBoosts.map(boost => {
      const timeStr = (boost.processingTime || '0s').replace('s', '');
      return parseFloat(timeStr) || 0;
    });
    
    const avgTimeSeconds = processingTimes.length > 0 
      ? processingTimes.reduce((sum, time) => sum + time, 0) / processingTimes.length
      : 0;
    
    const avgTime = avgTimeSeconds > 0 ? `${avgTimeSeconds.toFixed(1)}s` : '0s';
    
    return NextResponse.json({
      videosToday,
      totalViews,
      successRate: Math.round(successRate * 10) / 10, // Round to 1 decimal
      avgTime
    });
    
  } catch (error) {
    console.error('Stats error:', error);
    
    return NextResponse.json({
      videosToday: 0,
      totalViews: 0,
      successRate: 0,
      avgTime: '0s'
    });
  }
}