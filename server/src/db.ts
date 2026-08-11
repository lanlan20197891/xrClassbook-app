import { getSupabaseClient } from './storage/database/supabase-client.js';

// Service-role client for server-side operations (bypasses RLS)
export const db = getSupabaseClient();
