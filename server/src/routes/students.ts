import { Router } from 'express';
import type { Request, Response } from 'express';
import { pool } from '../db.js';

const router = Router();

// GET /api/v1/students
// Query: { search?: string, category?: string }
router.get('/', async (req: Request, res: Response) => {
  try {
    const search = (req.query.search as string) || '';
    const category = (req.query.category as string) || '';

    let sql = 'SELECT `ID`, `Username`, `HeadUrl`, `Group`, `UserData`, `Status` FROM `xlch_user` WHERE `Status` != ?';
    const params: any[] = ['Off'];

    if (search) {
      sql += ' AND `Username` LIKE ?';
      params.push(`%${search}%`);
    }

    if (category) {
      if (category === 'teacher') {
        sql += ' AND `Group` = ?';
        params.push('Teacher');
      } else if (category === 'monitor') {
        sql += ' AND `Group` = ?';
        params.push('Monitor');
      } else if (category === 'student') {
        sql += ' AND `Group` NOT IN (?, ?)';
        params.push('Teacher', 'Admin');
      }
    }

    sql += ' ORDER BY `ID` ASC';

    const [rows] = await pool.query(sql, params);
    const students = (rows as any[]).map((u) => {
      let userData = {};
      try {
        userData = u.UserData ? JSON.parse(u.UserData) : {};
      } catch { /* ignore */ }
      const ud = userData as any;
      return {
        id: u.ID,
        username: u.Username,
        headUrl: u.HeadUrl,
        group: u.Group,
        sign: ud?.Public?.Sign || '',
        birthday: ud?.MyInfo?.Birthday || '',
        gender: ud?.MyInfo?.Gender || '',
        constellation: ud?.MyInfo?.Constellation || '',
        motto: ud?.MyInfo?.Motto || '',
        hometown: ud?.Location?.Hometown || '',
        nowLive: ud?.Location?.NowLive || '',
        myLikeThing: ud?.LikeAndDislike?.MyLikeThing || '',
        beGoodAt: ud?.LikeAndDislike?.BeGoodAt || '',
        qq: ud?.ContactMe?.QQ || ud?.SocialAccount?.QQ || '',
        wechat: ud?.ContactMe?.WeChat || ud?.SocialAccount?.WeChat || '',
        email: ud?.ContactMe?.Email || '',
        phone: ud?.ContactMe?.Phone || '',
      };
    });

    return res.json({ ok: true, data: students });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// GET /api/v1/students/:id
router.get('/:id', async (req: Request, res: Response) => {
  try {
    const id = parseInt(req.params.id as string, 10);
    const [rows] = await pool.query(
      'SELECT * FROM `xlch_user` WHERE `ID` = ?',
      [id]
    );
    const user = (rows as any[])[0];
    if (!user) {
      return res.json({ ok: false, msg: '同学不存在' });
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
        status: user.Status,
        userData,
      },
    });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

export default router;
