<?php
/**
 * 相册详情页 — 展示某个相册目录的全部图片
 * URL: album.php?dirId=2
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_login();

$dirId = isset($_GET['dirId']) ? (int)$_GET['dirId'] : 0;
$album = fetch_album_by_dirid($dirId);

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $album ? htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8') . ' · 相册' : '相册不存在'; ?> · 小若同学录</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@300;400;500;600;700&family=Ma+Shan+Zheng&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        .album-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 24px 80px;
        }
        .album-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .album-date {
            font-family: 'Ma Shan Zheng', cursive;
            font-size: 1.5rem;
            color: var(--accent);
            letter-spacing: 3px;
            margin-bottom: 8px;
        }
        .album-title {
            font-size: 2rem;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
        }
        .album-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.8;
        }
        .album-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 12px;
        }
        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }
        .album-grid img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.02);
        }
        .album-grid img:hover {
            transform: scale(1.05);
            border-color: rgba(201, 184, 150, 0.4);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .album-empty {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted);
        }
        .album-empty h2 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: var(--text-secondary);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 30px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: var(--accent);
        }
        /* 灯箱 */
        .lightbox {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.92);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: zoom-out;
        }
        .lightbox.show {
            display: flex;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.5);
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 24px;
            font-size: 2rem;
            color: #fff;
            cursor: pointer;
            line-height: 1;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        .lightbox-close:hover {
            opacity: 1;
        }
        .album-body {
            background: var(--bg-primary);
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }
        body {
            overflow-y: auto !important;
        }
    </style>
</head>
<body>

    <div class="stars-bg" id="starsBg"></div>
    <div class="moonlight-overlay"></div>

    <div class="album-body">
        <div class="album-page">
            <a href="index.php" class="back-link">← 返回同学录</a>

            <?php if ($album): ?>
                <div class="album-header">
                    <div class="album-date"><?php echo htmlspecialchars($album['dateLabel'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <h1 class="album-title"><?php echo htmlspecialchars($album['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if ($album['desc'] && $album['desc'] !== '暂无介绍...'): ?>
                        <p class="album-desc"><?php echo htmlspecialchars($album['desc'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <div class="album-count">共 <?php echo $album['count']; ?> 张照片</div>
                </div>

                <div class="album-grid">
                    <?php foreach ($album['images'] as $img): ?>
                        <img src="<?php echo htmlspecialchars($img['url'], ENT_QUOTES, 'UTF-8'); ?>"
                             loading="lazy"
                             onclick="openLightbox(this.src)"
                             alt="">
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="album-empty">
                    <h2>📷 相册不存在</h2>
                    <p>该相册可能已被删除或没有可显示的图片</p>
                    <br>
                    <a href="index.php" class="back-link">← 返回同学录</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 灯箱 -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close" onclick="closeLightbox()">×</span>
        <img id="lightboxImg" src="" alt="">
    </div>

    <script>
        // 星空
        (function() {
            var container = document.getElementById('starsBg');
            for (var i = 0; i < 80; i++) {
                var star = document.createElement('div');
                star.className = 'star';
                var size = Math.random() * 2 + 1;
                star.style.width = size + 'px';
                star.style.height = size + 'px';
                star.style.left = Math.random() * 100 + '%';
                star.style.top = Math.random() * 100 + '%';
                star.style.setProperty('--duration', (Math.random() * 4 + 2) + 's');
                star.style.setProperty('--opacity', (Math.random() * 0.8 + 0.2));
                star.style.animationDelay = Math.random() * 5 + 's';
                container.appendChild(star);
            }
        })();

        function openLightbox(url) {
            document.getElementById('lightboxImg').src = url;
            document.getElementById('lightbox').classList.add('show');
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('show');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });
    </script>
</body>
</html>
