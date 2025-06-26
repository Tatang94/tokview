import { NextResponse } from 'next/server'
import { storage } from '../../../../server/storage'

export async function GET() {
  try {
    const stats = await storage.getTodayStats()
    return NextResponse.json(stats)
  } catch (error) {
    console.error('Stats error:', error)
    return NextResponse.json(
      { error: 'Failed to fetch stats' },
      { status: 500 }
    )
  }
}