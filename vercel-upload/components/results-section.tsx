import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ApiResponse } from "../shared/schema";
import { formatNumber, formatDate } from "@/lib/utils";
import { Eye, CheckCircle, Clock, Calendar, RotateCcw, Plus, TrendingUp, Heart } from "lucide-react";
import TimerDisplay from "@/components/timer-display";

interface ResultsSectionProps {
  results: ApiResponse;
  videoUrl: string;
  onBoostAgain: () => void;
  onNewVideo: () => void;
}

export default function ResultsSection({ results, videoUrl, onBoostAgain, onNewVideo }: ResultsSectionProps) {
  if (!results.success || !results.data) return null;

  const { data } = results;

  return (
    <>
      {data.nextBoostAt && (
        <TimerDisplay 
          nextBoostAt={data.nextBoostAt}
          boostsToday={data.boostsToday}
          boostsRemaining={data.boostsRemaining}
        />
      )}
      <Card className="bg-surface shadow-lg mb-6">
      <CardContent className="p-6 md:p-8">
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
            <TrendingUp className="text-green-600 text-2xl" size={24} />
          </div>
          <h3 className="text-xl font-semibold text-gray-800 mb-2">Hasil Boost</h3>
          <p className="text-gray-600 text-sm">Status proses boost view TikTok Anda</p>
        </div>

        {/* Results Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div className="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Eye className="text-blue-600" size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-blue-800">Views Ditambahkan</p>
                <p className="text-2xl font-bold text-blue-900">
                  +{formatNumber(data.viewsAdded)}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <CheckCircle className="text-green-600" size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-green-800">Status</p>
                <Badge variant={data.status === 'completed' ? 'default' : 'secondary'} className="text-lg font-semibold">
                  {data.status === 'completed' ? 'Berhasil' : data.status}
                </Badge>
              </div>
            </div>
          </div>

          <div className="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Clock className="text-purple-600" size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-purple-800">Waktu Proses</p>
                <p className="text-lg font-semibold text-purple-900">
                  {data.processingTime}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <Calendar className="text-orange-600" size={20} />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-orange-800">Tanggal</p>
                <p className="text-sm font-semibold text-orange-900">
                  {formatDate(new Date())}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Video Preview */}
        <div className="border rounded-lg p-4 bg-gray-50">
          <h4 className="font-medium text-gray-800 mb-3 flex items-center">
            <i className="fab fa-tiktok mr-2 text-tiktok"></i>
            Video yang Di-boost
          </h4>
          <div className="flex items-center space-x-3">
            <div className="w-16 h-16 bg-gray-300 rounded-lg flex items-center justify-center">
              <i className="fas fa-play text-gray-600"></i>
            </div>
            <div className="flex-1">
              <p className="font-medium text-gray-800 text-sm">
                {data.videoTitle || "TikTok Video"}
              </p>
              <p className="text-xs text-gray-500 break-all">
                {videoUrl}
              </p>
              <div className="flex items-center mt-1 space-x-4 text-xs text-gray-500">
                <span className="flex items-center">
                  <Eye size={12} className="mr-1" />
                  {formatNumber(data.viewsAdded + 15200)} views
                </span>
                <span className="flex items-center">
                  <Heart size={12} className="mr-1" />
                  {formatNumber(1800)} likes
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-3 mt-6">
          <Button 
            onClick={onBoostAgain}
            className="flex-1 bg-tiktok text-white hover:bg-tiktok-dark transition-colors duration-200"
          >
            <RotateCcw className="mr-2 h-4 w-4" />
            Boost Lagi
          </Button>
          <Button 
            onClick={onNewVideo}
            variant="secondary"
            className="flex-1 bg-gray-600 text-white hover:bg-gray-700 transition-colors duration-200"
          >
            <Plus className="mr-2 h-4 w-4" />
            Video Baru
          </Button>
        </div>
      </CardContent>
    </Card>
    </>
  );
}
