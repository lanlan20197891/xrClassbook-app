import { Router } from 'express';
import type { Request, Response } from 'express';
import { db } from '../db.js';
import { RELATION_CATEGORIES } from '../constants.js';

const router = Router();

/**
 * GET /api/v1/students
 * Query: { keyword?: string, category?: string }
 */
router.get('/', async (req: Request, res: Response) => {
  try {
    const keyword = (req.query.keyword as string) || '';
    const category = (req.query.category as string) || '';

    let query = db
      .from('users')
      .select('id, username, user_group, user_data, head_url, status')
      .eq('status', 'On')
      .neq('user_group', 'Admin');

    if (keyword) {
      query = query.ilike('username', `%${keyword}%`);
    }

    if (category && category !== 'all') {
      if (category === 'uncategorized') {
        // Users not in any relation category
        const { data: relations } = await db
          .from('moon_relations')
          .select('target_id')
          .neq('category', 'classmate');
        const relatedIds = (relations || []).map((r: any) => r.target_id);
        if (relatedIds.length > 0) {
          query = query.not('id', 'in', `(${relatedIds.join(',')})`);
        }
      } else {
        const { data: relations } = await db
          .from('moon_relations')
          .select('target_id')
          .eq('category', category);
        const relatedIds = (relations || []).map((r: any) => r.target_id);
        if (relatedIds.length > 0) {
          query = query.in('id', relatedIds);
        } else {
          return res.json({ ok: true, data: [] });
        }
      }
    }

    query = query.order('id', { ascending: true });

    const { data: users, error } = await query;
    if (error) throw error;

    const result = (users || []).map((u: any) => ({
      id: u.id,
      username: u.username,
      userGroup: u.user_group,
      headUrl: u.head_url,
      userData: u.user_data || {},
    }));

    res.json({ ok: true, data: result });
  } catch (err: any) {
    console.error('Students list error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * GET /api/v1/students/:id
 * Path: id: number
 */
router.get('/:id', async (req: Request, res: Response) => {
  try {
    const id = parseInt(req.params.id as string);
    const { data: users, error } = await db
      .from('users')
      .select('id, username, user_group, user_data, head_url, status')
      .eq('id', id)
      .eq('status', 'On')
      .limit(1);
    if (error) throw error;

    if (!users || users.length === 0) {
      return res.json({ ok: false, msg: '用户不存在' });
    }

    const u = users[0];
    res.json({
      ok: true,
      data: {
        id: u.id,
        username: u.username,
        userGroup: u.user_group,
        headUrl: u.head_url,
        userData: u.user_data || {},
      },
    });
  } catch (err: any) {
    console.error('Student detail error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * GET /api/v1/students/timeline/images
 * Query: { keyword?: string }
 */
router.get('/timeline/images', async (req: Request, res: Response) => {
  try {
    const keyword = (req.query.keyword as string) || '';

    let query = db
      .from('image_timeline')
      .select('id, dir_id, title, description, date_label, sort_date, image_url')
      .order('sort_date', { ascending: false });

    if (keyword) {
      query = query.or(`title.ilike.%${keyword}%,description.ilike.%${keyword}%`);
    }

    const { data: images, error } = await query;
    if (error) throw error;

    const result = (images || []).map((img: any) => ({
      id: img.id,
      dirId: img.dir_id,
      title: img.title,
      description: img.description,
      dateLabel: img.date_label,
      sortDate: img.sort_date,
      imageUrl: img.image_url,
    }));

    res.json({ ok: true, data: result });
  } catch (err: any) {
    console.error('Timeline images error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

export default router;
