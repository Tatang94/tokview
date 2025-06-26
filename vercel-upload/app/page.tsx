'use client'

import React, { useState } from 'react'
import TiktokForm from '@/components/tiktok-form'
import StatsSection from '@/components/stats-section'
import ResultsSection from '@/components/results-section'

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
    <main className="min-h-screen bg-gradient-to-br from-purple-50 via-pink-50 to-blue-50">
      {/* Mobile Header */}
      <div className="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-200/20 shadow-sm">
        <div className="px-4 py-3 flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <div className="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
              <span className="text-white font-bold text-sm">T</span>
            </div>
            <h1 className="font-bold text-gray-900 text-lg">TikTok Booster</h1>
          </div>
          <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
            <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clipRule="evenodd" />
            </svg>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="px-4 py-6 max-w-md mx-auto">
        {results ? (
          <ResultsSection 
            results={results}
            videoUrl={videoUrl}
            onBoostAgain={handleBoostAgain}
            onNewVideo={handleNewVideo}
          />
        ) : (
          <div className="space-y-6">
            {/* Welcome Card */}
            <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
              <div className="text-center mb-6">
                <div className="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl mx-auto mb-4 flex items-center justify-center">
                  <svg className="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                  </svg>
                </div>
                <h2 className="text-xl font-bold text-gray-900 mb-2">Boost Video TikTok</h2>
                <p className="text-gray-600 text-sm">Tingkatkan views video TikTok kamu dengan aman dan cepat</p>
              </div>
            </div>

            {/* Form Card */}
            <TiktokForm onSuccess={handleBoostSuccess} />
            
            {/* Stats Card */}
            <StatsSection />

            {/* Features Grid */}
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
                <div className="w-10 h-10 bg-blue-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                  <svg className="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                  </svg>
                </div>
                <h3 className="font-semibold text-gray-900 text-sm">Aman</h3>
                <p className="text-xs text-gray-600">100% Secure</p>
              </div>
              
              <div className="bg-white rounded-xl p-4 text-center shadow-sm border border-gray-100">
                <div className="w-10 h-10 bg-green-100 rounded-lg mx-auto mb-2 flex items-center justify-center">
                  <svg className="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clipRule="evenodd" />
                  </svg>
                </div>
                <h3 className="font-semibold text-gray-900 text-sm">Cepat</h3>
                <p className="text-xs text-gray-600">Instant Boost</p>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Bottom Navigation */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-4 py-2">
        <div className="flex justify-around max-w-md mx-auto">
          <button className="flex flex-col items-center py-2 px-3 text-purple-600">
            <svg className="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
            <span className="text-xs font-medium">Home</span>
          </button>
          
          <button className="flex flex-col items-center py-2 px-3 text-gray-400">
            <svg className="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span className="text-xs">History</span>
          </button>
          
          <button className="flex flex-col items-center py-2 px-3 text-gray-400">
            <svg className="w-5 h-5 mb-1" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/>
            </svg>
            <span className="text-xs">Help</span>
          </button>
        </div>
      </div>

      {/* Add bottom padding to account for fixed navigation */}
      <div className="h-20"></div>
    </main>
  )
}