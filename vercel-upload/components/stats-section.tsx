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
    <Card className="bg-surface shadow-lg">
      <CardContent className="p-6 md:p-8">
        <h3 className="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <BarChart3 className="mr-2 text-tiktok" size={20} />
          Statistik Hari Ini
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="text-center">
            <div className="text-2xl font-bold text-tiktok">
              {stats?.videosToday || 0}
            </div>
            <div className="text-xs text-gray-600">Video Di-boost</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-blue-600">
              {stats ? formatNumber(stats.totalViews) : '0'}
            </div>
            <div className="text-xs text-gray-600">Total Views</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-green-600">
              {stats?.successRate.toFixed(1) || '0'}%
            </div>
            <div className="text-xs text-gray-600">Success Rate</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-purple-600">
              {stats?.avgTime || '0s'}
            </div>
            <div className="text-xs text-gray-600">Avg. Time</div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
