# TikTok View Booster - Next.js Version

This is the Next.js/Vercel deployment version of the TikTok View Booster application.

## Current Status: Demo Mode

⚠️ **Important**: This version is currently configured in **demo mode** for security reasons.

### What Demo Mode Means:
- Simulates view boosting without calling external APIs
- Generates random view counts (1000-6000) for demonstration
- All other features work normally (database, rate limiting, VPN detection)
- No real views are added to TikTok videos

## Production Setup

To enable real view boosting in production:

### 1. Database Configuration
Update these environment variables in your Vercel dashboard:
```
DATABASE_URL=your_actual_database_url
PGHOST=your_pg_host
PGDATABASE=your_pg_database
PGUSER=your_pg_user
PGPASSWORD=your_pg_password
PGPORT=5432
```

### 2. API Integration (Optional)
If you want to enable real view boosting, add:
```
N1PANEL_API_KEY=your_api_key
```

Then update `/app/api/tiktok/boost/route.ts` to use the real API instead of demo mode.

## Security Features

✅ **Implemented Security Measures:**
- No hardcoded API keys or credentials
- Environment variables for all sensitive data
- VPN/Proxy detection and blocking
- IP-based rate limiting (5 boosts per day)
- Input validation and sanitization

## Deployment

1. Fork this repository
2. Connect to Vercel
3. Set environment variables in Vercel dashboard
4. Deploy

## Features

- Modern mobile-first UI design
- Real-time statistics tracking
- PostgreSQL database integration
- Rate limiting and security controls
- Responsive design for all devices

## Development

```bash
npm install
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) to view in development.

## Production vs Development

- **Development (Express.js)**: Full API integration on port 5000
- **Production (Next.js)**: Demo mode by default for security
- Both versions share the same database and core functionality