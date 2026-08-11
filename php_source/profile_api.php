<?php
/**
 * 用户资料编辑 API
 * PHP 7.4 + MySQL
 *
 * 动作:
 *   POST action=save_profile  → 保存个人信息 {sign, qq, wechat, birthday, gender, motto, constellation, hometown, nowlive, email, phone, like_thing, dislike_thing, good_at}
 *   POST action=save_avatar   → 保存头像 URL {headUrl}
 *   POST action=change_password → 修改密码 {oldPassword, newPassword}
 */

// 输出缓冲 — 防止 config.php/auth.php 的 BOM 或 Notice 破坏 JSON header
ob_start();

// 错误捕获 — 确保任何错误都返回 JSON 而非 HTML
set_exception_handler(function($e) {
    @ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['ok' => false, 'msg' => '服务器错误: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        @ob_end_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => false, 'msg' => 'PHP错误: ' . $err['message'] . ' (行' . $err['line'] . ')'], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// 清空之前可能产生的 BOM/警告输出
@ob_end_clean();

// 重新开启缓冲
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => '未登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {

    // ===== 保存个人信息 =====
    case 'save_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => '仅支持POST'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $fields = [];

        // 个性签名
        if (isset($_POST['sign'])) {
            $fields['sign'] = trim($_POST['sign']);
        }
        // 社交账号
        if (isset($_POST['qq'])) {
            $fields['qq'] = trim($_POST['qq']);
        }
        if (isset($_POST['wechat'])) {
            $fields['wechat'] = trim($_POST['wechat']);
        }
        // 个人信息
        if (isset($_POST['birthday'])) {
            $fields['birthday'] = trim($_POST['birthday']);
        }
        if (isset($_POST['gender'])) {
            $g = trim($_POST['gender']);
            if (in_array($g, ['0', '1', ''])) {
                $fields['gender'] = $g;
            }
        }
        if (isset($_POST['motto'])) {
            $fields['motto'] = trim($_POST['motto']);
        }
        if (isset($_POST['constellation'])) {
            $c = trim($_POST['constellation']);
            if (in_array($c, ['0','1','2','3','4','5','6','7','8','9','10','11',''])) {
                $fields['constellation'] = $c;
            }
        }
        // 地区
        if (isset($_POST['hometown'])) {
            $fields['hometown'] = trim($_POST['hometown']);
        }
        if (isset($_POST['nowlive'])) {
            $fields['nowlive'] = trim($_POST['nowlive']);
        }
        // 联系方式
        if (isset($_POST['email'])) {
            $fields['email'] = trim($_POST['email']);
        }
        if (isset($_POST['phone'])) {
            $fields['phone'] = trim($_POST['phone']);
        }
        // 爱好
        if (isset($_POST['like_thing'])) {
            $fields['like_thing'] = trim($_POST['like_thing']);
        }
        if (isset($_POST['dislike_thing'])) {
            $fields['dislike_thing'] = trim($_POST['dislike_thing']);
        }
        if (isset($_POST['good_at'])) {
            $fields['good_at'] = trim($_POST['good_at']);
        }

        if (count($fields) === 0) {
            echo json_encode(['ok' => false, 'msg' => '没有要更新的字段'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = update_user_profile($userId, $fields);

        if ($ok) {
            // 同步 session 中的信息
            $fullData = fetch_user_full_data($userId);
            if ($fullData) {
                $_SESSION['head_url'] = $fullData['headUrl'];
            }
            echo json_encode(['ok' => true, 'msg' => '保存成功'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'msg' => '保存失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ===== 保存头像 URL =====
    case 'save_avatar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => '仅支持POST'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $headUrl = isset($_POST['headUrl']) ? trim($_POST['headUrl']) : '';

        if ($headUrl === '') {
            echo json_encode(['ok' => false, 'msg' => 'URL不能为空'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = update_user_headurl($userId, $headUrl);

        if ($ok) {
            $_SESSION['head_url'] = $headUrl;
            echo json_encode(['ok' => true, 'msg' => '头像更新成功'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'msg' => '头像更新失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ===== 修改密码 =====
    case 'change_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => '仅支持POST'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $oldPassword = isset($_POST['oldPassword']) ? $_POST['oldPassword'] : '';
        $newPassword = isset($_POST['newPassword']) ? $_POST['newPassword'] : '';

        if ($oldPassword === '' || $newPassword === '') {
            echo json_encode(['ok' => false, 'msg' => '请填写原密码和新密码'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strlen($newPassword) < 3) {
            echo json_encode(['ok' => false, 'msg' => '新密码至少3位'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 验证原密码
        $fullData = fetch_user_full_data($userId);
        if (!$fullData) {
            echo json_encode(['ok' => false, 'msg' => '用户不存在'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($oldPassword !== $fullData['password']) {
            echo json_encode(['ok' => false, 'msg' => '原密码不正确'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = update_user_password($userId, $newPassword);

        if ($ok) {
            echo json_encode(['ok' => true, 'msg' => '密码修改成功'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'msg' => '密码修改失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => '未知操作'], JSON_UNESCAPED_UNICODE);
        break;
}
