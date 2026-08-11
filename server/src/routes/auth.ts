import { Router } from 'express';
import type { Request, Response } from 'express';
import crypto from 'crypto';
import { db } from '../db.js';

const router = Router();

/**
 * POST /api/v1/auth/login
 * Body: { username: string, password: string }
 */
router.post('/login', async (req: Request, res: Response) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.json({ ok: false, msg: '请输入用户名和密码' });
    }

    const { data: users, error } = await db
      .from('users')
      .select('id, username, password, status, user_group, user_data, head_url')
      .eq('username', username)
      .limit(1);
    if (error) throw error;

    if (!users || users.length === 0) {
      return res.json({ ok: false, msg: '账号或密码错误' });
    }

    const user = users[0];
    if (user.password !== password) {
      return res.json({ ok: false, msg: '账号或密码错误' });
    }
    if (user.status !== 'On') {
      return res.json({ ok: false, msg: '账号已被禁用' });
    }

    const token = crypto.randomBytes(16).toString('hex');
    const loginIp = req.ip || '';

    await db.from('users').update({ token, login_ip: loginIp, login_date: new Date().toISOString() }).eq('id', user.id);

    res.json({
      ok: true,
      data: {
        token,
        user: {
          id: user.id,
          username: user.username,
          userGroup: user.user_group,
          headUrl: user.head_url,
          userData: user.user_data || {},
        },
      },
    });
  } catch (err: any) {
    console.error('Login error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * GET /api/v1/auth/me
 * Headers: Authorization: Bearer <token>
 */
router.get('/me', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });

    const token = authHeader.replace('Bearer ', '');
    const { data: users, error } = await db
      .from('users')
      .select('id, username, user_group, user_data, head_url, status')
      .eq('token', token)
      .limit(1);
    if (error) throw error;

    if (!users || users.length === 0) {
      return res.json({ ok: false, msg: '登录已过期' });
    }

    const user = users[0];
    if (user.status !== 'On') {
      return res.json({ ok: false, msg: '账号已被禁用' });
    }

    res.json({
      ok: true,
      data: {
        id: user.id,
        username: user.username,
        userGroup: user.user_group,
        headUrl: user.head_url,
        userData: user.user_data || {},
      },
    });
  } catch (err: any) {
    console.error('Auth me error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

export default router;
