<?php
/**
 * 图片上传页面
 * PHP 7.4 + MySQL
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_login();

ensure_moon_photo_tables();

$currentUser = current_user();
$myPhotos = fetch_user_photos($currentUser['id']);
$myAlbums = fetch_user_albums($currentUser['id']);
$photosJson = json_encode($myPhotos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$albumsJson = json_encode($myAlbums, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传图片 · 小若同学录</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@300;400;500;600;700&family=Ma+Shan+Zheng&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>

    <div class="stars-bg" id="starsBg"></div>
    <div class="moonlight-overlay"></div>

    <div class="app-container">
        <!-- 左侧边栏 -->
        <aside class="sidebar" id="sidebar">
            <button class="sidebar-toggle" onclick="toggleSidebar()" title="折叠/展开侧边栏">
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none">
                    <path d="M6 1L2 5L6 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <div class="sidebar-header">
                <div class="logo-section">
                    <div class="moon-icon"></div>
                    <div>
                        <div class="logo-text">小若同学录</div>
                        <div class="logo-sub">上传图片 · 分享回忆</div>
                    </div>
                </div>
            </div>

            <nav class="nav-section">
                <div class="nav-category">导航</div>
                <a href="index.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">📖</span>
                    <span>同学录</span>
                </a>
                <a href="moon_graph.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">🌙</span>
                    <span>月光图谱</span>
                </a>
                <a href="graph.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">🗺️</span>
                    <span>编辑分类</span>
                </a>
                <div class="nav-item active">
                    <span class="nav-icon">📷</span>
                    <span>上传图片</span>
                </div>
                <a href="profile.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">✏️</span>
                    <span>编辑信息</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="current-user">
                    <?php
                        $cu = $currentUser;
                        $headUrl = $cu['head_url'];
                        $hasImage = !empty($headUrl) && (strpos($headUrl, 'http') === 0 || strpos($headUrl, '/Upload/') === 0);
                        $initial = mb_substr($cu['username'], 0, 1, 'UTF-8');
                    ?>
                    <div class="current-user-avatar">
                        <?php if ($hasImage): ?>
                            <img src="<?php echo htmlspecialchars($headUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="头像">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="current-user-info">
                        <div class="current-user-name"><?php echo htmlspecialchars($cu['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="current-user-group"><?php echo get_group_label($cu['group']); ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn" title="退出登录">⏻</a>
                </div>
                <div class="stats-bar">
                    <span class="stat">数据来自若与同学录主站</span>
                </div>
            </div>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <div class="top-bar">
                <div class="breadcrumb">
                    <a href="index.php" style="color:var(--text-secondary);text-decoration:none;">小若同学录</a>
                    <span class="breadcrumb-sep">/</span>
                    <span style="color: var(--text-primary);">上传图片</span>
                </div>
                <div class="top-actions">
                    <a href="index.php" class="action-btn">← 返回同学录</a>
                </div>
            </div>

            <div class="content-area" id="contentArea">
                <div class="page-header">
                    <h1 class="page-title">上传图片</h1>
                    <p class="page-subtitle">分享你的珍贵回忆，让青春永远闪耀</p>
                    <div class="header-divider"></div>
                </div>

                <!-- 上传表单 -->
                <div class="upload-form-card">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="upload-dropzone" id="dropzone">
                            <div class="dropzone-icon">📷</div>
                            <div class="dropzone-text">点击选择图片或拖拽到此处</div>
                            <div class="dropzone-hint">支持 JPG / PNG / GIF / WebP，最大 5MB</div>
                            <input type="file" name="photo" id="fileInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                        </div>

                        <!-- 图片预览 -->
                        <div class="upload-preview" id="previewArea" style="display:none;">
                            <img id="previewImg" src="" alt="预览">
                            <button type="button" class="preview-remove" id="previewRemove">✕</button>
                        </div>

                        <div class="upload-fields">
                            <div class="login-field">
                                <label class="login-label">所属相册</label>
                                <select name="albumId" id="albumSelect" class="login-input">
                                    <option value="0">未分类</option>
                                    <?php foreach ($myAlbums as $album): ?>
                                        <option value="<?php echo $album['id']; ?>"><?php echo htmlspecialchars($album['name'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $album['photoCount']; ?>张)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="album-create-btn" id="albumCreateBtn">+ 新建相册</button>
                            </div>
                            <div class="login-field">
                                <label class="login-label">标题（选填）</label>
                                <input type="text" name="title" id="titleInput" class="login-input" placeholder="给这张照片起个名字..." maxlength="100">
                            </div>
                            <div class="login-field">
                                <label class="login-label">描述（选填）</label>
                                <input type="text" name="description" id="descInput" class="login-input" placeholder="记录此刻的心情..." maxlength="500">
                            </div>
                        </div>

                        <div class="login-error" id="uploadError" style="display:none;"></div>

                        <button type="submit" class="login-btn" id="uploadBtn">
                            <span>上传图片</span>
                        </button>
                    </form>
                </div>

                <!-- 我的图片 -->
                <div class="section-title" style="margin-top: 40px;">我的图片</div>
                <div class="upload-grid" id="photoGrid">
                </div>

                <!-- 我的相册 -->
                <div class="section-title" style="margin-top: 40px;">我的相册</div>
                <div class="album-list" id="albumList">
                </div>
            </div>

            <!-- 版权页脚 -->
            <div class="app-footer">
                <p>Copyright © 2026 小若同学录管理组</p>
            </div>
        </main>
    </div>

    <!-- 图片预览弹窗 -->
    <div class="img-lightbox" id="imgLightbox">
        <button class="img-lightbox-close" id="imgLightboxClose">✕</button>
        <img class="img-lightbox-img" id="imgLightboxImg" src="" alt="">
    </div>

    <!-- 创建相册弹窗 -->
    <div class="modal-overlay" id="albumModal">
        <div class="modal-card">
            <div class="modal-title">📁 新建相册</div>
            <form id="albumForm" style="padding: 0 28px 28px;">
                <div class="login-field">
                    <label class="login-label">相册名称</label>
                    <input type="text" name="name" id="albumNameInput" class="login-input" placeholder="例如：2026春游、毕业季..." maxlength="50" required>
                </div>
                <div class="login-field" style="margin-top: 14px;">
                    <label class="login-label">相册描述（选填）</label>
                    <input type="text" name="description" id="albumDescInput" class="login-input" placeholder="简单描述这个相册..." maxlength="255">
                </div>
                <div class="login-error" id="albumError" style="display:none; margin-top: 12px;"></div>
                <button type="submit" class="login-btn" id="albumSubmitBtn" style="margin-top: 16px;">创建相册</button>
                <button type="button" class="login-btn-secondary" onclick="closeAlbumModal()" style="margin-top: 8px; width: 100%; padding: 10px; background: transparent; border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-secondary); cursor: pointer; font-family: 'Noto Serif SC', serif; font-size: 0.9rem;">取消</button>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div class="graph-toast" id="uploadToast"></div>

    <script>
    (function() {
        'use strict';

        var myPhotos = <?php echo $photosJson; ?>;
        var myAlbums = <?php echo $albumsJson; ?>;

        // ===== 星空 =====
        function createStars() {
            var container = document.getElementById('starsBg');
            for (var i = 0; i < 100; i++) {
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
        }

        // ===== Toast =====
        function showToast(msg) {
            var t = document.getElementById('uploadToast');
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.classList.remove('show'); }, 2500);
        }

        // ===== 图片预览 =====
        var fileInput = document.getElementById('fileInput');
        var dropzone = document.getElementById('dropzone');
        var previewArea = document.getElementById('previewArea');
        var previewImg = document.getElementById('previewImg');
        var previewRemove = document.getElementById('previewRemove');
        var selectedFile = null;

        function showPreview(file) {
            if (!file) return;
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/)) {
                showToast('仅支持 JPG/PNG/GIF/WebP 格式');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showToast('图片大小不能超过5MB');
                return;
            }

            selectedFile = file;
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewArea.style.display = 'flex';
                dropzone.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        function clearPreview() {
            selectedFile = null;
            fileInput.value = '';
            previewArea.style.display = 'none';
            dropzone.style.display = 'flex';
        }

        dropzone.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showPreview(this.files[0]);
            }
        });

        previewRemove.addEventListener('click', clearPreview);

        // 拖拽上传
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });
        dropzone.addEventListener('dragleave', function() {
            dropzone.classList.remove('drag-over');
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                showPreview(e.dataTransfer.files[0]);
            }
        });

        // ===== 上传 =====
        var uploadForm = document.getElementById('uploadForm');
        var uploadBtn = document.getElementById('uploadBtn');
        var errorEl = document.getElementById('uploadError');

        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedFile) {
                errorEl.textContent = '⚠ 请先选择图片';
                errorEl.style.display = 'flex';
                return;
            }

            errorEl.style.display = 'none';
            uploadBtn.disabled = true;
            uploadBtn.textContent = '上传中...';

            var formData = new FormData();
            formData.append('action', 'upload');
            formData.append('photo', selectedFile);
            formData.append('title', document.getElementById('titleInput').value.trim());
            formData.append('description', document.getElementById('descInput').value.trim());
            formData.append('albumId', document.getElementById('albumSelect').value);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_api.php', true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = '上传图片';

                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.ok) {
                                showToast('上传成功！');
                                // 添加到列表前面
                                if (res.data) {
                                    res.data.albumId = parseInt(document.getElementById('albumSelect').value) || 0;
                                    myPhotos.unshift(res.data);
                                    renderPhotos();
                                    // 更新相册计数
                                    var selAlbumId = res.data.albumId;
                                    if (selAlbumId > 0) {
                                        myAlbums.forEach(function(a) {
                                            if (a.id === selAlbumId) a.photoCount++;
                                        });
                                        renderAlbums();
                                        // 更新下拉框文字
                                        var opt = document.querySelector('#albumSelect option[value="' + selAlbumId + '"]');
                                        if (opt) {
                                            var name = opt.textContent.replace(/\s*\(\d+张\)/, '');
                                            opt.textContent = name + ' (' + (myAlbums.find(function(a) { return a.id === selAlbumId; }) || {}).photoCount + '张)';
                                        }
                                    }
                                }
                                // 清空表单
                                clearPreview();
                                document.getElementById('titleInput').value = '';
                                document.getElementById('descInput').value = '';
                            } else {
                                errorEl.textContent = '⚠ ' + (res.msg || '上传失败');
                                errorEl.style.display = 'flex';
                            }
                        } catch(err) {
                            errorEl.textContent = '⚠ 解析响应失败';
                            errorEl.style.display = 'flex';
                        }
                    } else {
                        errorEl.textContent = '⚠ 网络错误';
                        errorEl.style.display = 'flex';
                    }
                }
            };

            // 上传进度
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    uploadBtn.textContent = '上传中... ' + pct + '%';
                }
            };

            xhr.send(formData);
        });

        // ===== 渲染图片列表 =====
        function renderPhotos() {
            var grid = document.getElementById('photoGrid');

            if (myPhotos.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">还没有上传图片，快分享你的第一张回忆吧！</div>';
                return;
            }

            var html = '';
            myPhotos.forEach(function(photo) {
                var titleHtml = photo.title ? '<div class="photo-card-title">' + escapeHtml(photo.title) + '</div>' : '';
                var descHtml = photo.description ? '<div class="photo-card-desc">' + escapeHtml(photo.description) + '</div>' : '';
                var dateHtml = photo.dateLabel ? '<div class="photo-card-date">' + escapeHtml(photo.dateLabel) + '</div>' : '';

                html +=
                    '<div class="photo-card">' +
                        '<div class="photo-card-img">' +
                            '<img src="' + photo.url + '" loading="lazy" onclick="previewImage(\'' + photo.url.replace(/'/g, "\\'") + '\')" alt="">' +
                        '</div>' +
                        '<div class="photo-card-info">' +
                            titleHtml +
                            descHtml +
                            dateHtml +
                        '</div>' +
                    '</div>';
            });

            grid.innerHTML = html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===== 图片预览弹窗 =====
        function previewImage(url) {
            var box = document.getElementById('imgLightbox');
            var img = document.getElementById('imgLightboxImg');
            img.src = url;
            box.classList.add('show');
        }

        document.getElementById('imgLightboxClose').addEventListener('click', function() {
            document.getElementById('imgLightbox').classList.remove('show');
        });
        document.getElementById('imgLightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });

        window.previewImage = previewImage;
        window.filterByAlbum = filterByAlbum;
        window.closeAlbumModal = closeAlbumModal;

        // ===== 相册列表渲染 =====
        function renderAlbums() {
            var container = document.getElementById('albumList');

            if (myAlbums.length === 0) {
                container.innerHTML =
                    '<div class="album-empty-state">' +
                        '<div class="empty-icon">📁</div>' +
                        '<div>暂无相册，点击「+ 新建相册」创建第一个吧</div>' +
                    '</div>';
                return;
            }

            var html = '';
            myAlbums.forEach(function(album) {
                var descHtml = album.description
                    ? '<div class="album-card-desc">' + escapeHtml(album.description) + '</div>'
                    : '<div class="album-card-desc empty">暂无描述</div>';

                html +=
                    '<div class="album-card" onclick="filterByAlbum(' + album.id + ')">' +
                        '<div class="album-card-header">' +
                            '<span class="album-card-icon">📁</span>' +
                            '<span class="album-card-name">' + escapeHtml(album.name) + '</span>' +
                            '<span class="album-card-count">' + album.photoCount + ' 张</span>' +
                        '</div>' +
                        descHtml +
                        '<div class="album-card-date">创建于 ' + escapeHtml(album.dateLabel) + '</div>' +
                    '</div>';
            });

            container.innerHTML = html;
        }

        // 筛选相册中的图片
        function filterByAlbum(albumId) {
            var grid = document.getElementById('photoGrid');
            var filtered = albumId === 0 ? myPhotos : myPhotos.filter(function(p) { return p.albumId === albumId; });

            if (filtered.length === 0) {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">该相册暂无图片</div>';
                return;
            }

            var html = '';
            filtered.forEach(function(photo) {
                var titleHtml = photo.title ? '<div class="photo-card-title">' + escapeHtml(photo.title) + '</div>' : '';
                var descHtml = photo.description ? '<div class="photo-card-desc">' + escapeHtml(photo.description) + '</div>' : '';
                var dateHtml = photo.dateLabel ? '<div class="photo-card-date">' + escapeHtml(photo.dateLabel) + '</div>' : '';

                html +=
                    '<div class="photo-card">' +
                        '<div class="photo-card-img">' +
                            '<img src="' + photo.url + '" loading="lazy" onclick="previewImage(\'' + photo.url.replace(/'/g, "\\'") + '\')" alt="">' +
                        '</div>' +
                        '<div class="photo-card-info">' +
                            titleHtml +
                            descHtml +
                            dateHtml +
                        '</div>' +
                    '</div>';
            });

            grid.innerHTML = html;
        }

        // ===== 相册弹窗 =====
        var albumModal = document.getElementById('albumModal');
        var albumForm = document.getElementById('albumForm');
        var albumCreateBtn = document.getElementById('albumCreateBtn');
        var albumSubmitBtn = document.getElementById('albumSubmitBtn');
        var albumError = document.getElementById('albumError');

        function openAlbumModal() {
            albumError.style.display = 'none';
            document.getElementById('albumNameInput').value = '';
            document.getElementById('albumDescInput').value = '';
            albumModal.classList.add('show');
            setTimeout(function() { document.getElementById('albumNameInput').focus(); }, 100);
        }

        function closeAlbumModal() {
            albumModal.classList.remove('show');
        }

        albumCreateBtn.addEventListener('click', openAlbumModal);

        albumForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var name = document.getElementById('albumNameInput').value.trim();
            var description = document.getElementById('albumDescInput').value.trim();

            if (!name) {
                albumError.textContent = '⚠ 请输入相册名称';
                albumError.style.display = 'flex';
                return;
            }

            albumError.style.display = 'none';
            albumSubmitBtn.disabled = true;
            albumSubmitBtn.textContent = '创建中...';

            var formData = new FormData();
            formData.append('action', 'create_album');
            formData.append('name', name);
            formData.append('description', description);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'upload_api.php', true);

            xhr.onload = function() {
                albumSubmitBtn.disabled = false;
                albumSubmitBtn.textContent = '创建相册';

                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data.ok) {
                            myAlbums.unshift(data.album);
                            renderAlbums();

                            // 在下拉框中添加新相册
                            var select = document.getElementById('albumSelect');
                            var opt = document.createElement('option');
                            opt.value = data.album.id;
                            opt.textContent = data.album.name + ' (0张)';
                            select.appendChild(opt);
                            select.value = data.album.id;

                            closeAlbumModal();
                            showToast(data.msg || '相册创建成功');
                        } else {
                            albumError.textContent = '⚠ ' + (data.msg || '创建失败');
                            albumError.style.display = 'flex';
                        }
                    } catch (err) {
                        albumError.textContent = '⚠ 解析响应失败';
                        albumError.style.display = 'flex';
                    }
                } else {
                    albumError.textContent = '⚠ 网络错误';
                    albumError.style.display = 'flex';
                }
            };

            xhr.send(formData);
        });

        // ===== 初始化 =====
        createStars();
        renderPhotos();
        renderAlbums();
    })();

    function toggleSidebar() {
        var sb = document.getElementById('sidebar');
        var isCollapsed = sb.classList.contains('user-collapsed');
        if (isCollapsed) {
            sb.classList.remove('user-collapsed', 'collapsed');
        } else {
            sb.classList.add('user-collapsed', 'collapsed');
        }
        try { localStorage.setItem('sidebarUserCollapsed', sb.classList.contains('user-collapsed') ? '1' : '0'); } catch(e) {}
    }

    function autoSidebar() {
        var sb = document.getElementById('sidebar');
        var userCollapsed = sb.classList.contains('user-collapsed');
        if (!userCollapsed) {
            if (window.innerWidth <= 900) {
                sb.classList.add('collapsed');
            } else {
                sb.classList.remove('collapsed');
            }
        }
    }

    (function() {
        try {
            if (localStorage.getItem('sidebarUserCollapsed') === '1') {
                document.getElementById('sidebar').classList.add('user-collapsed', 'collapsed');
            }
        } catch(e) {}
        autoSidebar();
    })();

    window.addEventListener('resize', autoSidebar);
    </script>
</body>
</html>
