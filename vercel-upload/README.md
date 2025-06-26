# TikTok View Booster - Next.js Version

## Mobile Super App UI Design

This is a modern TikTok view booster application with a mobile-first super app design featuring:

- **Gradient Headers**: Beautiful gradient backgrounds with mobile-optimized navigation
- **Card-based Layout**: Clean card components for stats, forms, and results
- **Responsive Design**: Optimized for mobile devices with touch-friendly interactions
- **Real-time Stats**: Live statistics with PostgreSQL database integration
- **Modern UI Components**: Built with shadcn/ui and Tailwind CSS

## Database Configuration

The application uses PostgreSQL with the following environment variables:

```bash
DATABASE_URL=postgresql://neondb_owner:npg_fDo5qpKNe9zU@ep-gentle-fire-a62gl0x7.us-west-2.aws.neon.tech/neondb?sslmode=require
PGHOST=ep-gentle-fire-a62gl0x7.us-west-2.aws.neon.tech
PGDATABASE=neondb
PGUSER=neondb_owner
PGPASSWORD=npg_fDo5qpKNe9zU
PGPORT=5432
```

## Deployment

This application is configured for deployment on Vercel with:

- Next.js 14.0.3
- PostgreSQL database integration
- Tailwind CSS for styling
- API routes for TikTok boost functionality

## Build Configuration

The build has been optimized to resolve all dependency conflicts and is ready for production deployment.

## Features

- TikTok URL processing with view boost functionality
- Daily statistics tracking
- Mobile-optimized user interface
- Real-time feedback and notifications
- PostgreSQL data persistence