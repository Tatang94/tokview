import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ApiResponse } from "@/shared/schema";
import { formatNumber } from "@/lib/utils";
import { Eye, CheckCircle, Clock, RotateCcw, Plus, TrendingUp, Share2, Copy } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface ResultsSectionProps {
  results: ApiResponse;
  videoUrl: string;
  onBoostAgain: () => void;
  onNewVideo: () => void;
}

export default function ResultsSection({ results, videoUrl, onBoostAgain, onNewVideo }: ResultsSectionProps) {
  const { toast } = useToast();
  
  if (!results.success || !results.data) return null;

  const { data } = results;

  const copyUrl = () => {
    navigator.clipboard.writeText(videoUrl);
    toast({
      title: "Berhasil!",
      description: "URL video berhasil disalin",
    });
  };

  const shareVideo = () => {
    if (navigator.share) {
      navigator.share({
        title: 'Video TikTok Saya',
        url: videoUrl
      });
    } else {
      copyUrl();
    }
  };

  return (
    <div className="space-y-6">
      {/* Success Header */}
      <div className="bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl p-6 text-center">
        <div className="w-16 h-16 bg-white/20 rounded-2xl mx-auto mb-4 flex items-center justify-center">
          <CheckCircle className="text-white" size={32} />
        </div>
        <h2 className="text-white font-bold text-xl mb-2">Boost Berhasil!</h2>
        <p className="text-white/80 text-sm">Video TikTok Anda telah berhasil diboost</p>
      </div>

      {/* Results Card */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-blue-500 to-purple-500 px-6 py-4">
          <div className="flex items-center">
            <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
              <TrendingUp className="text-white" size={20} />
            </div>
            <div>
              <h3 className="text-white font-bold text-lg">Detail Hasil</h3>
              <p className="text-white/80 text-sm">Informasi boost yang telah dilakukan</p>
            </div>
          </div>
        </div>

        <div className="p-6">
          {/* Stats Grid */}
          <div className="grid grid-cols-2 gap-4 mb-6">
            <div className="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-4 border border-blue-100">
              <div className="flex items-center mb-2">
                <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                  <Eye className="text-blue-600" size={16} />
                </div>
                <div className="text-xs text-blue-600 font-medium">Views Added</div>
              </div>
              <div className="text-2xl font-bold text-blue-900">
                +{formatNumber(data.viewsAdded)}
              </div>
            </div>

            <div className="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-100">
              <div className="flex items-center mb-2">
                <div className="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                  <Clock className="text-purple-600" size={16} />
                </div>
                <div className="text-xs text-purple-600 font-medium">Process Time</div>
              </div>
              <div className="text-2xl font-bold text-purple-900">
                {data.processingTime}
              </div>
            </div>
          </div>

          {/* Status Badge */}
          <div className="flex justify-center mb-6">
            <Badge variant="default" className="bg-green-100 text-green-800 border-green-200 px-4 py-2">
              <CheckCircle className="w-4 h-4 mr-2" />
              Status: {data.status}
            </Badge>
          </div>

          {/* Video Info */}
          {data.videoTitle && (
            <div className="bg-gray-50 rounded-xl p-4 mb-6">
              <h4 className="font-semibold text-gray-900 mb-2">Video Information</h4>
              <p className="text-sm text-gray-600 mb-3">{data.videoTitle}</p>
              <div className="flex items-center space-x-2">
                <button
                  onClick={copyUrl}
                  className="flex items-center px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  <Copy className="w-4 h-4 mr-2" />
                  Copy URL
                </button>
                <button
                  onClick={shareVideo}
                  className="flex items-center px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  <Share2 className="w-4 h-4 mr-2" />
                  Share
                </button>
              </div>
            </div>
          )}

          {/* Boost Stats */}
          {(data.boostsToday !== undefined) && (
            <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
              <h4 className="font-semibold text-blue-900 mb-3">Boost Statistics</h4>
              <div className="grid grid-cols-2 gap-4">
                <div className="text-center">
                  <div className="text-xl font-bold text-blue-900">
                    {data.boostsToday}/5
                  </div>
                  <p className="text-blue-600 text-sm">Boost Digunakan</p>
                </div>
                <div className="text-center">
                  <div className="text-xl font-bold text-green-900">
                    {data.boostsRemaining}
                  </div>
                  <p className="text-green-600 text-sm">Boost Tersisa</p>
                </div>
              </div>
            </div>
          )}

          {/* Order ID */}
          {data.orderId && (
            <div className="bg-gray-50 rounded-xl p-4 mb-6">
              <h4 className="font-semibold text-gray-900 mb-2">Order Reference</h4>
              <p className="text-sm text-gray-600 font-mono">{data.orderId}</p>
            </div>
          )}

          {/* Action Buttons */}
          <div className="space-y-3">
            <Button
              onClick={onBoostAgain}
              className="w-full h-12 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold rounded-xl transition-all duration-200 transform hover:scale-[1.02]"
              disabled={(data.boostsRemaining !== undefined && data.boostsRemaining <= 0)}
            >
              <RotateCcw className="mr-2 h-5 w-5" />
              {(data.boostsRemaining !== undefined && data.boostsRemaining <= 0) 
                ? 'Batas Harian Tercapai' 
                : 'Boost Video Ini Lagi'
              }
            </Button>
            
            <Button
              onClick={onNewVideo}
              variant="outline"
              className="w-full h-12 border-2 border-gray-200 hover:border-purple-300 hover:bg-purple-50 text-gray-700 font-medium rounded-xl transition-all duration-200"
            >
              <Plus className="mr-2 h-5 w-5" />
              Boost Video Baru
            </Button>
          </div>

          {/* Additional Info */}
          <div className="mt-6 bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-xl p-4">
            <div className="flex items-start">
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/>
                </svg>
              </div>
              <div className="ml-3">
                <h4 className="text-sm font-bold text-green-900 mb-1">Tips untuk Hasil Optimal</h4>
                <div className="text-sm text-green-800 space-y-1">
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-green-400 rounded-full mr-2"></div>
                    Views akan bertambah secara bertahap dalam 24 jam
                  </div>
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-green-400 rounded-full mr-2"></div>
                    Pastikan video tetap public untuk hasil terbaik
                  </div>
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-green-400 rounded-full mr-2"></div>
                    Share video untuk mendapat engagement organik
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}