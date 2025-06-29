import { users, tiktokBoosts, type User, type InsertUser, type TiktokBoost, type InsertTiktokBoost } from "../shared/schema";
import { db } from "./db";
import { eq, sql, and, gte } from "drizzle-orm";

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

// In-memory storage for fallback when database is not available
class MemoryStorage implements IStorage {
  private users: User[] = [];
  private boosts: TiktokBoost[] = [];
  private nextUserId = 1;
  private nextBoostId = 1;

  async getUser(id: number): Promise<User | undefined> {
    return this.users.find(u => u.id === id);
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    return this.users.find(u => u.username === username);
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const user: User = {
      id: this.nextUserId++,
      username: insertUser.username,
      password: insertUser.password,
      createdAt: new Date()
    };
    this.users.push(user);
    return user;
  }

  async createTiktokBoost(insertBoost: InsertTiktokBoost): Promise<TiktokBoost> {
    const boost: TiktokBoost = {
      id: this.nextBoostId++,
      url: insertBoost.url,
      ipAddress: insertBoost.ipAddress,
      status: 'pending',
      viewsAdded: 0,
      processingTime: null,
      serviceType: insertBoost.serviceType || 'views',
      createdAt: new Date()
    };
    this.boosts.push(boost);
    return boost;
  }

  async updateTiktokBoost(id: number, updates: Partial<TiktokBoost>): Promise<TiktokBoost | undefined> {
    const index = this.boosts.findIndex(b => b.id === id);
    if (index === -1) return undefined;
    
    this.boosts[index] = { ...this.boosts[index], ...updates };
    return this.boosts[index];
  }

  async getTodayBoosts(url?: string): Promise<TiktokBoost[]> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return this.boosts.filter(boost => {
      const boostDate = boost.createdAt ? new Date(boost.createdAt) : new Date(0);
      const isToday = boostDate >= today;
      return url ? (isToday && boost.url === url) : isToday;
    });
  }

  async canBoost(ipAddress: string): Promise<{ canBoost: boolean; reason?: string; boostsToday: number }> {
    const todayBoosts = await this.getTodayBoostsByIP(ipAddress);
    const count = todayBoosts.length;
    const limit = 5; // 5 boosts per IP per day

    if (count >= limit) {
      return {
        canBoost: false,
        reason: `Daily limit reached. You can boost again tomorrow.`,
        boostsToday: count
      };
    }

    return {
      canBoost: true,
      boostsToday: count
    };
  }

  async getTodayBoostsByIP(ipAddress: string): Promise<TiktokBoost[]> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return this.boosts.filter(boost => {
      const boostDate = boost.createdAt ? new Date(boost.createdAt) : new Date(0);
      return boost.ipAddress === ipAddress && boostDate >= today;
    });
  }

  async getTodayStats(): Promise<{
    videosToday: number;
    totalViews: number;
    successRate: number;
    avgTime: string;
  }> {
    const todayBoosts = await this.getTodayBoosts();
    
    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter(boost => boost.status === 'completed').length;
    const successRate = videosToday > 0 ? (successfulBoosts / videosToday) * 100 : 0;

    // Calculate average processing time
    const completedBoosts = todayBoosts.filter(boost => boost.processingTime);
    const avgTimeMs = completedBoosts.length > 0 
      ? completedBoosts.reduce((sum, boost) => {
          const timeStr = boost.processingTime || '0';
          const timeMs = parseFloat(timeStr.replace('s', '')) * 1000;
          return sum + timeMs;
        }, 0) / completedBoosts.length
      : 0;
    
    const avgTime = avgTimeMs > 0 ? `${(avgTimeMs / 1000).toFixed(1)}s` : '0s';

    return {
      videosToday,
      totalViews,
      successRate: Math.round(successRate * 10) / 10,
      avgTime
    };
  }
}

export class DatabaseStorage implements IStorage {
  async getUser(id: number): Promise<User | undefined> {
    if (!db) throw new Error('Database not available');
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    if (!db) throw new Error('Database not available');
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user || undefined;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    if (!db) throw new Error('Database not available');
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  async createTiktokBoost(insertBoost: InsertTiktokBoost): Promise<TiktokBoost> {
    if (!db) throw new Error('Database not available');
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
    if (!db) throw new Error('Database not available');
    const [boost] = await db
      .update(tiktokBoosts)
      .set(updates)
      .where(eq(tiktokBoosts.id, id))
      .returning();
    return boost || undefined;
  }

  async getTodayBoosts(url?: string): Promise<TiktokBoost[]> {
    if (!db) throw new Error('Database not available');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (url) {
      return await db
        .select()
        .from(tiktokBoosts)
        .where(and(
          gte(tiktokBoosts.createdAt, today),
          eq(tiktokBoosts.url, url)
        ));
    }

    return await db
      .select()
      .from(tiktokBoosts)
      .where(gte(tiktokBoosts.createdAt, today));
  }

  async canBoost(ipAddress: string): Promise<{ canBoost: boolean; reason?: string; boostsToday: number }> {
    if (!db) throw new Error('Database not available');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const boostsToday = await db
      .select()
      .from(tiktokBoosts)
      .where(and(
        eq(tiktokBoosts.ipAddress, ipAddress),
        gte(tiktokBoosts.createdAt, today)
      ));

    const count = boostsToday.length;
    const limit = 5; // 5 boosts per IP per day

    if (count >= limit) {
      return {
        canBoost: false,
        reason: `Daily limit reached. You can boost again tomorrow.`,
        boostsToday: count
      };
    }

    return {
      canBoost: true,
      boostsToday: count
    };
  }

  async getTodayBoostsByIP(ipAddress: string): Promise<TiktokBoost[]> {
    if (!db) throw new Error('Database not available');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return await db
      .select()
      .from(tiktokBoosts)
      .where(and(
        eq(tiktokBoosts.ipAddress, ipAddress),
        gte(tiktokBoosts.createdAt, today)
      ));
  }

  async getTodayStats(): Promise<{
    videosToday: number;
    totalViews: number;
    successRate: number;
    avgTime: string;
  }> {
    if (!db) throw new Error('Database not available');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const todayBoosts = await db
      .select()
      .from(tiktokBoosts)
      .where(gte(tiktokBoosts.createdAt, today));

    const videosToday = todayBoosts.length;
    const totalViews = todayBoosts.reduce((sum, boost) => sum + (boost.viewsAdded || 0), 0);
    const successfulBoosts = todayBoosts.filter(boost => boost.status === 'completed').length;
    const successRate = videosToday > 0 ? (successfulBoosts / videosToday) * 100 : 0;

    // Calculate average processing time
    const completedBoosts = todayBoosts.filter(boost => boost.processingTime);
    const avgTimeMs = completedBoosts.length > 0 
      ? completedBoosts.reduce((sum, boost) => {
          const timeStr = boost.processingTime || '0';
          const timeMs = parseFloat(timeStr.replace('s', '')) * 1000;
          return sum + timeMs;
        }, 0) / completedBoosts.length
      : 0;
    
    const avgTime = avgTimeMs > 0 ? `${(avgTimeMs / 1000).toFixed(1)}s` : '0s';

    return {
      videosToday,
      totalViews,
      successRate: Math.round(successRate * 10) / 10,
      avgTime
    };
  }
}

// Use in-memory storage as fallback when database is not available
export const storage = db ? new DatabaseStorage() : new MemoryStorage();
console.log(`Using ${db ? 'database' : 'in-memory'} storage`);