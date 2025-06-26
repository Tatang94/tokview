import { users, tiktokBoosts, type User, type InsertUser, type TiktokBoost, type InsertTiktokBoost } from "../shared/schema";
import { db } from "./db";
import { eq } from "drizzle-orm";

export interface IStorage {
  getUser(id: number): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  createTiktokBoost(boost: InsertTiktokBoost): Promise<TiktokBoost>;
  updateTiktokBoost(id: number, updates: Partial<TiktokBoost>): Promise<TiktokBoost | undefined>;
  getTodayStats(): Promise<{
    videosToday: number;
    totalViews: number;
    successRate: number;
    avgTime: string;
  }>;
  getTodayBoosts(url?: string): Promise<TiktokBoost[]>;
  getTodayBoostsByIP(ipAddress: string): Promise<TiktokBoost[]>;
  canBoost(ipAddress: string): Promise<{ canBoost: boolean; reason?: string; boostsToday: number }>;
}

export class DatabaseStorage implements IStorage {
  async getUser(id: number): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user || undefined;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  async createTiktokBoost(insertBoost: InsertTiktokBoost): Promise<TiktokBoost> {
    const [boost] = await db
      .insert(tiktokBoosts)
      .values({
        ...insertBoost,
        status: 'pending',
        viewsAdded: 0,
        processingTime: null,
        createdAt: new Date(),
      })
      .returning();
    return boost;
  }

  async updateTiktokBoost(id: number, updates: Partial<TiktokBoost>): Promise<TiktokBoost | undefined> {
    const [boost] = await db
      .update(tiktokBoosts)
      .set(updates)
      .where(eq(tiktokBoosts.id, id))
      .returning();
    return boost || undefined;
  }

  async getTodayBoosts(url?: string): Promise<TiktokBoost[]> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    let query = db.select().from(tiktokBoosts);
    
    if (url) {
      const results = await query.where(eq(tiktokBoosts.url, url));
      return results.filter(boost => boost.createdAt && boost.createdAt >= today);
    } else {
      const results = await query;
      return results.filter(boost => boost.createdAt && boost.createdAt >= today);
    }
  }

  async canBoost(ipAddress: string): Promise<{ canBoost: boolean; reason?: string; boostsToday: number }> {
    const todayBoosts = await this.getTodayBoostsByIP(ipAddress);
    const boostsToday = todayBoosts.length;
    
    // Check if IP has reached daily limit (5 boosts per day)
    if (boostsToday >= 5) {
      return {
        canBoost: false,
        reason: "Anda sudah mencapai batas 5 boost per hari",
        boostsToday
      };
    }

    return {
      canBoost: true,
      boostsToday
    };
  }

  async getTodayBoostsByIP(ipAddress: string): Promise<TiktokBoost[]> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const query = db.select().from(tiktokBoosts);
    const results = await query.where(eq(tiktokBoosts.ipAddress, ipAddress));
    return results.filter(boost => boost.createdAt && boost.createdAt >= today);
  }

  async getTodayStats(): Promise<{
    videosToday: number;
    totalViews: number;
    successRate: number;
    avgTime: string;
  }> {
    const todayBoosts = await this.getTodayBoosts();
    const completed = todayBoosts.filter(boost => boost.status === 'completed');
    const totalViews = completed.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successRate = todayBoosts.length > 0 ? (completed.length / todayBoosts.length) * 100 : 0;

    return {
      videosToday: todayBoosts.length,
      totalViews,
      successRate: Math.round(successRate * 10) / 10,
      avgTime: "1.2s"
    };
  }
}

export const storage = new DatabaseStorage();
