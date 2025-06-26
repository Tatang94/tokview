import { NextResponse } from 'next/server';
import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import { tiktokBoosts } from '@/shared/schema';

const sql = neon(process.env.DATABASE_URL!);
const db = drizzle(sql);

export async function GET() {
  try {
    // Get today's date range
    const today = new Date();
    const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // Get all boosts from today
    const allBoosts = await db.select().from(tiktokBoosts);
    
    const todayBoosts = allBoosts.filter(boost => {
      const boostDate = new Date(boost.createdAt!);
      return boostDate >= todayStart;
    });
    
    // Calculate stats
    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter(boost => boost.status === 'completed').length;
    const successRate = videosToday > 0 ? (successfulBoosts / videosToday) * 100 : 0;
    
    // Calculate average processing time
    const processingTimes = todayBoosts
      .filter(boost => boost.processingTime && boost.processingTime !== '0s')
      .map(boost => {
        const timeStr = (boost.processingTime || '0s').replace('s', '');
        return parseInt(timeStr) || 0;
      });
    
    const avgTimeSeconds = processingTimes.length > 0 
      ? Math.round(processingTimes.reduce((sum, time) => sum + time, 0) / processingTimes.length)
      : 0;
    
    const avgTime = `${avgTimeSeconds}s`;
    
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