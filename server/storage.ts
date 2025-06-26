import { users, tiktokBoosts, type User, type InsertUser, type TiktokBoost, type InsertTiktokBoost } from "@shared/schema";
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
  canBoost(url: string): Promise<{ canBoost: boolean; reason?: string; nextBoostAt?: Date; boostsToday: number }>;
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
        nextBoostAt: null,
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

  async canBoost(url: string): Promise<{ canBoost: boolean; reason?: string; nextBoostAt?: Date; boostsToday: number }> {
    const todayBoosts = await this.getTodayBoosts(url);
    const boostsToday = todayBoosts.length;
    
    // Check if user has reached daily limit (3 boosts per day)
    if (boostsToday >= 3) {
      return {
        canBoost: false,
        reason: "Anda sudah mencapai batas 3 boost per hari untuk video ini",
        boostsToday
      };
    }

    // Check if user needs to wait 8 hours since last boost
    const lastBoost = todayBoosts
      .filter(boost => boost.status === 'completed')
      .sort((a, b) => (b.createdAt?.getTime() || 0) - (a.createdAt?.getTime() || 0))[0];

    if (lastBoost && lastBoost.createdAt) {
      const nextBoostTime = new Date(lastBoost.createdAt.getTime() + 8 * 60 * 60 * 1000); // 8 hours
      const now = new Date();
      
      if (now < nextBoostTime) {
        return {
          canBoost: false,
          reason: "Anda harus menunggu 8 jam sejak boost terakhir",
          nextBoostAt: nextBoostTime,
          boostsToday
        };
      }
    }

    return {
      canBoost: true,
      boostsToday
    };
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
