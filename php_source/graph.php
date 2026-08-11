<?php
/**
 * 关系图谱页面 — 拖拽分类
 * PHP 7.4 + MySQL
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_login();

ensure_moon_tables();

$currentUser = current_user();
$categories  = get_category_defs();
$catJson     = json_encode($categories, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关系图谱 · 小若同学录</title>
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
                        <div class="logo-sub">关系图谱 · 心连心</div>
                    </div>
                </div>
            </div>

            <nav class="nav-section">
                <div class="nav-category">导航</div>
                <a href="index.php" class="nav-item">
                    <span class="nav-icon">📖</span>
                    <span>同学录</span>
                </a>
                <a href="moon_graph.php" class="nav-item">
                    <span class="nav-icon">🌙</span>
                    <span>月光图谱</span>
                </a>
                <div class="nav-item active">
                    <span class="nav-icon">🗺️</span>
                    <span>编辑分类</span>
                </div>
                <a href="upload.php" class="nav-item">
                    <span class="nav-icon">📷</span>
                    <span>上传图片</span>
                </a>
                <a href="profile.php" class="nav-item">
                    <span class="nav-icon">✏️</span>
                    <span>编辑信息</span>
                </a>

                <div class="nav-category">图例</div>
                <?php foreach ($categories as $key => $cat): ?>
                <div class="nav-item" style="cursor:default;">
                    <span class="nav-icon" style="color: <?php echo $cat['color']; ?>;"><?php echo $cat['icon']; ?></span>
                    <span><?php echo $cat['label']; ?></span>
                </div>
                <?php endforeach; ?>
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
                    <span class="stat" id="graphStats">加载中...</span>
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
                    <span style="color: var(--text-primary);">关系图谱</span>
                </div>
                <div class="top-actions">
                    <button class="action-btn" id="btnAddTeacher">➕ 添加恩师</button>
                    <button class="action-btn primary" id="btnReset">↻ 重置视图</button>
                </div>
            </div>

            <div class="content-area" id="contentArea">
                <div class="page-header">
                    <h1 class="page-title">关系图谱</h1>
                    <p class="page-subtitle">拖动同学卡片到对应分类区域，自由编排你的关系网</p>
                    <div class="header-divider"></div>
                </div>

                <!-- 图谱画布 -->
                <div class="graph-canvas" id="graphCanvas">
                    <!-- 4 个分类区域 -->
                    <div class="graph-zone" data-category="close" style="--zone-color: <?php echo $categories['close']['color']; ?>;">
                        <div class="zone-header">
                            <span class="zone-icon"><?php echo $categories['close']['icon']; ?></span>
                            <span class="zone-title"><?php echo $categories['close']['label']; ?></span>
                            <span class="zone-count" id="count-close">0</span>
                        </div>
                        <div class="zone-body" id="zone-close"></div>
                    </div>

                    <div class="graph-zone" data-category="classmate" style="--zone-color: <?php echo $categories['classmate']['color']; ?>;">
                        <div class="zone-header">
                            <span class="zone-icon"><?php echo $categories['classmate']['icon']; ?></span>
                            <span class="zone-title"><?php echo $categories['classmate']['label']; ?></span>
                            <span class="zone-count" id="count-classmate">0</span>
                        </div>
                        <div class="zone-body" id="zone-classmate"></div>
                    </div>

                    <div class="graph-zone" data-category="roommate" style="--zone-color: <?php echo $categories['roommate']['color']; ?>;">
                        <div class="zone-header">
                            <span class="zone-icon"><?php echo $categories['roommate']['icon']; ?></span>
                            <span class="zone-title"><?php echo $categories['roommate']['label']; ?></span>
                            <span class="zone-count" id="count-roommate">0</span>
                        </div>
                        <div class="zone-body" id="zone-roommate"></div>
                    </div>

                    <div class="graph-zone" data-category="teacher" style="--zone-color: <?php echo $categories['teacher']['color']; ?>;">
                        <div class="zone-header">
                            <span class="zone-icon"><?php echo $categories['teacher']['icon']; ?></span>
                            <span class="zone-title"><?php echo $categories['teacher']['label']; ?></span>
                            <span class="zone-count" id="count-teacher">0</span>
                        </div>
                        <div class="zone-body" id="zone-teacher"></div>
                    </div>
                </div>

                <!-- 待分类区域 -->
                <div class="unclassified-area" id="unclassifiedArea">
                    <div class="zone-header">
                        <span class="zone-icon">📋</span>
                        <span class="zone-title">未分类同学</span>
                        <span class="zone-count" id="count-unclassified">0</span>
                    </div>
                    <div class="zone-body unclassified-body" id="zone-unclassified"></div>
                </div>
            </div>

            <!-- 版权页脚 -->
            <div class="app-footer">
                <p>Copyright © 2026 小若同学录管理组</p>
            </div>
        </main>
    </div>

    <!-- 添加恩师弹窗 -->
    <div class="modal-overlay" id="teacherModal">
        <div class="modal-card">
            <div class="modal-title">添加恩师</div>
            <div class="modal-body">
                <div class="login-field">
                    <label class="login-label">恩师姓名</label>
                    <input type="text" class="login-input" id="teacherName" placeholder="请输入恩师姓名" maxlength="50">
                </div>
                <div class="login-field">
                    <label class="login-label">备注（选填）</label>
                    <input type="text" class="login-input" id="teacherNote" placeholder="如：语文老师、班主任" maxlength="200">
                </div>
                <div class="login-error" id="teacherError" style="display:none;"></div>
            </div>
            <div class="modal-actions">
                <button class="action-btn" id="teacherCancel">取消</button>
                <button class="action-btn primary" id="teacherConfirm">确认添加</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="graph-toast" id="graphToast"></div>

    <script>
    (function() {
        'use strict';

        // ===== 数据 =====
        var students = [];
        var relations = [];
        var categories = <?php echo $catJson; ?>;

        // 关系映射: targetId → relation
        var relationMap = {};

        // ===== 工具函数 =====
        function showToast(msg) {
            var t = document.getElementById('graphToast');
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.classList.remove('show'); }, 2200);
        }

        function ajax(url, method, data, callback) {
            var xhr = new XMLHttpRequest();
            xhr.open(method, url, true);
            if (method === 'POST') {
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            callback(JSON.parse(xhr.responseText));
                        } catch(e) {
                            callback({ ok: false, msg: '解析错误: ' + xhr.responseText.substring(0, 200) });
                        }
                    } else {
                        callback({ ok: false, msg: '网络错误(' + xhr.status + '): ' + xhr.responseText.substring(0, 200) });
                    }
                }
            };
            xhr.send(data);
        }

        function encodeData(obj) {
            var parts = [];
            for (var k in obj) {
                if (obj.hasOwnProperty(k)) {
                    parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(obj[k]));
                }
            }
            return parts.join('&');
        }

        // ===== 创建星点 =====
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

        // ===== 创建节点 =====
        function createNode(student, relation) {
            var node = document.createElement('div');
            node.className = 'graph-node-item';
            node.dataset.targetId = student ? student.id : 0;
            node.dataset.relationId = relation ? relation.id : 0;

            // 拖拽属性
            node.draggable = true;

            // 头像
            var avatarHtml;
            var displayName;
            var displayInitial;

            if (relation && relation.customName) {
                // 自定义恩师
                displayName = relation.customName;
                displayInitial = displayName.charAt(0);
                avatarHtml = displayInitial;
                node.dataset.customName = relation.customName;
                node.dataset.isCustom = '1';
            } else if (student) {
                displayName = student.name;
                displayInitial = student.initial;
                if (student.avatarUrl) {
                    avatarHtml = '<img src="' + student.avatarUrl + '" alt="' + student.name + '">';
                } else {
                    avatarHtml = displayInitial;
                }
            } else {
                displayName = '未知';
                avatarHtml = '?';
            }

            node.innerHTML =
                '<div class="node-avatar" style="background: ' + (student ? student.color + '20' : 'rgba(254,225,64,0.2)') + '; border-color: ' + (student ? student.color + '60' : 'rgba(254,225,64,0.6)') + ';">' +
                    avatarHtml +
                '</div>' +
                '<div class="node-name">' + escapeHtml(displayName) + '</div>';

            // 自定义恩师有删除按钮
            if (relation && relation.customName) {
                var delBtn = document.createElement('button');
                delBtn.className = 'node-delete';
                delBtn.textContent = '×';
                delBtn.title = '删除';
                delBtn.onclick = function(e) {
                    e.stopPropagation();
                    deleteRelation(relation.id);
                };
                node.appendChild(delBtn);
            }

            // 拖拽事件
            node.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    targetId: node.dataset.targetId,
                    relationId: node.dataset.relationId,
                    isCustom: node.dataset.isCustom || '0'
                }));
                node.classList.add('dragging');
            });

            node.addEventListener('dragend', function() {
                node.classList.remove('dragging');
            });

            return node;
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ===== 渲染 =====
        function render() {
            // 清空所有区域
            ['close', 'classmate', 'roommate', 'teacher', 'unclassified'].forEach(function(cat) {
                var zone = document.getElementById('zone-' + cat);
                if (zone) zone.innerHTML = '';
            });

            // 构建已分类集合
            var classifiedIds = {};

            // 放置有关系的学生
            relations.forEach(function(rel) {
                var student = null;
                if (rel.targetId > 0) {
                    student = students.find(function(s) { return s.id === rel.targetId; });
                    if (!student) return; // 找不到对应学生则跳过
                    classifiedIds[rel.targetId] = true;
                }

                var node = createNode(student, rel);
                var zone = document.getElementById('zone-' + rel.category);
                if (zone) {
                    zone.appendChild(node);
                }
            });

            // 放置未分类学生
            students.forEach(function(s) {
                if (!classifiedIds[s.id] && s.id !== <?php echo $currentUser['id']; ?>) {
                    var node = createNode(s, null);
                    document.getElementById('zone-unclassified').appendChild(node);
                }
            });

            updateCounts();
        }

        function updateCounts() {
            var counts = { close: 0, classmate: 0, roommate: 0, teacher: 0, unclassified: 0 };

            relations.forEach(function(rel) {
                if (counts.hasOwnProperty(rel.category)) {
                    counts[rel.category]++;
                }
            });

            // 未分类 = 总数 - 已分类（排除自己）
            var totalOthers = students.filter(function(s) { return s.id !== <?php echo $currentUser['id']; ?>; }).length;
            var classifiedCount = relations.filter(function(r) { return r.targetId > 0; }).length;
            counts.unclassified = totalOthers - classifiedCount;
            if (counts.unclassified < 0) counts.unclassified = 0;

            for (var key in counts) {
                if (counts.hasOwnProperty(key)) {
                    var el = document.getElementById('count-' + key);
                    if (el) el.textContent = counts[key];
                }
            }

            document.getElementById('graphStats').textContent =
                totalOthers + ' 位同学 · ' + relations.length + ' 条关系';
        }

        // ===== 拖放处理 =====
        function initDropZones() {
            document.querySelectorAll('.graph-zone').forEach(function(zone) {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    zone.classList.add('drag-over');
                });

                zone.addEventListener('dragleave', function() {
                    zone.classList.remove('drag-over');
                });

                zone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    zone.classList.remove('drag-over');

                    var data = JSON.parse(e.dataTransfer.getData('text/plain'));
                    var category = zone.dataset.category;

                    if (data.isCustom === '1') {
                        // 自定义恩师只放恩师区
                        if (category !== 'teacher') {
                            showToast('自定义恩师只能在恩师区域');
                            return;
                        }
                        // 不需要更新分类，只需位置
                        return;
                    }

                    var targetId = parseInt(data.targetId);
                    if (targetId <= 0) return;

                    // 计算相对位置（百分比）
                    var rect = zone.getBoundingClientRect();
                    var posX = ((e.clientX - rect.left) / rect.width) * 100;
                    var posY = ((e.clientY - rect.top) / rect.height) * 100;
                    posX = Math.max(5, Math.min(90, posX));
                    posY = Math.max(10, Math.min(90, posY));

                    saveRelation(targetId, category, posX, posY);
                });
            });
        }

        // ===== 保存关系 =====
        function saveRelation(targetId, category, posX, posY) {
            var data = encodeData({
                action: 'save',
                targetId: targetId,
                category: category,
                posX: posX,
                posY: posY
            });

            ajax('relation_api.php', 'POST', data, function(res) {
                if (res.ok) {
                    // 更新本地数据
                    var existing = relations.find(function(r) { return r.targetId === targetId; });
                    if (existing) {
                        existing.category = category;
                        existing.posX = posX;
                        existing.posY = posY;
                    } else {
                        relations.push({
                            id: 0,
                            targetId: targetId,
                            category: category,
                            posX: posX,
                            posY: posY,
                            customName: '',
                            customNote: ''
                        });
                    }
                    render();
                    showToast('已分类为: ' + categories[category].label);
                } else {
                    showToast('保存失败: ' + (res.msg || '未知'));
                }
            });
        }

        // ===== 删除关系 =====
        function deleteRelation(relationId) {
            if (!confirm('确定删除这条关系？')) return;

            var data = encodeData({
                action: 'delete',
                relationId: relationId
            });

            ajax('relation_api.php', 'POST', data, function(res) {
                if (res.ok) {
                    relations = relations.filter(function(r) { return r.id !== relationId; });
                    render();
                    showToast('已删除');
                } else {
                    showToast('删除失败: ' + (res.msg || '未知'));
                }
            });
        }

        // ===== 加载数据 =====
        function loadData() {
            ajax('relation_api.php?action=load', 'GET', null, function(res) {
                if (res.ok) {
                    students = res.students || [];
                    relations = res.relations || [];
                    render();
                } else {
                    showToast('加载失败: ' + (res.msg || '未知错误'));
                }
            });
        }

        // ===== 恩师弹窗 =====
        function initTeacherModal() {
            var modal = document.getElementById('teacherModal');
            var btnAdd = document.getElementById('btnAddTeacher');
            var btnCancel = document.getElementById('teacherCancel');
            var btnConfirm = document.getElementById('teacherConfirm');
            var inputName = document.getElementById('teacherName');
            var inputNote = document.getElementById('teacherNote');
            var errorEl = document.getElementById('teacherError');

            btnAdd.addEventListener('click', function() {
                modal.classList.add('show');
                inputName.value = '';
                inputNote.value = '';
                errorEl.style.display = 'none';
                inputName.focus();
            });

            btnCancel.addEventListener('click', function() {
                modal.classList.remove('show');
            });

            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('show');
                }
            });

            btnConfirm.addEventListener('click', function() {
                var name = inputName.value.trim();
                var note = inputNote.value.trim();

                if (!name) {
                    errorEl.textContent = '⚠ 请输入恩师姓名';
                    errorEl.style.display = 'flex';
                    return;
                }

                btnConfirm.disabled = true;
                btnConfirm.textContent = '添加中...';

                var data = encodeData({
                    action: 'add_teacher',
                    name: name,
                    note: note
                });

                ajax('relation_api.php', 'POST', data, function(res) {
                    btnConfirm.disabled = false;
                    btnConfirm.textContent = '确认添加';

                    if (res.ok && res.data) {
                        relations.push(res.data);
                        render();
                        modal.classList.remove('show');
                        showToast('已添加恩师: ' + name);
                    } else {
                        errorEl.textContent = '⚠ ' + (res.msg || '添加失败');
                        errorEl.style.display = 'flex';
                    }
                });
            });

            // 回车确认
            inputNote.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    btnConfirm.click();
                }
            });
            inputName.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    inputNote.focus();
                }
            });
        }

        // ===== 重置视图 =====
        function initReset() {
            document.getElementById('btnReset').addEventListener('click', function() {
                render();
                showToast('视图已重置');
            });
        }

        // ===== 初始化 =====
        createStars();
        initDropZones();
        initTeacherModal();
        initReset();
        loadData();
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
