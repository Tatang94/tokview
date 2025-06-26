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