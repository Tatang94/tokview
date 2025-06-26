import { useState, useEffect } from "react";
import { Clock, Calendar } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface TimerDisplayProps {
  nextBoostAt?: string;
  boostsToday?: number;
  boostsRemaining?: number;
}

export default function TimerDisplay({ nextBoostAt, boostsToday = 0, boostsRemaining = 3 }: TimerDisplayProps) {
  const [timeLeft, setTimeLeft] = useState<string>("");
  const [canBoost, setCanBoost] = useState(true);

  useEffect(() => {
    if (!nextBoostAt) {
      setCanBoost(true);
      setTimeLeft("");
      return;
    }

    const updateTimer = () => {
      const now = new Date();
      const nextBoost = new Date(nextBoostAt);
      const diff = nextBoost.getTime() - now.getTime();

      if (diff <= 0) {
        setCanBoost(true);
        setTimeLeft("");
        return;
      }

      setCanBoost(false);
      
      const hours = Math.floor(diff / (1000 * 60 * 60));
      const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((diff % (1000 * 60)) / 1000);

      setTimeLeft(`${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`);
    };

    updateTimer();
    const interval = setInterval(updateTimer, 1000);

    return () => clearInterval(interval);
  }, [nextBoostAt]);

  if (boostsRemaining <= 0) {
    return (
      <Card className="bg-red-50 border-red-200 mb-6">
        <CardContent className="p-4">
          <div className="flex items-center justify-center space-x-3 text-red-700">
            <Calendar size={20} />
            <div className="text-center">
              <p className="font-semibold">Batas Harian Tercapai</p>
              <p className="text-sm">Anda sudah melakukan 3 boost hari ini. Coba lagi besok!</p>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  if (!canBoost && timeLeft) {
    return (
      <Card className="bg-orange-50 border-orange-200 mb-6">
        <CardContent className="p-4">
          <div className="text-center">
            <div className="flex items-center justify-center space-x-2 mb-3">
              <Clock className="text-orange-600" size={20} />
              <h3 className="font-semibold text-orange-800">Waktu Tunggu</h3>
            </div>
            
            <div className="bg-orange-100 rounded-lg p-4 mb-3">
              <div className="text-2xl font-bold text-orange-900 font-mono">
                {timeLeft}
              </div>
              <p className="text-sm text-orange-700 mt-1">
                Waktu tersisa hingga boost berikutnya
              </p>
            </div>

            <div className="flex justify-center space-x-4 text-sm">
              <div className="text-center">
                <Badge variant="secondary" className="bg-orange-200 text-orange-800">
                  {boostsToday}/3
                </Badge>
                <p className="text-orange-600 mt-1">Boost Hari Ini</p>
              </div>
              <div className="text-center">
                <Badge variant="secondary" className="bg-green-200 text-green-800">
                  {boostsRemaining}
                </Badge>
                <p className="text-green-600 mt-1">Boost Tersisa</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-green-50 border-green-200 mb-6">
      <CardContent className="p-4">
        <div className="text-center">
          <div className="flex items-center justify-center space-x-2 mb-2">
            <Clock className="text-green-600" size={20} />
            <h3 className="font-semibold text-green-800">Siap Boost!</h3>
          </div>
          
          <div className="flex justify-center space-x-4 text-sm">
            <div className="text-center">
              <Badge variant="secondary" className="bg-green-200 text-green-800">
                {boostsToday}/3
              </Badge>
              <p className="text-green-600 mt-1">Boost Hari Ini</p>
            </div>
            <div className="text-center">
              <Badge variant="secondary" className="bg-blue-200 text-blue-800">
                {boostsRemaining}
              </Badge>
              <p className="text-blue-600 mt-1">Boost Tersisa</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}