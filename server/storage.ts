import { users, tiktokBoosts, type User, type InsertUser, type TiktokBoost, type InsertTiktokBoost } from "@shared/schema";

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

export class MemStorage implements IStorage {
  private users: Map<number, User>;
  private tiktokBoosts: Map<number, TiktokBoost>;
  private currentUserId: number;
  private currentBoostId: number;

  constructor() {
    this.users = new Map();
    this.tiktokBoosts = new Map();
    this.currentUserId = 1;
    this.currentBoostId = 1;
  }

  async getUser(id: number): Promise<User | undefined> {
    return this.users.get(id);
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    return Array.from(this.users.values()).find(
      (user) => user.username === username,
    );
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const id = this.currentUserId++;
    const user: User = { ...insertUser, id };
    this.users.set(id, user);
    return user;
  }

  async createTiktokBoost(insertBoost: InsertTiktokBoost): Promise<TiktokBoost> {
    const id = this.currentBoostId++;
    const boost: TiktokBoost = {
      ...insertBoost,
      id,
      status: 'pending',
      viewsAdded: 0,
      processingTime: null,
      createdAt: new Date(),
      nextBoostAt: null,
    };
    this.tiktokBoosts.set(id, boost);
    return boost;
  }

  async getTodayBoosts(url?: string): Promise<TiktokBoost[]> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    return Array.from(this.tiktokBoosts.values()).filter(boost => {
      const boostDate = boost.createdAt && boost.createdAt >= today;
      const urlMatch = url ? boost.url === url : true;
      return boostDate && urlMatch;
    });
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

  async updateTiktokBoost(id: number, updates: Partial<TiktokBoost>): Promise<TiktokBoost | undefined> {
    const boost = this.tiktokBoosts.get(id);
    if (!boost) return undefined;
    
    const updatedBoost = { ...boost, ...updates };
    this.tiktokBoosts.set(id, updatedBoost);
    return updatedBoost;
  }

  async getTodayStats(): Promise<{
    videosToday: number;
    totalViews: number;
    successRate: number;
    avgTime: string;
  }> {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const todayBoosts = Array.from(this.tiktokBoosts.values()).filter(
      boost => boost.createdAt && boost.createdAt >= today
    );

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

export const storage = new MemStorage();
