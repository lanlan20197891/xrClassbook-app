import { Router } from 'express';
import type { Request, Response } from 'express';
import crypto from 'crypto';
import { pool } from '../db.js';

const router = Router();

// POST /api/v1/auth/login
// Body: { username: string, password: string }
router.post('/login', async (req: Request, res: Response) => {
  try {
    const { username, password } = req.body;
    if (!username || !password) {
      return res.json({ ok: false, msg: '请输入用户名和密码' });
    }

    const [rows] = await pool.query(
      'SELECT * FROM `xlch_user` WHERE `Username` = ? AND `Status` != ?',
      [username, 'Off']
    );
    const user = (rows as any[])[0];

    if (!user) {
      return res.json({ ok: false, msg: '用户名或密码错误' });
    }

    if (user.Password !== password) {
      return res.json({ ok: false, msg: '用户名或密码错误' });
    }

    const token = crypto.randomBytes(16).toString('hex');
    const loginIp = req.ip || '';

    await pool.query(
      'UPDATE `xlch_user` SET `Token` = ?, `LoginIP` = ?, `LoginDate` = NOW() WHERE `ID` = ?',
      [token, loginIp, user.ID]
    );

    let userData = {};
    try {
      userData = user.UserData ? JSON.parse(user.UserData) : {};
    } catch { /* ignore */ }

    return res.json({
      ok: true,
      data: {
        token,
        user: {
          id: user.ID,
          username: user.Username,
          headUrl: user.HeadUrl,
          group: user.Group,
          userData,
        },
      },
    });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// GET /api/v1/auth/me
router.get('/me', async (req: Request, res: Response) => {
  try {
    const token = req.query.token as string;
    if (!token) {
      return res.json({ ok: false, msg: '未登录' });
    }

    const [rows] = await pool.query(
      'SELECT * FROM `xlch_user` WHERE `Token` = ?',
      [token]
    );
    const user = (rows as any[])[0];

    if (!user) {
      return res.json({ ok: false, msg: '登录已过期' });
    }

    let userData = {};
    try {
      userData = user.UserData ? JSON.parse(user.UserData) : {};
    } catch { /* ignore */ }

    return res.json({
      ok: true,
      data: {
        id: user.ID,
        username: user.Username,
        headUrl: user.HeadUrl,
        group: user.Group,
        userData,
      },
    });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

export default router;
