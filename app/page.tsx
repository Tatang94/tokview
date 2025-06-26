'use client'

import React, { useState } from 'react'
import TiktokForm from '../client/src/components/tiktok-form'
import StatsSection from '../client/src/components/stats-section'
import ResultsSection from '../client/src/components/results-section'

interface ApiResponse {
  success: boolean;
  message: string;
  data?: {
    viewsAdded: number;
    status: string;
    processingTime: string;
    videoTitle?: string;
    orderId?: string;
    nextBoostAt?: string;
    boostsToday?: number;
    boostsRemaining?: number;
  };
  error?: string;
}

export default function HomePage() {
  const [results, setResults] = useState<ApiResponse | null>(null)
  const [videoUrl, setVideoUrl] = useState<string>('')

  const handleBoostSuccess = (response: ApiResponse, url: string) => {
    setResults(response)
    setVideoUrl(url)
  }

  const handleBoostAgain = () => {
    setResults(null)
    setVideoUrl('')
  }

  const handleNewVideo = () => {
    setResults(null)
    setVideoUrl('')
  }

  return (
    <main className="min-h-screen bg-background">
      <div className="container mx-auto px-4 py-8">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold text-primary mb-4">
            TikTok View Booster
          </h1>
          <p className="text-muted-foreground text-lg">
            Boost your TikTok video views safely and effectively
          </p>
        </div>
        
        {results ? (
          <ResultsSection 
            results={results}
            videoUrl={videoUrl}
            onBoostAgain={handleBoostAgain}
            onNewVideo={handleNewVideo}
          />
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div className="space-y-6">
              <TiktokForm onSuccess={handleBoostSuccess} />
            </div>
            
            <div className="space-y-6">
              <StatsSection />
            </div>
          </div>
        )}
      </div>
    </main>
  )
}