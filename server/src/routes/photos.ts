import { Router } from 'express';
import type { Request, Response } from 'express';
import multer from 'multer';
import { pool } from '../db.js';

const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10 * 1024 * 1024 } });

const router = Router();

function getToken(req: Request): string {
  const authHeader = req.headers.authorization || '';
  if (authHeader.startsWith('Bearer ')) {
    return authHeader.slice(7);
  }
  return (req.query.token as string) || '';
}

async function getUserIdFromToken(token: string): Promise<number | null> {
  const [rows] = await pool.query(
    'SELECT `ID` FROM `xlch_user` WHERE `Token` = ?',
    [token]
  );
  const user = (rows as any[])[0];
  return user ? user.ID : null;
}

// GET /api/v1/photos/albums
router.get('/albums', async (req: Request, res: Response) => {
  try {
    const [rows] = await pool.query(
      'SELECT a.*, (SELECT COUNT(*) FROM `moon_photo` WHERE `AlbumId` = a.`ID`) as photoCount FROM `moon_album` a ORDER BY a.`ID` ASC'
    );
    const albums = (rows as any[]).map((a) => ({
      id: a.ID,
      name: a.Name,
      description: a.Description,
      photoCount: a.photoCount || 0,
      createdAt: a.CreatedAt,
    }));
    return res.json({ ok: true, data: albums });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// POST /api/v1/photos/albums
router.post('/albums', async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    const userId = await getUserIdFromToken(token);
    if (!userId) return res.json({ ok: false, msg: '未登录' });

    const { name, description } = req.body;
    if (!name) return res.json({ ok: false, msg: '相册名称不能为空' });

    const [result] = await pool.query(
      'INSERT INTO `moon_album` (`Name`, `Description`) VALUES (?, ?)',
      [name, description || '']
    );
    return res.json({ ok: true, data: { id: (result as any).insertId }, msg: '相册已创建' });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// GET /api/v1/photos/albums/:id
router.get('/albums/:id', async (req: Request, res: Response) => {
  try {
    const id = parseInt(req.params.id as string, 10);
    const [rows] = await pool.query(
      'SELECT * FROM `moon_photo` WHERE `AlbumId` = ? ORDER BY `ID` DESC',
      [id]
    );
    const photos = (rows as any[]).map((p) => ({
      id: p.ID,
      fileName: p.FileName,
      originalName: p.OriginalName,
      url: p.Url,
      title: p.Title,
      description: p.Description,
      albumId: p.AlbumId,
      createdAt: p.CreatedAt,
    }));
    return res.json({ ok: true, data: photos });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// POST /api/v1/photos/upload
router.post('/upload', upload.single('file'), async (req: Request, res: Response) => {
  try {
    const token = getToken(req);
    const userId = await getUserIdFromToken(token);
    if (!userId) return res.json({ ok: false, msg: '未登录' });

    const file = req.file;
    if (!file) return res.json({ ok: false, msg: '请选择图片' });

    const albumId = parseInt(req.body.albumId || '0', 10);
    const title = req.body.title || file.originalname;
    const description = req.body.description || '';

    // Store as base64 data URL for now (in production, use object storage)
    const base64 = `data:${file.mimetype};base64,${file.buffer.toString('base64')}`;
    const fileName = `${Date.now()}_${file.originalname}`;

    const [result] = await pool.query(
      'INSERT INTO `moon_photo` (`FileName`, `OriginalName`, `Url`, `Title`, `Description`, `AlbumId`, `CreatedAt`) VALUES (?, ?, ?, ?, ?, ?, NOW())',
      [fileName, file.originalname, base64, title, description, albumId]
    );

    return res.json({
      ok: true,
      data: { id: (result as any).insertId, url: base64 },
      msg: '图片已上传',
    });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// GET /api/v1/photos/timeline
router.get('/timeline', async (_req: Request, res: Response) => {
  try {
    const [rows] = await pool.query(
      'SELECT i.*, d.`Name` as dirName FROM `xlch_image` i LEFT JOIN `xlch_image_dir` d ON i.`DirId` = d.`ID` ORDER BY i.`AddDate` DESC'
    );

    // Group by date
    const groups: Record<string, any[]> = {};
    (rows as any[]).forEach((img) => {
      const dateStr = img.AddDate ? new Date(img.AddDate).toISOString().split('T')[0] : 'unknown';
      if (!groups[dateStr]) groups[dateStr] = [];
      groups[dateStr].push({
        id: img.ID,
        url: img.Url,
        name: img.Name,
        dirName: img.dirName || '',
        date: dateStr,
      });
    });

    const timeline = Object.entries(groups)
      .sort(([a], [b]) => b.localeCompare(a))
      .map(([date, images]) => ({ date, images }));

    return res.json({ ok: true, data: timeline });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

// GET /api/v1/photos/dirs
router.get('/dirs', async (_req: Request, res: Response) => {
  try {
    const [rows] = await pool.query(
      'SELECT d.*, (SELECT COUNT(*) FROM `xlch_image` WHERE `DirId` = d.`ID`) as imageCount FROM `xlch_image_dir` d ORDER BY d.`AddDate` DESC'
    );
    const dirs = (rows as any[]).map((d) => ({
      id: d.ID,
      name: d.Name,
      bewrite: d.Bewrite,
      createrId: d.CreaterId,
      anybodyUpload: d.AnybodyUpload,
      imageCount: d.imageCount || 0,
      addDate: d.AddDate,
    }));
    return res.json({ ok: true, data: dirs });
  } catch (err: any) {
    return res.json({ ok: false, msg: err.message });
  }
});

export default router;
