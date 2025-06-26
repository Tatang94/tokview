import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatNumber(num: number): string {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toString();
}

export function formatDate(date: Date): string {
  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date);
}

export function validateTikTokUrl(url: string): boolean {
  try {
    const urlObj = new URL(url);
    // Support various TikTok URL formats
    return urlObj.hostname.includes('tiktok.com') || 
           urlObj.hostname.includes('vm.tiktok.com') ||
           urlObj.hostname.includes('vt.tiktok.com');
  } catch {
    return false;
  }
}
