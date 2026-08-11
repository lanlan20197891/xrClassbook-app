<?php
/**
 * 登录页面
 * PHP 7.4 兼容
 */
require_once __DIR__ . '/auth.php';

// 如果已经登录，直接跳转主页
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// 处理登录表单提交
$error = '';
$usernameValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $usernameValue = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

    if ($username === '' || $password === '') {
        $error = '请输入用户名和密码';
    } else {
        $result = try_login($username, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 · 小若同学录</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@300;400;500;600;700&family=Ma+Shan+Zheng&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>

    <div class="stars-bg" id="starsBg"></div>
    <div class="moonlight-overlay"></div>

    <div class="login-wrapper">
        <div class="login-card">
            <!-- 月亮装饰 -->
            <div class="login-moon"></div>

            <!-- 标题 -->
            <div class="login-header">
                <h1 class="login-title">小若同学录</h1>
                <p class="login-subtitle">月光笔记 · 珍藏青春</p>
                <div class="login-divider"></div>
            </div>

            <!-- 错误提示 -->
            <?php if ($error): ?>
            <div class="login-error">
                <span class="login-error-icon">⚠</span>
                <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endif; ?>

            <!-- 登录表单 -->
            <form class="login-form" method="POST" action="login.php" autocomplete="off">
                <div class="login-field">
                    <label class="login-label" for="username">用户名</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">👤</span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="login-input"
                            placeholder="请输入用户名"
                            value="<?php echo $usernameValue; ?>"
                            autofocus
                            required
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">密码</label>
                    <div class="login-input-wrap">
                        <span class="login-input-icon">🔒</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="请输入密码"
                            required
                        >
                        <button type="button" class="login-toggle-pwd" id="togglePwd" aria-label="显示密码">
                            <span id="toggleIcon">👁</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <span>登 录</span>
                </button>
            </form>

            <!-- 底部 -->
            <div class="login-footer">
                <p>愿此去前程似锦，再相逢依旧如故</p>
            </div>
        </div>

        <!-- 版权页脚 -->
        <div class="app-footer" style="position:fixed;bottom:0;left:0;right:0;">
            <p>Copyright © 2026 小若同学录管理组</p>
        </div>
    </div>

    <script>
        // 生成星空
        (function() {
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
        })();

        // 密码显示/隐藏切换
        document.getElementById('togglePwd').addEventListener('click', function() {
            var pwdInput = document.getElementById('password');
            var icon = document.getElementById('toggleIcon');
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                icon.textContent = '🙈';
            } else {
                pwdInput.type = 'password';
                icon.textContent = '👁';
            }
        });

        // 回车键提交
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>
