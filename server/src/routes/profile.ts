import { Router } from 'express';
import type { Request, Response } from 'express';
import { db } from '../db.js';

const router = Router();

/**
 * GET /api/v1/profile
 * Headers: Authorization: Bearer <token>
 */
router.get('/', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users, error } = await db
      .from('users')
      .select('id, username, user_group, user_data, head_url')
      .eq('token', token)
      .limit(1);
    if (error) throw error;
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });

    const u = users[0];
    const ud = (u.user_data || {}) as any;

    res.json({
      ok: true,
      data: {
        id: u.id,
        username: u.username,
        userGroup: u.user_group,
        headUrl: u.head_url,
        public: ud.Public || {},
        myInfo: ud.MyInfo || {},
        location: ud.Location || {},
        contactMe: ud.ContactMe || {},
        likeAndDislike: ud.LikeAndDislike || {},
      },
    });
  } catch (err: any) {
    console.error('Profile get error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * POST /api/v1/profile
 * Headers: Authorization: Bearer <token>
 * Body: { field: string, value: string }
 */
router.post('/', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id, user_data').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });

    const user = users[0];
    const { field, value } = req.body;

    if (!field || value === undefined) {
      return res.json({ ok: false, msg: '参数不完整' });
    }

    const userData = (user.user_data || {}) as any;
    const parts = field.split('.');

    if (parts.length >= 2) {
      const [group, key] = parts;
      if (!userData[group]) userData[group] = {};
      userData[group][key] = value;
    } else {
      return res.json({ ok: false, msg: '无效的字段名' });
    }

    const { error } = await db.from('users').update({ user_data: userData }).eq('id', user.id);
    if (error) throw error;

    res.json({ ok: true });
  } catch (err: any) {
    console.error('Profile update error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * POST /api/v1/profile/update-multiple
 * Headers: Authorization: Bearer <token>
 * Body: { fields: Array<{ field: string, value: string }> }
 */
router.post('/update-multiple', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id, user_data').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });

    const user = users[0];
    const { fields } = req.body;

    if (!Array.isArray(fields)) {
      return res.json({ ok: false, msg: '参数格式错误' });
    }

    const userData = (user.user_data || {}) as any;
    for (const { field, value } of fields) {
      const parts = field.split('.');
      if (parts.length >= 2) {
        const [group, key] = parts;
        if (!userData[group]) userData[group] = {};
        userData[group][key] = value;
      }
    }

    const { error } = await db.from('users').update({ user_data: userData }).eq('id', user.id);
    if (error) throw error;

    res.json({ ok: true });
  } catch (err: any) {
    console.error('Profile batch update error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

export default router;
