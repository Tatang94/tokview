# TikTok View Booster - Next.js Version

This is the Next.js/Vercel deployment version of the TikTok View Booster application.

## Current Status: Full Production Ready

✅ **Fully Functional**: This version now includes complete N1Panel API integration for real TikTok view boosting.

### Features:
- Real TikTok view boosting via N1Panel API (Service ID 838)
- Complete database integration with PostgreSQL
- VPN/Proxy detection and blocking system
- IP-based rate limiting (5 boosts per day per IP address)
- Secure environment variable configuration

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

### 2. API Integration (Required)
For real TikTok view boosting, add your N1Panel API key:
```
N1PANEL_API_KEY=your_n1panel_api_key
```

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
- **Production (Next.js)**: Full API integration with secure environment variables
- Both versions now functionally identical with complete N1Panel integration