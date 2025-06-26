import { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Progress } from "@/components/ui/progress";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { tiktokBoostSchema, type TiktokBoostRequest, type ApiResponse } from "@/shared/schema";
import { validateTikTokUrl } from "@/lib/utils";
import { CheckCircle, XCircle, Eye, EyeOff, Key, Link as LinkIcon, Rocket, Loader2 } from "lucide-react";
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
        toast({
          title: "Gagal!",
          description: response.message || "Terjadi kesalahan",
          variant: "destructive",
        });
      }
      setProgress(0);
    },
    onError: (error: any) => {
      toast({
        title: "Error!",
        description: error.message || "Terjadi kesalahan tidak terduga",
        variant: "destructive",
      });
      setProgress(0);
    },
  });

  const onSubmit = (data: TiktokBoostRequest) => {
    setProgress(0);
    
    const progressInterval = setInterval(() => {
      setProgress(prev => {
        if (prev >= 90) {
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
    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      {/* Header */}
      <div className="bg-gradient-to-r from-purple-500 to-pink-500 px-6 py-4">
        <div className="flex items-center">
          <div className="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
            <Rocket className="text-white" size={20} />
          </div>
          <div>
            <h2 className="text-white font-bold text-lg">Boost Video</h2>
            <p className="text-white/80 text-sm">Tingkatkan views dengan mudah</p>
          </div>
        </div>
      </div>

      <div className="p-6">
        {/* Boost Stats Display */}
        {(boostStats.boostsToday !== undefined) && (
          <div className={`mb-6 p-4 rounded-xl ${boostStats.boostsRemaining === 0 ? 'bg-red-50 border border-red-200' : 'bg-blue-50 border border-blue-200'}`}>
            <div className="text-center">
              <div className="flex items-center justify-center space-x-2 mb-3">
                <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${boostStats.boostsRemaining === 0 ? 'bg-red-100' : 'bg-blue-100'}`}>
                  <svg className={`w-4 h-4 ${boostStats.boostsRemaining === 0 ? 'text-red-600' : 'text-blue-600'}`} fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                    <path fillRule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clipRule="evenodd"/>
                  </svg>
                </div>
                <h3 className={`font-bold ${boostStats.boostsRemaining === 0 ? 'text-red-800' : 'text-blue-800'}`}>
                  {boostStats.boostsRemaining === 0 ? 'Batas Harian Tercapai' : 'Status Boost Hari Ini'}
                </h3>
              </div>
              
              {boostStats.boostsRemaining === 0 ? (
                <div className="bg-red-100 rounded-lg p-4">
                  <p className="text-red-800 font-semibold mb-2">Batas harian Anda sudah habis!</p>
                  <p className="text-red-600 text-sm mb-2">Kembali lagi besok untuk melakukan boost lagi.</p>
                  <p className="text-red-500 text-xs">Reset otomatis setiap hari pada pukul 00:00 WIB</p>
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-4">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-blue-900">
                      {boostStats.boostsToday}/5
                    </div>
                    <p className="text-blue-600 text-sm">Boost Digunakan</p>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-green-900">
                      {boostStats.boostsRemaining}
                    </div>
                    <p className="text-green-600 text-sm">Boost Tersisa</p>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}

        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          {/* URL Input */}
          <div className="space-y-3">
            <Label htmlFor="url" className="text-sm font-semibold text-gray-900 flex items-center">
              <LinkIcon className="w-4 h-4 mr-2 text-purple-500" />
              Link Video TikTok
            </Label>
            <div className="relative">
              <Input
                id="url"
                type="url"
                placeholder="https://vt.tiktok.com/ZSB1XfVrN/"
                className={`h-12 pl-4 pr-12 text-base rounded-xl border-2 transition-all duration-200 ${
                  url && isValidUrl 
                    ? "border-green-300 bg-green-50 focus:border-green-500" 
                    : url && !isValidUrl 
                    ? "border-red-300 bg-red-50 focus:border-red-500"
                    : "border-gray-200 focus:border-purple-500"
                }`}
                {...form.register("url")}
              />
              {url && (
                <div className="absolute inset-y-0 right-0 pr-4 flex items-center">
                  {isValidUrl ? (
                    <CheckCircle className="text-green-500" size={20} />
                  ) : (
                    <XCircle className="text-red-500" size={20} />
                  )}
                </div>
              )}
            </div>
            {form.formState.errors.url && (
              <div className="text-red-500 text-sm flex items-center bg-red-50 p-3 rounded-lg">
                <XCircle className="w-4 h-4 mr-2" />
                {form.formState.errors.url.message}
              </div>
            )}
            <div className="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg">
              <div className="flex items-center">
                <svg className="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/>
                </svg>
                Contoh: https://vt.tiktok.com/ZSB1XfVrN/
              </div>
            </div>
          </div>

          {/* API Key Section Toggle */}
          <div className="border-t border-gray-100 pt-4">
            <button
              type="button"
              onClick={() => setShowApiKeySection(!showApiKeySection)}
              className="flex items-center justify-between w-full p-3 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
            >
              <div className="flex items-center">
                <Key className="mr-3 text-purple-500" size={16} />
                API Key Pribadi (Opsional)
              </div>
              <svg className={`w-4 h-4 transform transition-transform ${showApiKeySection ? 'rotate-180' : ''}`} fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd"/>
              </svg>
            </button>
            
            {showApiKeySection && (
              <div className="mt-3 space-y-3">
                <div className="relative">
                  <Input
                    type={showApiKey ? "text" : "password"}
                    placeholder="Masukkan API key pribadi"
                    className="h-12 pr-12 rounded-xl border-2 border-gray-200 focus:border-purple-500"
                    {...form.register("apiKey")}
                  />
                  <button
                    type="button"
                    onClick={() => setShowApiKey(!showApiKey)}
                    className="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600"
                  >
                    {showApiKey ? <EyeOff size={18} /> : <Eye size={18} />}
                  </button>
                </div>
                <div className="text-xs text-gray-500 bg-blue-50 p-3 rounded-lg">
                  <svg className="w-4 h-4 inline mr-2 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/>
                  </svg>
                  Kosongkan untuk menggunakan sistem default
                </div>
              </div>
            )}
          </div>

          {/* Progress Bar */}
          {boostMutation.isPending && (
            <div className="space-y-3 bg-purple-50 p-4 rounded-xl">
              <div className="flex justify-between items-center text-sm">
                <span className="text-purple-700 font-medium">Sedang memproses boost...</span>
                <span className="text-purple-600">{progress}%</span>
              </div>
              <Progress value={progress} className="h-3" />
            </div>
          )}

          {/* Submit Button */}
          <Button
            type="submit"
            disabled={boostMutation.isPending || !isValidUrl || isBlocked}
            className="w-full h-14 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white font-bold text-lg rounded-xl transition-all duration-200 transform hover:scale-[1.02] disabled:hover:scale-100 disabled:opacity-50 shadow-lg"
          >
            {boostMutation.isPending ? (
              <>
                <Loader2 className="mr-3 h-5 w-5 animate-spin" />
                Memproses...
              </>
            ) : isBlocked ? (
              <>
                Batas Harian Tercapai
              </>
            ) : (
              <>
                <Rocket className="mr-3 h-5 w-5" />
                Boost Sekarang
              </>
            )}
          </Button>

          {/* Info Card */}
          <div className="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-4">
            <div className="flex items-start">
              <div className="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg className="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd"/>
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-bold text-blue-900 mb-2">Aturan Penggunaan</h3>
                <div className="text-sm text-blue-800 space-y-1">
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2"></div>
                    Maksimal 5 boost per IP per hari
                  </div>
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2"></div>
                    Proses 1-3 menit
                  </div>
                  <div className="flex items-center">
                    <div className="w-1.5 h-1.5 bg-blue-400 rounded-full mr-2"></div>
                    Views bertambah bertahap
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}