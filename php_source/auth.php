<?php
/**
 * 认证模块
 * PHP 7.4 兼容
 * 密码明文比对（不使用 MD5）
 */
if (!headers_sent() && ob_get_level() === 0) {
    ob_start();
}
session_start();

require_once __DIR__ . '/config.php';

/**
 * 检查用户是否已登录
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * 获取当前登录用户信息
 * @return array|null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'head_url' => $_SESSION['head_url'],
        'group'    => $_SESSION['group'],
    ];
}

/**
 * 尝试登录
 * @param string $username 用户名
 * @param string $password 明文密码
 * @return array ['success' => bool, 'message' => string]
 */
function try_login($username, $password) {
    $conn = db_connect();

    // 查询用户（用户名区分大小写 — utf8_bin 排序规则）
    $sql = "SELECT `ID`, `Username`, `Password`, `HeadUrl`, `Status`, `Group` FROM `xlch_user` WHERE `Username` = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '用户不存在'];
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    // 检查账号状态
    if ($user['Status'] !== 'On') {
        return ['success' => false, 'message' => '账号已被禁用，请联系管理员'];
    }

    // 明文密码比对
    if ($user['Password'] !== $password) {
        return ['success' => false, 'message' => '密码错误'];
    }

    // 登录成功 — 写入 session
    $_SESSION['user_id']   = (int)$user['ID'];
    $_SESSION['username']  = $user['Username'];
    $_SESSION['head_url']  = $user['HeadUrl'];
    $_SESSION['group']     = $user['Group'];

    // 更新登录时间和 IP（非关键路径，失败不影响登录）
    $conn2 = db_connect();
    $loginIP = $_SERVER['REMOTE_ADDR'] ?: '0.0.0.0';
    $loginDate = date('Y-m-d H:i:s');
    $token = md5($user['ID'] . $user['Username'] . time() . mt_rand(1000, 9999));
    $upd = $conn2->prepare("UPDATE `xlch_user` SET `LoginIP` = ?, `LoginDate` = ?, `Token` = ? WHERE `ID` = ?");
    if ($upd) {
        $upd->bind_param('sssi', $loginIP, $loginDate, $token, $user['ID']);
        $upd->execute();
        $upd->close();
    }
    $conn2->close();

    return ['success' => true, 'message' => '登录成功'];
}

/**
 * 注销登录
 */
function do_logout() {
    $_SESSION = [];
    session_destroy();
}

/**
 * 要求登录 — 未登录则跳转到登录页
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}
