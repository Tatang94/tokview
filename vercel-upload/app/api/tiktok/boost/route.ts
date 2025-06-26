import { NextRequest, NextResponse } from 'next/server';
import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import { eq } from 'drizzle-orm';
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

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { url } = boostRequestSchema.parse(body);
    
    // Get client IP
    const forwarded = request.headers.get('x-forwarded-for');
    const ip = forwarded ? forwarded.split(',')[0] : '127.0.0.1';
    
    // Check daily limit (5 per IP) - simplified approach
    const today = new Date();
    const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // Get all boosts for this IP today
    const allBoosts = await db.select().from(tiktokBoosts).where(eq(tiktokBoosts.ipAddress, ip));
    
    const boostsToday = allBoosts.filter(boost => {
      const boostDate = new Date(boost.createdAt!);
      return boostDate >= todayStart;
    }).length;
    
    if (boostsToday >= 5) {
      return NextResponse.json({
        success: false,
        message: 'Batas harian tercapai (5 boost per hari)',
        error: 'Daily limit exceeded'
      }, { status: 429 });
    }
    
    // Create boost record
    const startTime = Date.now();
    
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
    
    // Simulate processing (in real app, call N1Panel API)
    const processingTime = Math.floor((Date.now() - startTime) / 1000);
    const viewsAdded = Math.floor(Math.random() * 5000) + 1000; // 1000-6000 views
    
    // Update boost record
    await db
      .update(tiktokBoosts)
      .set({
        status: 'completed',
        viewsAdded,
        processingTime: `${processingTime}s`
      })
      .where(eq(tiktokBoosts.id, newBoost.id));
    
    const boostsRemaining = 5 - (boostsToday + 1);
    
    return NextResponse.json({
      success: true,
      message: 'Boost berhasil!',
      data: {
        viewsAdded,
        status: 'completed',
        processingTime: `${processingTime}s`,
        videoTitle: 'TikTok Video',
        orderId: `TK${newBoost.id.toString().padStart(6, '0')}`,
        boostsToday: boostsToday + 1,
        boostsRemaining
      }
    });
    
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