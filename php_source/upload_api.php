<?php
/**
 * 图片上传 API
 * PHP 7.4 + MySQL
 *
 * 动作:
 *   POST action=upload        → 上传图片 {photo, title, description, albumId}
 *   GET  action=list          → 获取当前用户图片
 *   GET  action=list_all      → 获取所有用户图片
 *   POST action=delete        → 删除图片 {photoId}
 *   GET  action=list_albums  → 获取当前用户相册列表
 *   POST action=create_album → 创建相册 {name, description}
 */

// 输出缓冲 — 防止 config.php/auth.php 的 BOM 或 Notice 破坏 JSON header
ob_start();

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// 清空之前可能产生的 BOM/警告输出
@ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => '未登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

ensure_moon_photo_tables();

$userId = (int)$_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'upload';

switch ($action) {

    // ===== 上传图片 =====
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'msg' => '仅支持POST'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errMsg = '请选择图片';
            if (isset($_FILES['photo'])) {
                switch ($_FILES['photo']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errMsg = '文件过大';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errMsg = '请选择图片';
                        break;
                    default:
                        $errMsg = '上传出错';
                        break;
                }
            }
            echo json_encode(['ok' => false, 'msg' => $errMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $file = $_FILES['photo'];
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $albumId = isset($_POST['albumId']) ? (int)$_POST['albumId'] : 0;

        // 校验文件类型
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $file['type'];
        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['ok' => false, 'msg' => '仅支持 JPG/PNG/GIF/WebP 格式'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 校验文件大小（最大 5MB）
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'msg' => '图片大小不能超过5MB'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 生成唯一文件名
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!$ext) {
            $extMap = [
                'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                'image/png' => 'png', 'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            $ext = isset($extMap[$fileType]) ? $extMap[$fileType] : 'jpg';
        }
        $filename = 'photo_' . $userId . '_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;

        // 创建上传目录
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        if (!is_writable($uploadDir)) {
            echo json_encode(['ok' => false, 'msg' => '上传目录不可写，请检查权限'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            echo json_encode(['ok' => false, 'msg' => '文件保存失败'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $url = 'uploads/' . $filename;

        $insertId = save_photo($userId, $filename, $file['name'], $url, $title, $description, $albumId);

        if ($insertId) {
            echo json_encode([
                'ok'  => true,
                'msg' => '上传成功',
                'data' => [
                    'id'          => $insertId,
                    'url'         => $url,
                    'title'       => $title,
                    'description' => $description,
                    'dateLabel'   => date('Y.m.d'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            // 数据库保存失败，删除已上传的文件
            @unlink($destPath);
            echo json_encode(['ok' => false, 'msg' => '数据库保存失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ===== 获取当前用户图片 =====
    case 'list':
        $photos = fetch_user_photos($userId);
        echo json_encode(['ok' => true, 'photos' => $photos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    // ===== 获取所有用户图片 =====
    case 'list_all':
        $photos = fetch_all_photos();
        echo json_encode(['ok' => true, 'photos' => $photos], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    // ===== 删除图片 =====
    case 'delete':
        $photoId = isset($_POST['photoId']) ? (int)$_POST['photoId'] : 0;

        if ($photoId <= 0) {
            echo json_encode(['ok' => false, 'msg' => '无效ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = delete_photo($userId, $photoId);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        break;

    // ===== 获取相册列表 =====
    case 'list_albums':
        $albums = fetch_user_albums($userId);
        echo json_encode(['ok' => true, 'albums' => $albums], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    // ===== 创建相册 =====
    case 'create_album':
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';

        if ($name === '') {
            echo json_encode(['ok' => false, 'msg' => '相册名称不能为空'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (mb_strlen($name) > 50) {
            echo json_encode(['ok' => false, 'msg' => '相册名称不能超过50字'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $albumId = create_album($userId, $name, $description);
        if ($albumId) {
            echo json_encode([
                'ok'     => true,
                'msg'    => '相册创建成功',
                'album'  => [
                    'id'          => (int)$albumId,
                    'name'        => $name,
                    'description' => $description,
                    'photoCount'  => 0,
                    'dateLabel'   => date('Y.m.d'),
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo json_encode(['ok' => false, 'msg' => '创建失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => '未知操作'], JSON_UNESCAPED_UNICODE);
        break;
}
