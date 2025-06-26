# TikTok View Booster - Next.js Version

Aplikasi Next.js untuk boost views TikTok dengan integrasi N1Panel API.

## Deployment ke Vercel

### Langkah 1: Upload ke Repository
Upload seluruh folder `vercel-upload` ke repository GitHub Anda.

### Langkah 2: Environment Variables
Set environment variable berikut di Vercel dashboard:
```
DATABASE_URL=your_postgresql_connection_string
```

### Langkah 3: Deploy
Connect repository ke Vercel dan deploy.

## Fitur
- VPN/Proxy detection dan blocking
- Rate limiting 5 boost per IP per hari
- Integrasi N1Panel API untuk TikTok views
- UI modern dengan Tailwind CSS dan shadcn/ui
- Real-time statistics
- PostgreSQL database dengan Drizzle ORM

## Struktur Project
```
app/
├── api/
│   ├── tiktok/boost/
│   └── stats/today/
├── globals.css
├── layout.tsx
└── page.tsx
components/
├── ui/
├── tiktok-form.tsx
├── results-section.tsx
├── stats-section.tsx
└── timer-display.tsx
shared/
└── schema.ts
```

## Build Fixes Applied
- Fixed import paths from @shared to @/shared
- Fixed Next.js config (serverExternalPackages)
- Updated Tailwind config for Next.js structure
- Added nextBoostAt field to ApiResponse interface
- Removed deprecated experimental.serverComponentsExternalPackages