import { NextRequest, NextResponse } from 'next/server'
import { storage } from '../../../../server/storage'
import { tiktokBoostSchema } from '../../../../shared/schema'
import { validateTikTokUrl } from '../../../../client/src/lib/utils'

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    
    // Validate request body
    const validatedData = tiktokBoostSchema.parse(body)
    
    // Validate TikTok URL format
    if (!validateTikTokUrl(validatedData.url)) {
      return NextResponse.json(
        { success: false, error: 'Invalid TikTok URL format' },
        { status: 400 }
      )
    }

    // Check if boost is allowed
    const canBoostResult = await storage.canBoost(validatedData.url)
    if (!canBoostResult.canBoost) {
      return NextResponse.json({
        success: false,
        message: canBoostResult.reason || 'Cannot boost this video right now',
        data: {
          nextBoostAt: canBoostResult.nextBoostAt?.toISOString(),
          boostsToday: canBoostResult.boostsToday,
          boostsRemaining: Math.max(0, 3 - canBoostResult.boostsToday)
        }
      })
    }

    // Create boost record
    const boost = await storage.createTiktokBoost({
      url: validatedData.url
    })

    // Simulate API call to N1Panel (replace with actual API call)
    const startTime = Date.now()
    
    try {
      // Simulate processing
      await new Promise(resolve => setTimeout(resolve, 1000 + Math.random() * 1000))
      
      const viewsAdded = 1000 // Standard package
      const processingTime = `${((Date.now() - startTime) / 1000).toFixed(1)}s`
      
      // Update boost record
      await storage.updateTiktokBoost(boost.id, {
        status: 'completed',
        viewsAdded,
        processingTime
      })

      // Calculate next boost time (8 hours from now)
      const nextBoostAt = new Date(Date.now() + 8 * 60 * 60 * 1000)
      
      const response = {
        success: true,
        message: 'Views berhasil ditambahkan ke video TikTok Anda!',
        data: {
          viewsAdded,
          status: 'completed',
          processingTime,
          videoTitle: 'TikTok Video',
          orderId: `TK${boost.id.toString().padStart(6, '0')}`,
          nextBoostAt: nextBoostAt.toISOString(),
          boostsToday: canBoostResult.boostsToday + 1,
          boostsRemaining: Math.max(0, 2 - canBoostResult.boostsToday)
        }
      }

      return NextResponse.json(response)
      
    } catch (apiError) {
      // Update boost record as failed
      await storage.updateTiktokBoost(boost.id, {
        status: 'failed',
        processingTime: `${((Date.now() - startTime) / 1000).toFixed(1)}s`
      })

      const response = {
        success: false,
        message: 'Gagal memproses boost. Silakan coba lagi nanti.',
        error: 'API processing failed'
      }

      return NextResponse.json(response, { status: 500 })
    }

  } catch (error) {
    console.error('Boost error:', error)
    
    if (error instanceof Error && error.name === 'ZodError') {
      return NextResponse.json(
        { success: false, error: 'Invalid request data' },
        { status: 400 }
      )
    }

    const response = {
      success: false,
      message: 'Terjadi kesalahan internal. Silakan coba lagi nanti.',
      error: 'Internal server error'
    }

    return NextResponse.json(response, { status: 500 })
  }
}