import { Router } from 'express';
import type { Request, Response } from 'express';
import { pool } from '../db.js';

const router = Router();

// Helper to get userId from token
async function getUserIdFromToken(token: string): Promise<number | null> {
  const [rows] = await pool.query(
    'SELECT `ID` FROM `xlch_user` WHERE `Token` = ?',
    [token]
  );
  const user = (rows as any[])[0];
  return user ? user.ID : null;
}

function getToken(req: Request): string {
  const authHeader = req.headers.authorization || '';
  if (authHeader.startsWith('Bearer ')) {
    return authHeader.slice(7);
  }
  return (req.query.token as string) || '';
}

// GET /api/v1/relations/moon
router.get('/moon', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    const userId = await getUserIdFromToken(token);
    if (!userId) {
      return res.json({ ok: false, msg: '未登录' });
    }

    const [rows] = await pool.query(
      'SELECT * FROM `moon_relation` WHERE `UserID` = ? ORDER BY `ID` ASC',
      [userId]
    );

    const relations = (rows as any[]).map((r) => ({
      id: r.ID,
      userId: r.UserID,
      targetId: r.TargetID,
      category: r.Category,
      posX: r.PosX,
      posY: r.PosY,
      customName: r.CustomName,
      customNote: r.CustomNote,
      createdAt: r.CreatedAt,
      updatedAt: r.UpdatedAt,
    }));

    return res.json({ ok: true, data: relations });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// POST /api/v1/relations/moon
router.post('/moon', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    const userId = await getUserIdFromToken(token);
    if (!userId) {
      return res.json({ ok: false, msg: '未登录' });
    }

    const { targetId, category, posX, posY, customName, customNote } = req.body;

    await pool.query(
      `INSERT INTO \`moon_relation\` (\`UserID\`, \`TargetID\`, \`Category\`, \`PosX\`, \`PosY\`, \`CustomName\`, \`CustomNote\`, \`CreatedAt\`, \`UpdatedAt\`)
       VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [userId, targetId || 0, category || 'classmate', posX || 0, posY || 0, customName || '', customNote || '']
    );

    return res.json({ ok: true, msg: '关系已添加' });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// PUT /api/v1/relations/moon/:id
router.put('/moon/:id', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    const userId = await getUserIdFromToken(token);
    if (!userId) {
      return res.json({ ok: false, msg: '未登录' });
    }

    const id = parseInt(req.params.id as string, 10);
    const { category, posX, posY, customName, customNote } = req.body;

    await pool.query(
      `UPDATE \`moon_relation\` SET \`Category\` = ?, \`PosX\` = ?, \`PosY\` = ?, \`CustomName\` = ?, \`CustomNote\` = ?, \`UpdatedAt\` = NOW()
       WHERE \`ID\` = ? AND \`UserID\` = ?`,
      [category || 'classmate', posX, posY, customName || '', customNote || '', id, userId]
    );

    return res.json({ ok: true, msg: '关系已更新' });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

export default router;
