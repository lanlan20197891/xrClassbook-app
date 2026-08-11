import { Router } from 'express';
import type { Request, Response } from 'express';
import multer from 'multer';
import { db } from '../db.js';

const upload = multer({ storage: multer.memoryStorage(), limits: { fileSize: 10 * 1024 * 1024 } });
const router = Router();

/**
 * GET /api/v1/photos/albums
 * Headers: Authorization: Bearer <token>
 */
router.get('/albums', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const { data: albums, error } = await db
      .from('moon_albums')
      .select('id, name, description, created_at')
      .eq('user_id', userId)
      .order('created_at', { ascending: false });
    if (error) throw error;

    const result = (albums || []).map((a: any) => ({
      id: a.id,
      name: a.name,
      description: a.description,
      createdAt: a.created_at,
    }));

    res.json({ ok: true, data: result });
  } catch (err: any) {
    console.error('Albums list error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * POST /api/v1/photos/albums
 * Headers: Authorization: Bearer <token>
 * Body: { name: string, description?: string }
 */
router.post('/albums', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const { name, description } = req.body;
    if (!name) return res.json({ ok: false, msg: '相册名称不能为空' });

    const { data: inserted, error } = await db
      .from('moon_albums')
      .insert({ user_id: userId, name, description: description || '' })
      .select()
      .single();
    if (error) throw error;

    res.json({ ok: true, data: { id: inserted.id, name: inserted.name } });
  } catch (err: any) {
    console.error('Album create error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * GET /api/v1/photos/albums/:id
 * Headers: Authorization: Bearer <token>
 * Path: id: number
 */
router.get('/albums/:id', async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const albumId = parseInt(req.params.id as string);

    const { data: album, error: albumError } = await db
      .from('moon_albums')
      .select('id, name, description')
      .eq('id', albumId)
      .eq('user_id', userId)
      .maybeSingle();
    if (albumError) throw albumError;
    if (!album) return res.json({ ok: false, msg: '相册不存在' });

    const { data: photos, error: photosError } = await db
      .from('moon_photos')
      .select('id, file_name, original_name, url, title, description, created_at')
      .eq('user_id', userId)
      .eq('album_id', albumId)
      .order('created_at', { ascending: false });
    if (photosError) throw photosError;

    res.json({
      ok: true,
      data: {
        album: { id: album.id, name: album.name, description: album.description },
        photos: (photos || []).map((p: any) => ({
          id: p.id,
          fileName: p.file_name,
          originalName: p.original_name,
          url: p.url,
          title: p.title,
          description: p.description,
          createdAt: p.created_at,
        })),
      },
    });
  } catch (err: any) {
    console.error('Album detail error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

/**
 * POST /api/v1/photos/upload
 * Headers: Authorization: Bearer <token>
 * FormData: file, albumId, title?, description?
 */
router.post('/upload', upload.single('file'), async (req: Request, res: Response) => {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader) return res.json({ ok: false, msg: '未登录' });
    const token = authHeader.replace('Bearer ', '');

    const { data: users } = await db.from('users').select('id').eq('token', token).limit(1);
    if (!users || users.length === 0) return res.json({ ok: false, msg: '未登录' });
    const userId = users[0].id;

    const file = req.file;
    if (!file) return res.json({ ok: false, msg: '请选择图片' });

    const albumId = parseInt(req.body.albumId || '0');
    const title = req.body.title || '';
    const description = req.body.description || '';

    // Store as base64 data URL (for simplicity in this app)
    const base64 = file.buffer.toString('base64');
    const mimeType = file.mimetype;
    const dataUrl = `data:${mimeType};base64,${base64}`;

    const { data: inserted, error } = await db
      .from('moon_photos')
      .insert({
        user_id: userId,
        file_name: file.originalname,
        original_name: file.originalname,
        url: dataUrl,
        title,
        description,
        album_id: albumId,
      })
      .select()
      .single();
    if (error) throw error;

    res.json({
      ok: true,
      data: {
        id: inserted.id,
        fileName: inserted.file_name,
        url: inserted.url,
        title: inserted.title,
      },
    });
  } catch (err: any) {
    console.error('Photo upload error:', err);
    res.json({ ok: false, msg: '上传失败' });
  }
});

/**
 * GET /api/v1/photos/timeline
 * Returns the image timeline data
 */
router.get('/timeline', async (req: Request, res: Response) => {
  try {
    const { data: timeline, error } = await db
      .from('image_timeline')
      .select('id, dir_id, title, description, date_label, sort_date, image_url')
      .order('sort_date', { ascending: false });
    if (error) throw error;

    // Group by dir_id
    const grouped: Record<string, { title: string; description: string; dateLabel: string; images: string[] }> = {};
    for (const item of (timeline || []) as any[]) {
      const key = String(item.dir_id);
      if (!grouped[key]) {
        grouped[key] = {
          title: item.title,
          description: item.description,
          dateLabel: item.date_label,
          images: [],
        };
      }
      grouped[key].images.push(item.image_url);
    }

    const result = Object.values(grouped).map((g) => ({
      title: g.title,
      description: g.description,
      dateLabel: g.dateLabel,
      images: g.images,
    }));

    res.json({ ok: true, data: result });
  } catch (err: any) {
    console.error('Timeline error:', err);
    res.json({ ok: false, msg: '服务器错误' });
  }
});

export default router;
