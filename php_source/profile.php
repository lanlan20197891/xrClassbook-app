<?php
/**
 * 用户资料编辑页面
 * PHP 7.4 + MySQL
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_login();

$currentUser = current_user();
$fullData = fetch_user_full_data($currentUser['id']);

if (!$fullData) {
    header('Location: logout.php');
    exit;
}

$p = $fullData['parsed'];
$constellations = [
    ''  => '请选择',
    '0' => '白羊座', '1' => '金牛座', '2' => '双子座', '3' => '巨蟹座',
    '4' => '狮子座', '5' => '处女座', '6' => '天秤座', '7' => '天蝎座',
    '8' => '射手座', '9' => '摩羯座', '10' => '水瓶座', '11' => '双鱼座',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑我的信息 · 小若同学录</title>
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
                        <div class="logo-sub">编辑我的信息</div>
                    </div>
                </div>

                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="搜索同学姓名..." id="searchInput" onfocus="this.blur();" onclick="window.location.href='index.php'">
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
                <a href="graph.php" class="nav-item">
                    <span class="nav-icon">🗺️</span>
                    <span>编辑分类</span>
                </a>
                <a href="upload.php" class="nav-item">
                    <span class="nav-icon">📷</span>
                    <span>上传图片</span>
                </a>
                <div class="nav-item active">
                    <span class="nav-icon">✏️</span>
                    <span>编辑信息</span>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="current-user">
                    <?php
                        $headUrl = $currentUser['head_url'] ?? $fullData['headUrl'];
                        $hasImage = !empty($headUrl) && (strpos($headUrl, 'http') === 0 || strpos($headUrl, '/Upload/') === 0);
                        $initial = mb_substr($currentUser['username'], 0, 1, 'UTF-8');
                    ?>
                    <div class="current-user-avatar">
                        <?php if ($hasImage): ?>
                            <img src="<?php echo htmlspecialchars($headUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="头像">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initial, ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="current-user-info">
                        <div class="current-user-name"><?php echo htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="current-user-group"><?php echo get_group_label($currentUser['group']); ?></div>
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
                    <span style="color: var(--text-primary);">编辑我的信息</span>
                </div>
                <div class="top-actions">
                    <a href="index.php" class="action-btn">← 返回同学录</a>
                </div>
            </div>

            <div class="content-area" id="contentArea">
                <div class="page-header">
                    <h1 class="page-title">编辑我的信息</h1>
                    <p class="page-subtitle">在这里修改你的个人资料，同学们会在同学录中看到最新的你</p>
                    <div class="header-divider"></div>
                </div>

                <!-- 基本信息 -->
                <div class="profile-section">
                    <h2 class="section-title">基本信息</h2>
                    <div class="profile-form-card">
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">用户名</label>
                                <input type="text" class="profile-input" value="<?php echo htmlspecialchars($fullData['username'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                <span class="profile-hint">用户名不可修改</span>
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">身份</label>
                                <input type="text" class="profile-input" value="<?php echo get_group_label($fullData['group']); ?>" disabled>
                            </div>
                        </div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">性别</label>
                                <select class="profile-select" id="gender">
                                    <option value="" <?php echo $p['gender'] === '' ? 'selected' : ''; ?>>不透露</option>
                                    <option value="0" <?php echo $p['gender'] === '0' ? 'selected' : ''; ?>>男</option>
                                    <option value="1" <?php echo $p['gender'] === '1' ? 'selected' : ''; ?>>女</option>
                                </select>
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">生日</label>
                                <input type="text" class="profile-input" id="birthday" value="<?php echo htmlspecialchars($p['birthday'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：2013-4-28">
                            </div>
                        </div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">星座</label>
                                <select class="profile-select" id="constellation">
                                    <?php foreach ($constellations as $key => $label): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $p['constellation'] === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">座右铭</label>
                                <input type="text" class="profile-input" id="motto" value="<?php echo htmlspecialchars($p['motto'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="你的座右铭">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 个性签名 -->
                <div class="profile-section">
                    <h2 class="section-title">个性签名</h2>
                    <div class="profile-form-card">
                        <div class="profile-field">
                            <textarea class="profile-textarea" id="sign" rows="3" maxlength="200" placeholder="说点什么吧..."><?php echo htmlspecialchars($p['sign'] === '这家伙很懒惰，什么都没写！' ? '' : $p['sign'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- 地区 -->
                <div class="profile-section">
                    <h2 class="section-title">地区</h2>
                    <div class="profile-form-card">
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">家乡</label>
                                <input type="text" class="profile-input" id="hometown" value="<?php echo htmlspecialchars($p['hometown'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：山东青岛">
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">现居</label>
                                <input type="text" class="profile-input" id="nowlive" value="<?php echo htmlspecialchars($p['nowlive'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：山东青岛">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 联系方式 -->
                <div class="profile-section">
                    <h2 class="section-title">联系方式</h2>
                    <div class="profile-form-card">
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">QQ</label>
                                <input type="text" class="profile-input" id="qq" value="<?php echo htmlspecialchars($p['qq'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="QQ号">
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">微信</label>
                                <input type="text" class="profile-input" id="wechat" value="<?php echo htmlspecialchars($p['wechat'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="微信号">
                            </div>
                        </div>
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">邮箱</label>
                                <input type="text" class="profile-input" id="email" value="<?php echo htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="邮箱地址">
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">电话</label>
                                <input type="text" class="profile-input" id="phone" value="<?php echo htmlspecialchars($p['phone'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="手机号">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 兴趣爱好 -->
                <div class="profile-section">
                    <h2 class="section-title">兴趣爱好</h2>
                    <div class="profile-form-card">
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">喜欢的事</label>
                                <input type="text" class="profile-input" id="like_thing" value="<?php echo htmlspecialchars($p['like_thing'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：打球、看书">
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">不喜欢的事</label>
                                <input type="text" class="profile-input" id="dislike_thing" value="<?php echo htmlspecialchars($p['dislike_thing'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：早起">
                            </div>
                        </div>
                        <div class="profile-field">
                            <label class="profile-label">擅长</label>
                            <input type="text" class="profile-input" id="good_at" value="<?php echo htmlspecialchars($p['good_at'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="如：画画、编程">
                        </div>
                    </div>
                </div>

                <!-- 头像设置 -->
                <div class="profile-section">
                    <h2 class="section-title">头像设置</h2>
                    <div class="profile-form-card">
                        <div class="profile-field">
                            <label class="profile-label">头像 URL</label>
                            <input type="text" class="profile-input" id="headUrl" value="<?php echo htmlspecialchars($fullData['headUrl'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="粘贴图片URL">
                            <span class="profile-hint">直接粘贴图片的网址链接（以 http 开头）</span>
                        </div>
                        <button class="profile-btn profile-btn-secondary" id="btnSaveAvatar">保存头像</button>
                    </div>
                </div>

                <!-- 修改密码 -->
                <div class="profile-section">
                    <h2 class="section-title">修改密码</h2>
                    <div class="profile-form-card">
                        <div class="profile-row">
                            <div class="profile-field">
                                <label class="profile-label">原密码</label>
                                <input type="password" class="profile-input" id="oldPassword" placeholder="输入当前密码">
                            </div>
                            <div class="profile-field">
                                <label class="profile-label">新密码</label>
                                <input type="password" class="profile-input" id="newPassword" placeholder="输入新密码（至少3位）">
                            </div>
                        </div>
                        <button class="profile-btn profile-btn-secondary" id="btnChangePwd">修改密码</button>
                    </div>
                </div>

                <!-- 保存按钮 -->
                <div class="profile-actions">
                    <button class="profile-btn profile-btn-primary" id="btnSaveProfile">💾 保存所有信息</button>
                    <a href="index.php" class="profile-btn profile-btn-cancel">取消</a>
                </div>
            </div>

            <!-- 版权页脚 -->
            <div class="app-footer">
                <p>Copyright © 2026 小若同学录管理组</p>
            </div>
        </main>
    </div>

    <!-- Toast -->
    <div class="graph-toast" id="profileToast"></div>

    <script>
    (function() {
        'use strict';

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
            var t = document.getElementById('profileToast');
            t.textContent = msg;
            t.classList.add('show');
            clearTimeout(t._timer);
            t._timer = setTimeout(function() { t.classList.remove('show'); }, 2500);
        }

        // ===== 保存个人信息 =====
        document.getElementById('btnSaveProfile').addEventListener('click', function() {
            var fields = {
                sign:           document.getElementById('sign').value,
                qq:             document.getElementById('qq').value,
                wechat:         document.getElementById('wechat').value,
                birthday:       document.getElementById('birthday').value,
                gender:         document.getElementById('gender').value,
                motto:          document.getElementById('motto').value,
                constellation:  document.getElementById('constellation').value,
                hometown:       document.getElementById('hometown').value,
                nowlive:        document.getElementById('nowlive').value,
                email:          document.getElementById('email').value,
                phone:          document.getElementById('phone').value,
                like_thing:     document.getElementById('like_thing').value,
                dislike_thing:  document.getElementById('dislike_thing').value,
                good_at:        document.getElementById('good_at').value
            };

            var btn = this;
            btn.disabled = true;
            btn.textContent = '保存中...';

            var formData = new FormData();
            formData.append('action', 'save_profile');
            for (var key in fields) {
                formData.append(key, fields[key]);
            }

            fetch('profile_api.php', { method: 'POST', body: formData })
                .then(function(r) { 
                    if (!r.ok) {
                        return r.text().then(function(t) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); });
                    }
                    return r.json(); 
                })
                .then(function(data) {
                    if (data.ok) {
                        showToast('✅ ' + data.msg);
                    } else {
                        showToast('❌ ' + data.msg);
                    }
                })
                .catch(function(err) {
                    showToast('❌ ' + err.message);
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.textContent = '💾 保存所有信息';
                });
        });

        // ===== 保存头像 =====
        document.getElementById('btnSaveAvatar').addEventListener('click', function() {
            var headUrl = document.getElementById('headUrl').value.trim();
            if (!headUrl) {
                showToast('请输入头像URL');
                return;
            }

            var btn = this;
            btn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'save_avatar');
            formData.append('headUrl', headUrl);

            fetch('profile_api.php', { method: 'POST', body: formData })
                .then(function(r) {
                    if (!r.ok) {
                        return r.text().then(function(t) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); });
                    }
                    return r.json();
                })
                .then(function(data) {
                    showToast(data.ok ? '✅ ' + data.msg : '❌ ' + data.msg);
                })
                .catch(function(err) {
                    showToast('❌ ' + err.message);
                })
                .finally(function() {
                    btn.disabled = false;
                });
        });

        // ===== 修改密码 =====
        document.getElementById('btnChangePwd').addEventListener('click', function() {
            var oldPwd = document.getElementById('oldPassword').value;
            var newPwd = document.getElementById('newPassword').value;

            if (!oldPwd || !newPwd) {
                showToast('请填写原密码和新密码');
                return;
            }
            if (newPwd.length < 3) {
                showToast('新密码至少3位');
                return;
            }

            var btn = this;
            btn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('oldPassword', oldPwd);
            formData.append('newPassword', newPwd);

            fetch('profile_api.php', { method: 'POST', body: formData })
                .then(function(r) {
                    if (!r.ok) {
                        return r.text().then(function(t) { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); });
                    }
                    return r.json();
                })
                .then(function(data) {
                    showToast(data.ok ? '✅ ' + data.msg : '❌ ' + data.msg);
                    if (data.ok) {
                        document.getElementById('oldPassword').value = '';
                        document.getElementById('newPassword').value = '';
                    }
                })
                .catch(function(err) {
                    showToast('❌ ' + err.message);
                })
                .finally(function() {
                    btn.disabled = false;
                });
        });

        // ===== 初始化 =====
        createStars();
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
