import express from "express";
import cors from "cors";
import path from "path";
import { fileURLToPath } from "url";

import authRoutes from "./routes/auth.js";
import studentRoutes from "./routes/students.js";
import relationRoutes from "./routes/relations.js";
import profileRoutes from "./routes/profile.js";
import photoRoutes from "./routes/photos.js";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const port = process.env.PORT || 9091;

// Middleware
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// Serve uploaded files statically
app.use('/uploads', express.static(path.join(__dirname, '../../uploads')));

// Health check
app.get('/api/v1/health', (req, res) => {
  res.status(200).json({ status: 'ok' });
});

// Routes
app.use('/api/v1/auth', authRoutes);
app.use('/api/v1/students', studentRoutes);
app.use('/api/v1/relations', relationRoutes);
app.use('/api/v1/profile', profileRoutes);
app.use('/api/v1/photos', photoRoutes);

// Error handling for multer
app.use((err: any, req: express.Request, res: express.Response, next: express.NextFunction) => {
  if (err.code === 'LIMIT_FILE_SIZE') {
    res.status(413).json({ ok: false, msg: '文件大小超过限制（最大 5MB）' });
    return;
  }
  next(err);
});

app.listen(port, () => {
  console.log(`Server listening at http://localhost:${port}/`);
});
