import { Router } from 'express';
import type { Request, Response } from 'express';
import { pool } from '../db.js';

const router = Router();

function getToken(req: Request): string {
  const authHeader = req.headers.authorization || '';
  if (authHeader.startsWith('Bearer ')) {
    return authHeader.slice(7);
  }
  return (req.query.token as string) || '';
}

// GET /api/v1/profile
router.get('/', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
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

// PUT /api/v1/profile
router.put('/', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    if (!token) {
      return res.json({ ok: false, msg: '未登录' });
    }

    const [rows] = await pool.query(
      'SELECT `ID`, `UserData` FROM `xlch_user` WHERE `Token` = ?',
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

    const ud = userData as any;
    const { sign, birthday, gender, constellation, motto, hometown, nowLive, qq, wechat, email, phone, myLikeThing, beGoodAt } = req.body;

    // Update UserData JSON
    if (!ud.Public) ud.Public = {};
    if (!ud.MyInfo) ud.MyInfo = {};
    if (!ud.Location) ud.Location = {};
    if (!ud.ContactMe) ud.ContactMe = {};
    if (!ud.SocialAccount) ud.SocialAccount = {};
    if (!ud.LikeAndDislike) ud.LikeAndDislike = {};

    if (sign !== undefined) ud.Public.Sign = sign;
    if (birthday !== undefined) ud.MyInfo.Birthday = birthday;
    if (gender !== undefined) ud.MyInfo.Gender = gender;
    if (constellation !== undefined) ud.MyInfo.Constellation = constellation;
    if (motto !== undefined) ud.MyInfo.Motto = motto;
    if (hometown !== undefined) ud.Location.Hometown = hometown;
    if (nowLive !== undefined) ud.Location.NowLive = nowLive;
    if (qq !== undefined) ud.SocialAccount.QQ = qq;
    if (wechat !== undefined) ud.SocialAccount.WeChat = wechat;
    if (email !== undefined) ud.ContactMe.Email = email;
    if (phone !== undefined) ud.ContactMe.Phone = phone;
    if (myLikeThing !== undefined) ud.LikeAndDislike.MyLikeThing = myLikeThing;
    if (beGoodAt !== undefined) ud.LikeAndDislike.BeGoodAt = beGoodAt;

    await pool.query(
      'UPDATE `xlch_user` SET `UserData` = ? WHERE `ID` = ?',
      [JSON.stringify(ud), user.ID]
    );

    return res.json({ ok: true, msg: '资料已更新', data: { userData: ud } });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

export default router;
