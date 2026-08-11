import { Router } from 'express';
import type { Request, Response } from 'express';
import { db } from '../db.js';
import { RELATION_CATEGORIES } from '../constants.js';

const router = Router();

/**
 * GET /api/v1/relations/moon
 * Headers: Authorization: Bearer <token>
 */
router.get('/moon', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const { data: relations, error } = await db
      .from('moon_relations')
      .select('id, target_id, category, pos_x, pos_y, custom_name, custom_note')
      .eq('user_id', userId);
    if (error) throw error;

    const result = (relations || []).map((r: any) => ({
      id: r.id,
      targetId: r.target_id,
      category: r.category,
      posX: r.pos_x,
      posY: r.pos_y,
      customName: r.custom_name,
      customNote: r.custom_note,
    }));

    res.json({ ok: true, data: result });
  } catch (err: any) {
    console.error('Moon relations error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * GET /api/v1/relations/categories
 */
router.get('/categories', async (_req: Request, res: Response) => {
  res.json({ ok: true, data: RELATION_CATEGORIES });
});

/**
 * GET /api/v1/relations/by-category
 * Headers: Authorization: Bearer <token>
 */
router.get('/by-category', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const { data: relations, error } = await db
      .from('moon_relations')
      .select('id, target_id, category, pos_x, pos_y, custom_name, custom_note')
      .eq('user_id', userId);
    if (error) throw error;

    const grouped: Record<string, any[]> = {};
    for (const cat of RELATION_CATEGORIES) {
      grouped[cat.key] = [];
    }
    grouped['uncategorized'] = [];

    const categorizedIds = new Set<number>();
    for (const r of relations || []) {
      const cat = r.category;
      if (grouped[cat]) {
        grouped[cat].push({
          id: r.id,
          targetId: r.target_id,
          posX: r.pos_x,
          posY: r.pos_y,
          customName: r.custom_name,
          customNote: r.custom_note,
        });
        categorizedIds.add(r.target_id);
      }
    }

    // Get all active students
    const { data: allStudents } = await db
      .from('users')
      .select('id, username, user_group, user_data, head_url')
      .eq('status', 'On')
      .neq('user_group', 'Admin');

    const uncategorized = (allStudents || [])
      .filter((s: any) => !categorizedIds.has(s.id))
      .map((s: any) => ({
        id: s.id,
        username: s.username,
        userGroup: s.user_group,
        headUrl: s.head_url,
        userData: s.user_data || {},
      }));

    grouped['uncategorized'] = uncategorized;

    res.json({ ok: true, data: grouped });
  } catch (err: any) {
    console.error('Relations by category error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * POST /api/v1/relations/update
 * Headers: Authorization: Bearer <token>
 * Body: { targetId: number, category: string, posX?: number, posY?: number }
 */
router.post('/update', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const { targetId, category, posX, posY } = req.body;
    if (!targetId || !category) {
      return res.json({ ok: false, msg: '参数不完整' });
    }

    // Check if relation exists
    const { data: existing } = await db
      .from('moon_relations')
      .select('id')
      .eq('user_id', userId)
      .eq('target_id', targetId)
      .limit(1);

    if (existing && existing.length > 0) {
      const updateData: any = { category, updated_at: new Date().toISOString() };
      if (posX !== undefined) updateData.pos_x = posX;
      if (posY !== undefined) updateData.pos_y = posY;

      const { error } = await db
        .from('moon_relations')
        .update(updateData)
        .eq('id', existing[0].id);
      if (error) throw error;
    } else {
      const { error } = await db
        .from('moon_relations')
        .insert({
          user_id: userId,
          target_id: targetId,
          category,
          pos_x: posX || null,
          pos_y: posY || null,
        });
      if (error) throw error;
    }

    res.json({ ok: true });
  } catch (err: any) {
    console.error('Relation update error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

export default router;
