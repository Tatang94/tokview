import { useState } from "react";
import TiktokForm from "@/components/tiktok-form";
import ResultsSection from "@/components/results-section";
import StatsSection from "@/components/stats-section";
import { ApiResponse } from "@shared/schema";

export default function Home() {
  const [results, setResults] = useState<ApiResponse | null>(null);
  const [lastUrl, setLastUrl] = useState<string>("");

  const handleBoostSuccess = (response: ApiResponse, url: string) => {
    setResults(response);
    setLastUrl(url);
  };

  const handleBoostAgain = () => {
    setResults(null);
  };

  const handleNewVideo = () => {
    setResults(null);
    setLastUrl("");
  };

  return (
    <div className="min-h-screen" style={{ backgroundColor: 'var(--background-light)' }}>
      {/* Header */}
      <header className="gradient-bg text-white py-6 shadow-lg">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-center space-x-3">
            <i className="fab fa-tiktok text-3xl"></i>
            <h1 className="text-2xl md:text-3xl font-bold">TikTok View Booster</h1>
          </div>
          <p className="text-center mt-2 text-pink-100 text-sm md:text-base">
            Tingkatkan engagement video TikTok Anda dengan mudah
          </p>
          <div className="text-center mt-3">
            <div className="inline-flex items-center bg-white/20 rounded-full px-4 py-2 text-sm">
              <span className="text-yellow-300 mr-2">🚀</span>
              <span>Maksimal 5 boost per hari - Tanpa timer cooldown</span>
            </div>
          </div>
        </div>
      </header>

      {/* Main Container */}
      <div className="container mx-auto px-4 py-8 max-w-2xl">
        <TiktokForm onSuccess={handleBoostSuccess} />
        
        {results && (
          <ResultsSection 
            results={results} 
            videoUrl={lastUrl}
            onBoostAgain={handleBoostAgain}
            onNewVideo={handleNewVideo}
          />
        )}
        
        <StatsSection />
      </div>
    </div>
  );
}
