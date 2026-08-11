<?php
/**
 * 小若同学录 · 月光笔记
 * PHP 7.4 + MySQL
 * 数据来源: xlch_user + moon_relation + xlch_image
 */
require_once __DIR__ . '/auth.php';

require_login();

ensure_moon_tables();
ensure_moon_photo_tables();

// 当前登录用户
$currentUser = current_user();

// 获取所有同学数据
$students = fetch_all_students();

// 获取当前用户的关系分类
$relations = fetch_user_relations($currentUser['id']);

// 获取图片时间轴（相册 + 用户上传）
$timeline = fetch_image_timeline();
$photoTimeline = fetch_photo_timeline();
$timeline = array_merge($timeline, $photoTimeline);

// 按日期排序
usort($timeline, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// 构建 targetId → category 映射
$relMap = []; // targetId => category
$customTeachers = []; // 自定义恩师列表

foreach ($relations as $rel) {
    if ($rel['targetId'] > 0) {
        $relMap[$rel['targetId']] = $rel['category'];
    } else {
        // TargetID=0 → 自定义恩师
        $customTeachers[] = $rel;
    }
}

// 为每个同学附加用户自定义分类（覆盖默认 group 分类）
foreach ($students as &$s) {
    if (isset($relMap[$s['id']])) {
        $s['userCategory'] = $relMap[$s['id']];
    } else {
        $s['userCategory'] = ''; // 未分类
    }
}
unset($s);

// 统计各分类人数（基于用户自定义分类）
$catDefs = get_category_defs();
$catCounts = ['close' => 0, 'classmate' => 0, 'roommate' => 0, 'teacher' => 0, 'unclassified' => 0];

foreach ($students as $s) {
    if (isset($catCounts[$s['userCategory']])) {
        $catCounts[$s['userCategory']]++;
    } else {
        $catCounts['unclassified']++;
    }
}
// 加上自定义恩师
$catCounts['teacher'] += count($customTeachers);

$totalCount = count($students) + count($customTeachers);

// JSON 输出
$studentsJson = json_encode($students, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$teachersJson = json_encode($customTeachers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$timelineJson = json_encode($timeline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$catDefsJson  = json_encode($catDefs, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小若同学录 · 月光笔记</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@300;400;500;600;700&family=Ma+Shan+Zheng&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <div class="logo-sub">月光笔记 · 珍藏青春</div>
                    </div>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="搜索同学姓名..." id="searchInput">
                    <div class="search-count" id="searchCount" style="display:none;"></div>
                </div>
            </div>

            <nav class="nav-section">
                <div class="nav-category">导航</div>
                <a href="moon_graph.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">🌙</span>
                    <span>月光图谱</span>
                </a>
                <a href="graph.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">🗺️</span>
                    <span>编辑分类</span>
                </a>
                <a href="upload.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">📷</span>
                    <span>上传图片</span>
                </a>
                <a href="profile.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">✏️</span>
                    <span>编辑信息</span>
                </a>

                <div class="nav-category">我的分类</div>
                <div class="nav-item active" data-cat="all">
                    <span class="nav-icon">📖</span>
                    <span>全部</span>
                    <span class="nav-badge"><?php echo $totalCount; ?></span>
                </div>
                <div class="nav-item" data-cat="close">
                    <span class="nav-icon">⭐</span>
                    <span>挚友</span>
                    <span class="nav-badge"><?php echo $catCounts['close']; ?></span>
                </div>
                <div class="nav-item" data-cat="classmate">
                    <span class="nav-icon">👥</span>
                    <span>同窗</span>
                    <span class="nav-badge"><?php echo $catCounts['classmate']; ?></span>
                </div>
                <div class="nav-item" data-cat="roommate">
                    <span class="nav-icon">🌿</span>
                    <span>萍水相逢</span>
                    <span class="nav-badge"><?php echo $catCounts['roommate']; ?></span>
                </div>
                <div class="nav-item" data-cat="teacher">
                    <span class="nav-icon">📚</span>
                    <span>恩师</span>
                    <span class="nav-badge"><?php echo $catCounts['teacher']; ?></span>
                </div>
                <div class="nav-item" data-cat="unclassified">
                    <span class="nav-icon">💭</span>
                    <span>未分类</span>
                    <span class="nav-badge"><?php echo $catCounts['unclassified']; ?></span>
                </div>

                <div class="nav-category">时光</div>
                <div class="nav-item" data-scroll="timelineSection">
                    <span class="nav-icon">📷</span>
                    <span>回忆时间轴</span>
                </div>
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
                    <span class="stat"><?php echo $totalCount; ?> 位同学</span>
                    <span class="stat">数据来自若与同学录主站</span>
                </div>
            </div>
        </aside>

        <!-- 主内容区 -->
        <main class="main-content">
            <div class="top-bar">
                <div class="breadcrumb">
                    <span>小若同学录</span>
                    <span class="breadcrumb-sep">/</span>
                    <span style="color: var(--text-primary);">月光笔记</span>
                </div>
                <div class="top-actions">
                    <a href="graph.php" class="action-btn">🗺️ 编辑分类</a>
                </div>
            </div>

            <div class="content-area" id="contentArea">
                <!-- 页面标题 -->
                <div class="page-header">
                    <h1 class="page-title">月光同学录</h1>
                    <p class="page-subtitle">愿此去前程似锦，再相逢依旧如故</p>
                    <div class="header-divider"></div>
                </div>

                <!-- 视图切换标签 -->
                <div class="view-tabs">
                    <button class="view-tab active" data-cat="all">全部</button>
                    <button class="view-tab" data-cat="close">挚友</button>
                    <button class="view-tab" data-cat="classmate">同窗</button>
                    <button class="view-tab" data-cat="roommate">萍水相逢</button>
                    <button class="view-tab" data-cat="teacher">恩师</button>
                    <button class="view-tab" data-cat="unclassified">未分类</button>
                </div>

                <!-- 列表视图 -->
                <div id="listView">
                    <div class="section-title">同学卡片</div>
                    <div class="cards-grid" id="cardsGrid">
                    </div>

                    <div class="section-title">个性签名</div>
                    <div class="detail-panel" style="margin-bottom: 40px;">
                        <div class="message-box">
                            <div class="message-label" id="featuredLabel">同学留言</div>
                            <div class="message-text" id="featuredMessage">
                                "愿此去前程似锦，再相逢依旧如故。"
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 详情视图 -->
                <div id="detailView" style="display: none;">
                    <button class="action-btn" onclick="backToList()" style="margin-bottom: 20px;">← 返回列表</button>
                    <div class="detail-panel" id="detailPanel">
                    </div>
                </div>

                <!-- ===== 时间轴 ===== -->
                <div id="timelineSection">
                    <div class="section-title" style="margin-top: 60px;">📷 回忆时间轴</div>
                    <p class="page-subtitle" style="margin-bottom: 30px;">那些年，我们一起走过的日子</p>

                    <div class="timeline-container" id="timelineContainer">
                    </div>
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

    <script>
    (function() {
        // ===== 数据 =====
        var students = <?php echo $studentsJson; ?>;
        var customTeachers = <?php echo $teachersJson; ?>;
        var timeline = <?php echo $timelineJson; ?>;
        var catDefs = <?php echo $catDefsJson; ?>;

        // 分类中文名映射
        var catLabels = {};
        Object.keys(catDefs).forEach(function(k) {
            catLabels[k] = catDefs[k].label;
        });
        catLabels['unclassified'] = '未分类';

        // 分类颜色映射
        var catColors = {};
        Object.keys(catDefs).forEach(function(k) {
            catColors[k] = catDefs[k].color;
        });
        catColors['unclassified'] = '#8a8a9a';

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

        // ===== 渲染卡片 =====
        function renderCards(data) {
            var grid = document.getElementById('cardsGrid');
            grid.innerHTML = '';

            if (data.length === 0) {
                grid.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">暂无同学数据，去<a href="graph.php" style="color:var(--accent);">关系图谱</a>添加分类吧</div>';
                return;
            }

            data.forEach(function(student) {
                var card = document.createElement('div');
                card.className = 'student-card';
                card.onclick = function() { showDetail(student); };

                var avatarHtml;
                if (student.avatarUrl) {
                    avatarHtml = '<img src="' + student.avatarUrl + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" alt="' + student.name + '">';
                } else {
                    avatarHtml = student.initial;
                }

                // 分类标签
                var catLabel = catLabels[student.userCategory] || '未分类';
                var catColor = catColors[student.userCategory] || '#8a8a9a';

                // 自定义恩师显示"恩师"标签
                if (student.isTeacher) {
                    catLabel = '恩师';
                    catColor = catColors['teacher'];
                }

                card.innerHTML =
                    '<div class="card-header">' +
                        '<div class="card-avatar" style="background: ' + catColor + '20; border-color: ' + catColor + '40; color: ' + catColor + '; overflow: hidden;">' +
                            avatarHtml +
                        '</div>' +
                        '<div class="card-meta">' +
                            '<div class="card-name">' + student.name + '</div>' +
                            '<div class="card-role" style="color: ' + catColor + ';">' + catLabel + (student.tags && student.tags[1] ? ' · ' + student.tags[1] : '') + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card-quote">"' + (student.quote || student.customNote || '') + '"</div>' +
                    '<div class="card-tags">' +
                        (student.tags ? student.tags.map(function(tag) { return '<span class="card-tag">' + tag + '</span>'; }).join('') : '') +
                    '</div>';

                grid.appendChild(card);
            });

            if (data.length > 0) {
                var first = data[0];
                document.getElementById('featuredLabel').textContent = first.name + ' 的签名';
                document.getElementById('featuredMessage').textContent = '"' + (first.quote || first.customNote || '') + '"';
            }
        }

        // ===== 按分类筛选 =====
        function filterByCategory(cat) {
            if (cat === 'all') {
                // 全部 = 所有同学 + 自定义恩师
                var all = students.slice();
                customTeachers.forEach(function(t) {
                    all.push({
                        id: 'teacher_' + t.id,
                        name: t.customName,
                        initial: t.customName.charAt(0),
                        quote: t.customNote || '恩师寄语',
                        tags: ['恩师'],
                        color: '#fee140',
                        avatarUrl: '',
                        userCategory: 'teacher',
                        isTeacher: true,
                        info: { '备注': t.customNote || '' }
                    });
                });
                return all;
            }

            if (cat === 'teacher') {
                // 恩师 = xlch_user 中分类为 teacher 的 + 自定义恩师
                var result = students.filter(function(s) { return s.userCategory === 'teacher'; });
                customTeachers.forEach(function(t) {
                    result.push({
                        id: 'teacher_' + t.id,
                        name: t.customName,
                        initial: t.customName.charAt(0),
                        quote: t.customNote || '恩师寄语',
                        tags: ['恩师'],
                        color: '#fee140',
                        avatarUrl: '',
                        userCategory: 'teacher',
                        isTeacher: true,
                        info: { '备注': t.customNote || '' }
                    });
                });
                return result;
            }

            if (cat === 'unclassified') {
                return students.filter(function(s) { return !s.userCategory; });
            }

            return students.filter(function(s) { return s.userCategory === cat; });
        }

        // ===== 详情 =====
        function showDetail(student) {
            var listView = document.getElementById('listView');
            var detailView = document.getElementById('detailView');
            var detailPanel = document.getElementById('detailPanel');

            var infoHtml = '';
            if (student.info) {
                for (var key in student.info) {
                    if (student.info.hasOwnProperty(key)) {
                        infoHtml +=
                            '<div class="info-item">' +
                                '<div class="info-label">' + key + '</div>' +
                                '<div class="info-value">' + student.info[key] + '</div>' +
                            '</div>';
                    }
                }
            }

            var avatarHtml;
            if (student.avatarUrl) {
                avatarHtml = '<img src="' + student.avatarUrl + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;" alt="' + student.name + '">';
            } else {
                avatarHtml = student.initial;
            }

            var catLabel = catLabels[student.userCategory] || '未分类';
            var catColor = catColors[student.userCategory] || '#8a8a9a';
            if (student.isTeacher) { catLabel = '恩师'; catColor = catColors['teacher']; }

            var tagsHtml = student.tags ? student.tags.join(' · ') : '';

            detailPanel.innerHTML =
                '<div class="detail-header">' +
                    '<div class="detail-avatar" style="background: ' + catColor + '20; border-color: ' + catColor + '40; color: ' + catColor + '; overflow: hidden;">' +
                        avatarHtml +
                    '</div>' +
                    '<div class="detail-title">' +
                        '<h2>' + student.name + '</h2>' +
                        '<p style="color: ' + catColor + ';">' + catLabel + (tagsHtml ? ' · ' + tagsHtml : '') + '</p>' +
                    '</div>' +
                '</div>' +
                (infoHtml ? '<div class="info-grid">' + infoHtml + '</div>' : '') +
                '<div class="message-box">' +
                    '<div class="message-label">' + student.name + ' 留言</div>' +
                    '<div class="message-text">"' + (student.info && student.info['留言'] ? student.info['留言'] : (student.quote || '')) + '"</div>' +
                '</div>';

            listView.style.display = 'none';
            detailView.style.display = 'block';
        }

        function backToList() {
            document.getElementById('listView').style.display = 'block';
            document.getElementById('detailView').style.display = 'none';
        }

        // ===== 切换分类 =====
        function switchCategory(cat) {
            // 更新标签栏高亮
            document.querySelectorAll('.view-tab').forEach(function(tab) {
                tab.classList.toggle('active', tab.dataset.cat === cat);
            });
            // 更新侧边栏高亮
            document.querySelectorAll('.nav-item[data-cat]').forEach(function(item) {
                item.classList.toggle('active', item.dataset.cat === cat);
            });

            var filtered = filterByCategory(cat);
            renderCards(filtered);
        }

        // ===== 渲染时间轴 =====
        function renderTimeline() {
            var container = document.getElementById('timelineContainer');

            if (!timeline || timeline.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-muted);">暂无相册数据</div>';
                return;
            }

            var html = '';
            timeline.forEach(function(item, idx) {
                var sideClass = idx % 2 === 0 ? 'left' : 'right';

                // 最多预览 6 张
                var previewImgs = item.images.slice(0, 6);
                var imgsHtml = previewImgs.map(function(img) {
                    return '<img src="' + img.url + '" loading="lazy" onclick="previewImage(\'' + img.url.replace(/'/g, "\\'") + '\')" alt="">';
                }).join('');

                var moreHint = item.count > 6 ? '<div class="timeline-more" onclick="openAlbum(' + item.dirId + ')">+' + (item.count - 6) + ' 张 · 查看全部</div>' : '';

                html +=
                    '<div class="timeline-item ' + sideClass + '">' +
                        '<div class="timeline-dot"></div>' +
                        '<div class="timeline-content">' +
                            '<div class="timeline-date">' + item.dateLabel + '</div>' +
                            '<h3 class="timeline-title">' + item.title + '</h3>' +
                            (item.desc && item.desc !== '暂无介绍...' ? '<p class="timeline-desc">' + item.desc + '</p>' : '') +
                            '<div class="timeline-images">' + imgsHtml + moreHint + '</div>' +
                            '<div class="timeline-count">共 ' + item.count + ' 张照片</div>' +
                        '</div>' +
                    '</div>';
            });

            container.innerHTML = html;
        }

        // ===== 图片预览 =====
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

        // ===== 搜索 =====
        document.getElementById('searchInput').addEventListener('input', function(e) {
            var keyword = e.target.value.trim().toLowerCase();
            var countEl = document.getElementById('searchCount');
            if (!keyword) {
                renderCards(filterByCategory(getCurrentCat()));
                if (countEl) countEl.style.display = 'none';
                return;
            }

            var all = filterByCategory('all');
            var filtered = all.filter(function(s) {
                return s.name.toLowerCase().indexOf(keyword) !== -1 ||
                       (s.tags && s.tags.some(function(tag) { return tag.toLowerCase().indexOf(keyword) !== -1; })) ||
                       (s.quote && s.quote.toLowerCase().indexOf(keyword) !== -1);
            });
            renderCards(filtered);
            if (countEl) {
                countEl.textContent = '找到 ' + filtered.length + ' 位同学';
                countEl.style.display = 'block';
            }
        });

        function getCurrentCat() {
            var active = document.querySelector('.view-tab.active');
            return active ? active.dataset.cat : 'all';
        }

        // ===== 侧边栏点击 =====
        document.querySelectorAll('.nav-item[data-cat]').forEach(function(item) {
            item.addEventListener('click', function() {
                switchCategory(this.dataset.cat);
            });
        });

        // 视图标签点击
        document.querySelectorAll('.view-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                switchCategory(this.dataset.cat);
            });
        });

        // 滚动到时间轴
        document.querySelectorAll('.nav-item[data-scroll]').forEach(function(item) {
            item.addEventListener('click', function() {
                var target = document.getElementById(this.dataset.scroll);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                    document.querySelectorAll('.nav-item').forEach(function(n) { n.classList.remove('active'); });
                    this.classList.add('active');
                }
            });
        });

        // ===== 暴露全局函数 =====
        window.backToList = backToList;
        window.previewImage = previewImage;
        window.openAlbum = function(dirId) { window.open('album.php?dirId=' + dirId, '_blank'); };

        // ===== 初始化 =====
        createStars();
        renderCards(filterByCategory('all'));
        renderTimeline();
    })();

    // ===== 侧边栏折叠 =====
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
