import { neon } from '@neondatabase/serverless';
import { drizzle } from 'drizzle-orm/neon-http';
import * as schema from "../shared/schema";

// Check for database URL in environment or from Replit secrets
const databaseUrl = process.env.DATABASE_URL || 
  process.env.PGPORT ? `postgresql://${process.env.PGUSER}:${process.env.PGPASSWORD}@${process.env.PGHOST}:${process.env.PGPORT}/${process.env.PGDATABASE}` : null;

if (!databaseUrl) {
  console.warn("No DATABASE_URL found. You may need to provision a PostgreSQL database.");
  console.warn("Available database environment variables:", {
    DATABASE_URL: process.env.DATABASE_URL,
    PGHOST: process.env.PGHOST,
    PGPORT: process.env.PGPORT,
    PGUSER: process.env.PGUSER,
    PGDATABASE: process.env.PGDATABASE
  });
}

// Use HTTP mode instead of WebSocket for better reliability
const sql = databaseUrl ? neon(databaseUrl) : null;
export const db = sql ? drizzle(sql, { schema }) : null;

// Test database connection function
export async function testConnection() {
  if (!sql) {
    console.warn('Database connection not available');
    return false;
  }
  try {
    await sql`SELECT 1`;
    console.log('Database connection successful');
    return true;
  } catch (error) {
    console.error('Database connection failed:', error);
    return false;
  }
}