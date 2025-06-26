import { Card, CardContent } from "@/components/ui/card";
import { useQuery } from "@tanstack/react-query";
import { StatsResponse } from "../shared/schema";
import { formatNumber } from "@/lib/utils";
import { BarChart3 } from "lucide-react";

export default function StatsSection() {
  const { data: stats } = useQuery<StatsResponse>({
    queryKey: ["/api/stats/today"],
  });

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-500 to-purple-500 px-6 py-4">
        <div className="flex items-center">
          <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
            <BarChart3 className="text-white" size={20} />
          </div>
          <div>
            <h3 className="text-white font-bold text-lg">Statistik Hari Ini</h3>
            <p className="text-white/80 text-sm">Data real-time sistem</p>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="grid grid-cols-2 gap-4">
          <div className="bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-4 border border-pink-100">
            <div className="flex items-center justify-between mb-2">
              <div className="w-8 h-8 bg-pink-100 rounded-lg flex items-center justify-center">
                <svg className="w-4 h-4 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                </svg>
              </div>
            </div>
            <div className="text-2xl font-bold text-gray-900 mb-1">
              {stats?.videosToday || 0}
            </div>
            <div className="text-sm text-gray-600">Video Diboost</div>
          </div>

          <div className="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl p-4 border border-blue-100">
            <div className="flex items-center justify-between mb-2">
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg className="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                  <path fillRule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clipRule="evenodd"/>
                </svg>
              </div>
            </div>
            <div className="text-2xl font-bold text-gray-900 mb-1">
              {stats ? formatNumber(stats.totalViews) : '0'}
            </div>
            <div className="text-sm text-gray-600">Total Views</div>
          </div>

          <div className="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
            <div className="flex items-center justify-between mb-2">
              <div className="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd"/>
                </svg>
              </div>
            </div>
            <div className="text-2xl font-bold text-gray-900 mb-1">
              {stats?.successRate.toFixed(1) || '0'}%
            </div>
            <div className="text-sm text-gray-600">Success Rate</div>
          </div>

          <div className="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-100">
            <div className="flex items-center justify-between mb-2">
              <div className="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg className="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd"/>
                </svg>
              </div>
            </div>
            <div className="text-2xl font-bold text-gray-900 mb-1">
              {stats?.avgTime || '0s'}
            </div>
            <div className="text-sm text-gray-600">Avg. Time</div>
          </div>
        </div>

        {/* Additional info */}
        <div className="mt-6 bg-gray-50 rounded-xl p-4">
          <div className="flex items-center">
            <div className="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
            <span className="text-sm text-gray-600">Data diperbarui secara real-time</span>
          </div>
        </div>
      </div>
    </div>
  );
}
