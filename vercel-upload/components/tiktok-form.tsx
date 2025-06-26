import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent } from "@/components/ui/card";
import { Progress } from "@/components/ui/progress";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { tiktokBoostSchema, type TiktokBoostRequest, type ApiResponse } from "@/shared/schema";
import { validateTikTokUrl } from "@/lib/utils";
import { CheckCircle, XCircle, Eye, EyeOff, Key, Link as LinkIcon, Rocket, Loader2, Clock } from "lucide-react";
import TimerDisplay from "@/components/timer-display";

interface TiktokFormProps {
  onSuccess: (response: ApiResponse, url: string) => void;
}

export default function TiktokForm({ onSuccess }: TiktokFormProps) {
  const [showApiKey, setShowApiKey] = useState(false);
  const [showApiKeySection, setShowApiKeySection] = useState(false);
  const [progress, setProgress] = useState(0);
  const [boostStats, setBoostStats] = useState<{ boostsToday?: number; boostsRemaining?: number }>({});
  const { toast } = useToast();

  const form = useForm<TiktokBoostRequest>({
    resolver: zodResolver(tiktokBoostSchema),
    defaultValues: {
      url: "",
      apiKey: "",
    },
  });

  const boostMutation = useMutation({
    mutationFn: async (data: TiktokBoostRequest) => {
      const response = await apiRequest("POST", "/api/tiktok/boost", data);
      return response.json() as Promise<ApiResponse>;
    },
    onSuccess: (response, variables) => {
      if (response.success) {
        toast({
          title: "Berhasil!",
          description: response.message,
        });
        onSuccess(response, variables.url);
        form.reset();
        setBoostStats({
          boostsToday: response.data?.boostsToday,
          boostsRemaining: response.data?.boostsRemaining,
        });
      } else {
        // Special handling for VPN blocking
        if (response.error === "VPN_BLOCKED") {
          toast({
            title: "VPN/Proxy Terdeteksi!",
            description: response.message,
            variant: "destructive",
            duration: 8000,
          });
        } else {
          toast({
            title: "Error!",
            description: response.error || response.message,
            variant: "destructive",
          });
        }
        
        // Update boost stats even on error
        if (response.data) {
          setBoostStats({
            boostsToday: response.data.boostsToday,
            boostsRemaining: response.data.boostsRemaining,
          });
        }
      }
      setProgress(0);
    },
    onError: (error: any) => {
      console.error("Boost error:", error);
      toast({
        title: "Error!",
        description: error.message || "Terjadi kesalahan saat memproses request",
        variant: "destructive",
      });
      setProgress(0);
    },
  });



  const onSubmit = (data: TiktokBoostRequest) => {
    setProgress(0);
    
    // Simulate progress
    const progressInterval = setInterval(() => {
      setProgress(prev => {
        if (prev >= 100) {
          clearInterval(progressInterval);
          return 100;
        }
        return prev + Math.random() * 15;
      });
    }, 200);

    boostMutation.mutate(data);
  };

  const url = form.watch("url");
  const isValidUrl = Boolean(url && validateTikTokUrl(url));
  const isBlocked = (boostStats.boostsRemaining !== undefined && boostStats.boostsRemaining <= 0);

  return (
    <>
      <Card className="bg-surface shadow-lg mb-6">
      <CardContent className="p-6 md:p-8">
        <div className="text-center mb-6">
          <div className="inline-flex items-center justify-center w-16 h-16 bg-pink-light rounded-full mb-4">
            <LinkIcon className="text-tiktok text-2xl" size={24} />
          </div>
          <h2 className="text-xl md:text-2xl font-semibold text-gray-800 mb-2">
            Masukkan URL TikTok
          </h2>
          <p className="text-gray-600 text-sm md:text-base">
            Paste link video TikTok yang ingin ditingkatkan viewnya
          </p>
        </div>

        {/* Boost Stats Display */}
        {(boostStats.boostsToday !== undefined) && (
          <Card className={`mb-6 ${boostStats.boostsRemaining === 0 ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'}`}>
            <CardContent className="p-4">
              <div className="text-center">
                <div className="flex items-center justify-center space-x-2 mb-3">
                  <Eye className={`${boostStats.boostsRemaining === 0 ? 'text-red-600' : 'text-blue-600'}`} size={20} />
                  <h3 className={`font-semibold ${boostStats.boostsRemaining === 0 ? 'text-red-800' : 'text-blue-800'}`}>
                    {boostStats.boostsRemaining === 0 ? 'Batas Harian Tercapai' : 'Status Boost Hari Ini'}
                  </h3>
                </div>
                
                {boostStats.boostsRemaining === 0 ? (
                  <div className="bg-red-100 rounded-lg p-4 mb-3">
                    <p className="text-red-800 font-semibold mb-2">Batas harian Anda sudah habis!</p>
                    <p className="text-red-600 text-sm">Kembali lagi besok untuk melakukan boost lagi.</p>
                    <p className="text-red-500 text-xs mt-2">Reset otomatis setiap hari pada pukul 00:00 WIB</p>
                  </div>
                ) : (
                  <div className="flex justify-center space-x-6 text-sm">
                    <div className="text-center">
                      <div className="text-2xl font-bold text-blue-900">
                        {boostStats.boostsToday}/5
                      </div>
                      <p className="text-blue-600">Boost Digunakan</p>
                    </div>
                    <div className="text-center">
                      <div className="text-2xl font-bold text-green-900">
                        {boostStats.boostsRemaining}
                      </div>
                      <p className="text-green-600">Boost Tersisa</p>
                    </div>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
        )}

        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* URL Input */}
          <div className="space-y-2">
            <Label htmlFor="url" className="text-sm font-medium text-gray-700">
              URL TikTok <span className="text-tiktok">*</span>
            </Label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i className="fab fa-tiktok text-gray-400"></i>
              </div>
              <Input
                id="url"
                type="url"
                placeholder="https://vt.tiktok.com/ZSB1Qvfyr/"
                className={`pl-10 pr-12 transition-colors duration-200 ${
                  url && isValidUrl 
                    ? "border-green-300 focus:border-green-500" 
                    : url && !isValidUrl 
                    ? "border-red-300 focus:border-red-500"
                    : ""
                }`}
                {...form.register("url")}
              />
              {url && (
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                  {isValidUrl ? (
                    <CheckCircle className="text-green-500" size={20} />
                  ) : (
                    <XCircle className="text-red-500" size={20} />
                  )}
                </div>
              )}
            </div>
            {form.formState.errors.url && (
              <div className="text-red-500 text-xs flex items-center">
                <i className="fas fa-exclamation-triangle mr-1"></i>
                {form.formState.errors.url.message}
              </div>
            )}
            <div className="text-xs text-gray-500 flex items-center">
              <i className="fas fa-info-circle mr-1"></i>
              Contoh: https://www.tiktok.com/@username/video/1234567890
            </div>
          </div>

          {/* API Key Section */}
          {showApiKeySection && (
            <div className="space-y-2">
              <Label htmlFor="apiKey" className="text-sm font-medium text-gray-700">
                API Key N1Panel <span className="text-tiktok">*</span>
              </Label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Key className="text-gray-400" size={16} />
                </div>
                <Input
                  id="apiKey"
                  type={showApiKey ? "text" : "password"}
                  placeholder="Masukkan API key Anda"
                  className="pl-10 pr-12"
                  {...form.register("apiKey")}
                />
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="absolute inset-y-0 right-0 pr-3 h-full"
                  onClick={() => setShowApiKey(!showApiKey)}
                >
                  {showApiKey ? (
                    <EyeOff className="text-gray-400" size={16} />
                  ) : (
                    <Eye className="text-gray-400" size={16} />
                  )}
                </Button>
              </div>
              <div className="text-xs text-gray-500 flex items-center">
                <i className="fas fa-shield-alt mr-1"></i>
                API key Anda akan dienkripsi dan disimpan dengan aman
              </div>
            </div>
          )}

          {/* Submit Button */}
          <Button
            type="submit"
            disabled={boostMutation.isPending || !isValidUrl || isBlocked}
            className="w-full gradient-bg text-white py-3 px-6 font-medium hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
          >
            {boostMutation.isPending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Memproses...
              </>
            ) : isBlocked ? (
              <>
                <Clock className="mr-2 h-4 w-4" />
                Batas Harian Tercapai
              </>
            ) : (
              <>
                <Rocket className="mr-2 h-4 w-4" />
                Boost Views Sekarang
              </>
            )}
          </Button>

          {/* Progress Bar */}
          {boostMutation.isPending && (
            <div className="space-y-2">
              <div className="flex justify-between text-sm text-gray-600">
                <span>Progress</span>
                <span>{Math.round(progress)}%</span>
              </div>
              <Progress value={progress} className="h-2" />
            </div>
          )}
        </form>
      </CardContent>
    </Card>
    </>
  );
}
