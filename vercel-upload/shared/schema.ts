import { pgTable, text, serial, integer, boolean, timestamp } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

export const users = pgTable("users", {
  id: serial("id").primaryKey(),
  username: text("username").notNull().unique(),
  password: text("password").notNull(),
});

export const tiktokBoosts = pgTable("tiktok_boosts", {
  id: serial("id").primaryKey(),
  url: text("url").notNull(),
  ipAddress: text("ip_address").notNull(),
  status: text("status").notNull(), // 'pending', 'completed', 'failed'
  viewsAdded: integer("views_added").default(0),
  processingTime: text("processing_time"),
  orderId: text("order_id"),
  errorMessage: text("error_message"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertUserSchema = createInsertSchema(users).pick({
  username: true,
  password: true,
});

export const tiktokBoostSchema = z.object({
  url: z.string().url().refine((url) => {
    try {
      const urlObj = new URL(url);
      return urlObj.hostname.includes('tiktok.com') || 
             urlObj.hostname.includes('vm.tiktok.com') ||
             urlObj.hostname.includes('vt.tiktok.com');
    } catch {
      return false;
    }
  }, {
    message: "URL harus berupa link video TikTok yang valid"
  }),
  apiKey: z.string().optional(),
});

export const insertTiktokBoostSchema = createInsertSchema(tiktokBoosts).pick({
  url: true,
  ipAddress: true,
});

export type InsertUser = z.infer<typeof insertUserSchema>;
export type User = typeof users.$inferSelect;
export type TiktokBoost = typeof tiktokBoosts.$inferSelect;
export type InsertTiktokBoost = z.infer<typeof insertTiktokBoostSchema>;
export type TiktokBoostRequest = z.infer<typeof tiktokBoostSchema>;

export interface ApiResponse {
  success: boolean;
  message: string;
  data?: {
    viewsAdded: number;
    status: string;
    processingTime: string;
    videoTitle?: string;
    orderId?: string;
    boostsToday?: number;
    boostsRemaining?: number;
  };
  error?: string;
}

export interface StatsResponse {
  videosToday: number;
  totalViews: number;
  successRate: number;
  avgTime: string;
}
