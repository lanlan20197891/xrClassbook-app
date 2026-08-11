<?php
/**
 * 月光图谱页面 — 以用户为中心，小月亮环绕
 * PHP 7.4 + MySQL
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_login();

ensure_moon_tables();

$currentUser = current_user();

// 获取所有同学数据
$students = fetch_all_students();

// 获取当前用户的关系分类
$relations = fetch_user_relations($currentUser['id']);

// 构建 targetId → category 映射
$relMap = [];
$customTeachers = [];

foreach ($relations as $rel) {
    if ($rel['targetId'] > 0) {
        $relMap[$rel['targetId']] = $rel['category'];
    } else {
        $customTeachers[] = $rel;
    }
}

// 为每个同学附加用户自定义分类
foreach ($students as &$s) {
    if (isset($relMap[$s['id']])) {
        $s['userCategory'] = $relMap[$s['id']];
    } else {
        $s['userCategory'] = '';
    }
}
unset($s);

// 排除当前用户自己
$students = array_filter($students, function($s) use ($currentUser) {
    return $s['id'] !== $currentUser['id'];
});
$students = array_values($students);

$catDefs = get_category_defs();
$catDefsJson = json_encode($catDefs, JSON_UNESCAPED_UNICODE);
$studentsJson = json_encode($students, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$teachersJson = json_encode($customTeachers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 统计各分类人数
$catCounts = ['close' => 0, 'classmate' => 0, 'roommate' => 0, 'teacher' => 0, 'unclassified' => 0];
foreach ($students as $s) {
    if (isset($catCounts[$s['userCategory']])) {
        $catCounts[$s['userCategory']]++;
    } else {
        $catCounts['unclassified']++;
    }
}
$catCounts['teacher'] += count($customTeachers);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>月光图谱 · 小若同学录</title>
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
                        <div class="logo-sub">月光图谱 · 星河璀璨</div>
                    </div>
                </div>
            </div>

            <nav class="nav-section">
                <div class="nav-category">导航</div>
                <a href="index.php" class="nav-item" style="text-decoration:none;">
                    <span class="nav-icon">📖</span>
                    <span>同学录</span>
                </a>
                <div class="nav-item active">
                    <span class="nav-icon">🌙</span>
                    <span>月光图谱</span>
                </div>
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

                <div class="nav-category">图例</div>
                <?php foreach ($catDefs as $key => $cat): ?>
                <div class="nav-item" style="cursor:default;">
                    <span class="nav-icon" style="color: <?php echo $cat['color']; ?>;"><?php echo $cat['icon']; ?></span>
                    <span><?php echo $cat['label']; ?></span>
                    <span class="nav-badge"><?php echo $catCounts[$key]; ?></span>
                </div>
                <?php endforeach; ?>
                <div class="nav-item" style="cursor:default;">
                    <span class="nav-icon" style="color: #8a8a9a;">💭</span>
                    <span>未分类</span>
                    <span class="nav-badge"><?php echo $catCounts['unclassified']; ?></span>
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
                    <span style="color: var(--text-primary);">月光图谱</span>
                </div>
                <div class="top-actions">
                    <a href="graph.php" class="action-btn">🗺️ 编辑分类</a>
                </div>
            </div>

            <div class="content-area" id="contentArea">
                <div class="page-header">
                    <h1 class="page-title">月光图谱</h1>
                    <p class="page-subtitle">以你为中心，每一颗小月亮都是一位同学</p>
                    <div class="header-divider"></div>
                </div>

                <!-- 月光图谱画布 -->
                <div class="moon-graph-stage" id="graphStage">
                    <svg class="moon-graph-svg" id="graphSvg"></svg>
                    <div class="moon-graph-center" id="centerMoon">
                        <div class="center-moon-body"></div>
                        <div class="center-moon-name"><?php echo htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="center-moon-label">我</div>
                    </div>
                    <div class="moon-graph-nodes" id="nodesContainer"></div>
                </div>

                <!-- 图例说明 -->
                <div class="moon-graph-legend">
                    <?php foreach ($catDefs as $key => $cat): ?>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: <?php echo $cat['color']; ?>;"></span>
                        <span class="legend-text"><?php echo $cat['icon'] . ' ' . $cat['label']; ?></span>
                        <span class="legend-count"><?php echo $catCounts[$key]; ?> 人</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 版权页脚 -->
            <div class="app-footer">
                <p>Copyright © 2026 小若同学录管理组</p>
            </div>
        </main>
    </div>

    <!-- 悬浮提示 -->
    <div class="moon-graph-tooltip" id="moonTooltip"></div>

    <script>
    (function() {
        'use strict';

        var students   = <?php echo $studentsJson; ?>;
        var customTeachers = <?php echo $teachersJson; ?>;
        var catDefs    = <?php echo $catDefsJson; ?>;

        // 轨道半径定义
        var orbitRadii = {
            close:     130,
            classmate: 220,
            roommate:  310,
            teacher:   400
        };

        // 分类颜色
        var catColors = {};
        Object.keys(catDefs).forEach(function(k) {
            catColors[k] = catDefs[k].color;
        });
        catColors['unclassified'] = '#8a8a9a';

        // ===== 星空 =====
        function createStars() {
            var container = document.getElementById('starsBg');
            for (var i = 0; i < 120; i++) {
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

        // ===== 计算位置 =====
        function calculatePositions() {
            // 按分类分组
            var grouped = { close: [], classmate: [], roommate: [], teacher: [], unclassified: [] };

            students.forEach(function(s) {
                var cat = s.userCategory || 'unclassified';
                if (grouped[cat]) {
                    grouped[cat].push(s);
                } else {
                    grouped['unclassified'].push(s);
                }
            });

            // 添加自定义恩师
            customTeachers.forEach(function(t) {
                grouped['teacher'].push({
                    name: t.customName,
                    initial: t.customName.charAt(0),
                    customNote: t.customNote,
                    isCustom: true,
                    avatarUrl: '',
                    color: catColors['teacher'],
                    info: { '备注': t.customNote || '' }
                });
            });

            // 为每个分类计算圆形分布位置
            var positions = [];

            Object.keys(grouped).forEach(function(cat) {
                var members = grouped[cat];
                if (members.length === 0) return;

                var radius = orbitRadii[cat] || 400;
                // 未分类放在最外圈
                if (cat === 'unclassified') {
                    radius = 470;
                }

                // 如果人数太多，增加半径
                if (members.length > 12) {
                    radius += (members.length - 12) * 8;
                }

                var angleStep = (2 * Math.PI) / members.length;
                // 不同分类起始角度不同，错开排列
                var startAngles = {
                    close: -Math.PI / 2,
                    classmate: -Math.PI / 2 + 0.3,
                    roommate: -Math.PI / 2 + 0.6,
                    teacher: -Math.PI / 2 + 0.9,
                    unclassified: -Math.PI / 2 + 1.2
                };
                var startAngle = startAngles[cat] || 0;

                members.forEach(function(s, i) {
                    var angle = startAngle + i * angleStep;
                    positions.push({
                        student: s,
                        category: cat,
                        x: Math.cos(angle) * radius,
                        y: Math.sin(angle) * radius,
                        angle: angle,
                        radius: radius
                    });
                });
            });

            return positions;
        }

        // ===== 绘制轨道圆环 =====
        function drawOrbits() {
            var svg = document.getElementById('graphSvg');
            var stage = document.getElementById('graphStage');
            var w = stage.offsetWidth;
            var h = stage.offsetHeight;
            var cx = w / 2;
            var cy = h / 2;

            svg.setAttribute('width', w);
            svg.setAttribute('height', h);
            svg.innerHTML = '';

            var allRadii = [
                { r: orbitRadii.close, color: catColors.close, label: '挚友' },
                { r: orbitRadii.classmate, color: catColors.classmate, label: '同窗' },
                { r: orbitRadii.roommate, color: catColors.roommate, label: '萍水相逢' },
                { r: orbitRadii.teacher, color: catColors.teacher, label: '恩师' },
                { r: 470, color: catColors.unclassified, label: '未分类' }
            ];

            allRadii.forEach(function(orbit) {
                // 轨道圆环
                var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', cx);
                circle.setAttribute('cy', cy);
                circle.setAttribute('r', orbit.r);
                circle.setAttribute('fill', 'none');
                circle.setAttribute('stroke', orbit.color);
                circle.setAttribute('stroke-width', '1');
                circle.setAttribute('stroke-opacity', '0.12');
                circle.setAttribute('stroke-dasharray', '4 6');
                svg.appendChild(circle);

                // 轨道标签
                var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', cx + orbit.r + 8);
                text.setAttribute('y', cy + 4);
                text.setAttribute('fill', orbit.color);
                text.setAttribute('fill-opacity', '0.4');
                text.setAttribute('font-size', '11');
                text.setAttribute('font-family', 'Noto Serif SC, serif');
                text.textContent = orbit.label;
                svg.appendChild(text);
            });
        }

        // ===== 渲染月亮节点 =====
        function renderNodes() {
            var container = document.getElementById('nodesContainer');
            container.innerHTML = '';

            var positions = calculatePositions();
            var tooltip = document.getElementById('moonTooltip');

            positions.forEach(function(pos, idx) {
                var s = pos.student;
                var cat = pos.category;
                var color = catColors[cat] || '#8a8a9a';

                var node = document.createElement('div');
                node.className = 'moon-node';
                node.style.left = '50%';
                node.style.top = '50%';
                node.style.setProperty('--node-color', color);
                node.style.setProperty('--float-delay', (idx * 0.15) + 's');
                node.style.setProperty('--tx', pos.x + 'px');
                node.style.setProperty('--ty', pos.y + 'px');

                // 头像
                var avatarHtml;
                if (s.avatarUrl) {
                    avatarHtml = '<img src="' + s.avatarUrl + '" alt="' + escapeHtml(s.name) + '">';
                } else {
                    avatarHtml = escapeHtml(s.initial || s.name.charAt(0));
                }

                node.innerHTML =
                    '<div class="moon-node-body">' +
                        avatarHtml +
                    '</div>' +
                    '<div class="moon-node-name">' + escapeHtml(s.name) + '</div>';

                // 悬浮提示
                var tooltipHtml = '<div class="tooltip-name">' + escapeHtml(s.name) + '</div>';
                tooltipHtml += '<div class="tooltip-cat" style="color:' + color + ';">' + (catDefs[cat] ? catDefs[cat].label : '未分类') + '</div>';
                if (s.quote) {
                    tooltipHtml += '<div class="tooltip-quote">"' + escapeHtml(s.quote) + '"</div>';
                }
                if (s.customNote) {
                    tooltipHtml += '<div class="tooltip-quote">"' + escapeHtml(s.customNote) + '"</div>';
                }

                node.addEventListener('mouseenter', function(e) {
                    tooltip.innerHTML = tooltipHtml;
                    tooltip.classList.add('show');
                });

                node.addEventListener('mousemove', function(e) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                });

                node.addEventListener('mouseleave', function() {
                    tooltip.classList.remove('show');
                });

                container.appendChild(node);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===== 窗口调整 =====
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                drawOrbits();
                renderNodes();
            }, 200);
        });

        // ===== 初始化 =====
        createStars();
        drawOrbits();
        renderNodes();
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
