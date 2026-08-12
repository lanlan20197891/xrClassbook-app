import AsyncStorage from '@react-native-async-storage/async-storage';
import { create } from 'zustand';
import { createFormDataFile } from '@/utils';
import { useEffect, type ReactNode } from 'react';

const EXPO_PUBLIC_BACKEND_BASE_URL = process.env.EXPO_PUBLIC_BACKEND_BASE_URL;
const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

export interface User {
  id: number;
  username: string;
  headUrl: string;
  group: string;
  userData: Record<string, any>;
}

interface AuthState {
  token: string | null;
  user: User | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  loadStoredAuth: () => Promise<void>;
  updateProfile: (userData: Record<string, any>) => Promise<void>;
  uploadPhoto: (uri: string, albumId: number, title?: string, description?: string) => Promise<boolean>;
  createAlbum: (name: string, description?: string) => Promise<boolean>;
}

export const useAuth = create<AuthState>((set, get) => ({
  token: null,
  user: null,
  isLoading: true,
  isAuthenticated: false,

  login: async (username: string, password: string) => {
    /**
     * 服务端文件：server/src/routes/auth.ts
     * 接口：POST /api/v1/auth/login
     * Body 参数：username: string, password: string
     */
    const response = await fetch(`${EXPO_PUBLIC_BACKEND_BASE_URL}/api/v1/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password }),
    });
    const data = await response.json();
    if (!data.ok) throw new Error(data.msg || '登录失败');
    const { token, user } = data.data;
    await AsyncStorage.multiSet([[TOKEN_KEY, token], [USER_KEY, JSON.stringify(user)]]);
    set({ token, user, isAuthenticated: true });
  },

  logout: async () => {
    await AsyncStorage.multiRemove([TOKEN_KEY, USER_KEY]);
    set({ token: null, user: null, isAuthenticated: false });
  },

  loadStoredAuth: async () => {
    try {
      const entries = await AsyncStorage.multiGet([TOKEN_KEY, USER_KEY]);
      const token = entries.find(([key]) => key === TOKEN_KEY)?.[1] || null;
      const userStr = entries.find(([key]) => key === USER_KEY)?.[1] || null;
      const user = userStr ? JSON.parse(userStr) : null;
      set({ token, user, isAuthenticated: !!token, isLoading: false });
    } catch {
      set({ isLoading: false });
    }
  },

  updateProfile: async (userData: Record<string, any>) => {
    const { token } = get();
    if (!token) throw new Error('未登录');
    /**
     * 服务端文件：server/src/routes/profile.ts
     * 接口：PUT /api/v1/profile
     * Body 参数：sign: string, birthday: string, gender: string, constellation: string,
     *   motto: string, hometown: string, nowLive: string, qq: string, wechat: string,
     *   email: string, phone: string, myLikeThing: string, beGoodAt: string
     */
    const ud = userData;
    const response = await fetch(`${EXPO_PUBLIC_BACKEND_BASE_URL}/api/v1/profile`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({
        sign: ud?.Public?.Sign,
        birthday: ud?.MyInfo?.Birthday,
        gender: ud?.MyInfo?.Gender,
        constellation: ud?.MyInfo?.Constellation,
        motto: ud?.MyInfo?.Motto,
        hometown: ud?.Location?.Hometown,
        nowLive: ud?.Location?.NowLive,
        qq: ud?.SocialAccount?.QQ,
        wechat: ud?.SocialAccount?.WeChat,
        email: ud?.ContactMe?.Email,
        phone: ud?.ContactMe?.Phone,
        myLikeThing: ud?.LikeAndDislike?.MyLikeThing,
        beGoodAt: ud?.LikeAndDislike?.BeGoodAt,
      }),
    });
    const data = await response.json();
    if (!data.ok) throw new Error(data.msg || '更新失败');
    const updatedUser = { ...get().user!, userData: data.data.userData };
    await AsyncStorage.setItem(USER_KEY, JSON.stringify(updatedUser));
    set({ user: updatedUser });
  },

  uploadPhoto: async (uri: string, albumId: number, title?: string, description?: string) => {
    const { token } = get();
    if (!token) throw new Error('未登录');
    /**
     * 服务端文件：server/src/routes/photos.ts
     * 接口：POST /api/v1/photos/upload
     * Body 参数：FormData with file: File, albumId: number, title: string, description: string
     */
    const formData = await createFormDataFile(uri, 'file');
    formData.append('albumId', albumId.toString());
    if (title) formData.append('title', title);
    if (description) formData.append('description', description);
    const response = await fetch(`${EXPO_PUBLIC_BACKEND_BASE_URL}/api/v1/photos/upload`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` },
      body: formData,
    });
    const data = await response.json();
    return data.ok;
  },

  createAlbum: async (name: string, description?: string) => {
    const { token } = get();
    if (!token) throw new Error('未登录');
    /**
     * 服务端文件：server/src/routes/photos.ts
     * 接口：POST /api/v1/photos/albums
     * Body 参数：name: string, description: string
     */
    const response = await fetch(`${EXPO_PUBLIC_BACKEND_BASE_URL}/api/v1/photos/albums`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      body: JSON.stringify({ name, description }),
    });
    const data = await response.json();
    return data.ok;
  },
}));

export function AuthProvider({ children }: { children: ReactNode }) {
  const loadStoredAuth = useAuth((s) => s.loadStoredAuth);

  useEffect(() => {
    loadStoredAuth();
  }, [loadStoredAuth]);

  return <>{children}</>;
}
