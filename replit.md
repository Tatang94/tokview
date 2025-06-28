# TikTok View Booster Application

## Overview

This is a full-stack web application that provides TikTok view boosting services. The application allows users to submit TikTok video URLs and boost their view counts through integration with the N1Panel API service. It features a modern React frontend with TypeScript, Express.js backend, and PostgreSQL database with Drizzle ORM for data persistence.

## System Architecture

The application follows a monorepo structure with clear separation between client, server, and shared components:

- **Frontend**: React with TypeScript, using Vite for development and build
- **Backend**: Express.js server with TypeScript
- **Database**: PostgreSQL with Drizzle ORM for schema management
- **Styling**: Tailwind CSS with shadcn/ui component library
- **State Management**: TanStack Query for server state management
- **Form Handling**: React Hook Form with Zod validation
- **Deployment**: Replit with autoscale deployment target

## Key Components

### Frontend Architecture
- **React Router**: Using Wouter for lightweight client-side routing
- **Component Library**: shadcn/ui components built on Radix UI primitives
- **Form Management**: React Hook Form with Zod schema validation
- **HTTP Client**: Custom fetch-based API client with React Query integration
- **Styling System**: Tailwind CSS with CSS custom properties for theming

### Backend Architecture
- **Express Server**: RESTful API with middleware for logging and error handling
- **Database Layer**: Drizzle ORM with PostgreSQL for data persistence
- **Storage Abstraction**: Interface-based storage pattern with in-memory fallback
- **External API Integration**: N1Panel API for TikTok view boosting services
- **Environment Configuration**: Environment variables for API keys and database connection

### Database Schema
- **Users Table**: Basic user management with username/password
- **TikTok Boosts Table**: Tracks boost requests with status, view counts, and processing metrics
- **Schema Validation**: Zod schemas for runtime type checking and validation

## Data Flow

1. **User Input**: User submits TikTok URL through the frontend form
2. **Validation**: Client-side validation using Zod schema, server-side re-validation
3. **Database Record**: Create boost record in pending status
4. **External API Call**: Submit request to N1Panel API for view boosting
5. **Status Update**: Update database record with results (success/failure, view count, processing time)
6. **UI Update**: Display results to user with statistics and action buttons
7. **Analytics**: Track daily statistics including success rates and processing times

## External Dependencies

### Core Dependencies
- **@neondatabase/serverless**: PostgreSQL database driver for serverless environments
- **drizzle-orm**: Type-safe ORM for database operations
- **@tanstack/react-query**: Server state management and caching
- **@hookform/resolvers**: Form validation integration
- **zod**: Runtime type validation and schema definition

### UI Dependencies
- **@radix-ui/***: Unstyled, accessible UI primitives
- **tailwindcss**: Utility-first CSS framework
- **class-variance-authority**: Type-safe component variants
- **lucide-react**: Icon library

### Development Dependencies
- **vite**: Fast development server and build tool
- **typescript**: Type checking and development experience
- **tsx**: TypeScript execution for development

## Deployment Strategy

The application is configured for deployment on Replit with the following setup:

- **Build Process**: Vite builds the client-side assets, esbuild bundles the server
- **Development**: Uses tsx for hot-reloading TypeScript server code
- **Production**: Node.js serves the bundled application
- **Database**: PostgreSQL provisioned through Replit's database service
- **Environment**: Automatic environment variable injection for database connection
- **Port Configuration**: Server runs on port 5000, exposed on port 80

### Build Commands
- `npm run dev`: Development mode with hot reloading
- `npm run build`: Production build (client + server bundling)
- `npm run start`: Production server startup
- `npm run db:push`: Database schema deployment

## User Preferences

Preferred communication style: Simple, everyday language.

## Changelog

Changelog:
- June 26, 2025. Initial setup
- Added PostgreSQL database integration with Drizzle ORM
- ~~Implemented 8-hour cooldown timer system between boosts~~ (Removed)
- ~~Added 3 boosts per day limit per video~~ (Changed to 5 per IP)
- Integrated N1Panel SMM service with provided API key for TikTok views
- ~~Added real-time countdown timer display with visual status indicators~~ (Removed)
- Price: $0.001 per 1000 views with automatic rate limiting
- Migration from Express.js to Next.js started for Vercel deployment compatibility
- Created Next.js app structure with API routes and React components
- Both Express.js (port 5000) and Next.js (port 3000) versions available for testing
- **Updated System (June 26, 2025):**
  - Changed to 5 boosts per IP address per day (instead of 3 per video)
  - Removed 8-hour cooldown timer completely
  - Added VPN/Proxy detection and blocking system
  - IP-based tracking with real-time API validation
  - Clear daily limit notifications with "come back tomorrow" messaging
- **Migration Complete (June 26, 2025):**
  - Successfully migrated from Replit Agent to standard Replit environment
  - PostgreSQL database created and configured with proper environment variables
  - Database schema deployed with all required tables
  - All dependencies installed and working properly
  - Express.js server running on port 5000 with full functionality
  - Vercel-upload folder synchronized with current working version
  - Both development environments (Express and Next.js) ready for deployment
- **Next.js Build Fixes (June 26, 2025):**
  - Fixed all import path errors (@shared to @/shared, component paths)
  - Updated Next.js config to use serverExternalPackages instead of experimental option
  - Fixed Tailwind config for Next.js structure (removed client/src paths)
  - Added missing nextBoostAt field to ApiResponse interface
  - Corrected Toaster component import path
  - Next.js version now ready for Vercel deployment without build errors
- **Service Optimization (June 26, 2025):**
  - Changed TikTok service from auto-search to service ID 838 for faster processing
  - Updated both Express.js and Next.js versions to use the optimized service
  - Removed service discovery API call to reduce latency
- **UI Synchronization (June 26, 2025):**
  - Synchronized all components between Express.js and Next.js versions
  - Fixed import paths and component structure for Next.js compatibility
  - Added missing Toast components for consistent notifications
  - Ensured identical user experience across both deployment environments
- **Migration Complete (June 28, 2025):**
  - Successfully migrated from Replit Agent to standard Replit environment
  - PostgreSQL database created and configured with proper environment variables
  - Database schema deployed with all required tables
  - All dependencies installed and working properly
  - Express.js server running on port 5000 with full functionality
  - Updated TikTok URL examples from long format to short format (vt.tiktok.com)
  - Both development environments ready for production use
- **Migration to Replit Environment Complete (June 28, 2025):**
  - Successfully migrated from Replit Agent to standard Replit environment
  - PostgreSQL database created and configured with proper environment variables
  - Database schema deployed with all required tables
  - All dependencies installed and working properly
  - Express.js server running on port 5000 with full functionality
  - Cleaned up unused Vercel deployment files (app/, vercel-upload/, next.config.js)
  - Project now runs cleanly in Replit environment with security best practices
- **API Integration and Balance Handling (June 28, 2025):**
  - Added N1PANEL_API_KEY environment variable to both Express.js and PHP versions
  - Implemented intelligent fallback to demo mode when API key is invalid or insufficient balance
  - Added clear messaging about minimum balance requirements ($0.01 for TikTok views)
  - Both applications now gracefully handle API errors and provide meaningful feedback
  - Demo mode allows full UI testing while indicating real boost requirements
- **API Migration to Lollipop SMM (June 28, 2025):**
  - Migrated from N1Panel API to Lollipop SMM API (lollipop-smm.com/api/v2)
  - Updated API key to Lollipop SMM: 99417915b8b348b025ee348e678b7788
  - Changed service ID from 838 to 746 (TikTok Views | Max Unlimited)
  - Significantly improved pricing: 22.00 per 1000 views vs previous rates
  - Successfully tested API integration with real order creation
  - Both Express.js and PHP versions updated with new API configuration
- **PHP Version Created (June 28, 2025):**
  - Created complete PHP version for shared hosting deployment
  - Single-file application (index_hosting.php) with all functionality
  - MySQL database support with auto-table creation
  - Real N1Panel API integration with fallback demo mode
  - Hosting-compatible configuration (config_hosting.php)
  - Debug utilities (debug_test.php) for troubleshooting
  - Fixed database insert/update issues and statistics tracking
  - Support for table prefix and shared hosting limitations
- **Mobile Super App UI Design (June 26, 2025):**
  - Completely redesigned Next.js version with mobile-first super app interface
  - Modern gradient headers with card-based layout design
  - Responsive mobile navigation with sticky header and bottom navigation
  - Enhanced visual feedback with color-coded status indicators
  - Improved user experience with intuitive icons and micro-interactions
  - Consistent design language across all components (TikTok form, stats, results)
  - Optimized for mobile usage with touch-friendly buttons and spacing
- **Next.js Build Configuration Fixed (June 26, 2025):**
  - Fixed PostCSS configuration to use CommonJS module exports
  - Updated Next.js config to use serverExternalPackages instead of experimental option
  - Resolved TypeScript errors in API routes with proper Drizzle ORM usage
  - Created proper environment variables configuration (.env.local)
  - Database URL and credentials properly configured for Vercel deployment
  - API routes fully functional with PostgreSQL integration
  - Removed problematic @tailwindcss/typography dependency causing build errors
  - Cleaned Tailwind config to use minimal plugins without animations
  - Added Vercel configuration file with environment variables
  - Build system now clean and ready for production deployment