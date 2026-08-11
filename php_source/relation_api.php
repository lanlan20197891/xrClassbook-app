<?php
/**
 * 关系图谱 AJAX API
 * PHP 7.4 + MySQL
 *
 * 动作:
 *   GET  action=load              → 加载当前用户所有关系 + 所有同学基础信息
 *   POST action=save              → 保存/更新分类+位置 {targetId, category, posX, posY}
 *   POST action=move              → 仅更新位置     {targetId, posX, posY}
 *   POST action=add_teacher       → 添加自定义恩师 {name, note}
 *   POST action=delete            → 删除关系       {relationId}
 */

// 输出缓冲 — 防止 config.php/auth.php 的 BOM 或 Notice 破坏 JSON header
ob_start();

// 错误捕获 — 确保任何错误都返回 JSON 而非 HTML
set_exception_handler(function($e) {
    if (!headers_sent()) {
        @ob_end_clean();
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

// 清空之前可能产生的 BOM/警告输出
@ob_end_clean();

// 重新开启缓冲，后续 echo 不会立即输出
ob_start();

header('Content-Type: application/json; charset=utf-8');

// 未登录 → 401
if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'msg' => '未登录'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 确保表存在
ensure_moon_tables();

$userId = (int)$_SESSION['user_id'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {

    // ===== 加载全部数据 =====
    case 'load':
        $students = fetch_all_students();
        $relations = fetch_user_relations($userId);

        // 简化同学数据，只传图谱需要的字段
        $simpleStudents = [];
        foreach ($students as $s) {
            $simpleStudents[] = [
                'id'        => $s['id'],
                'name'      => $s['name'],
                'initial'   => $s['initial'],
                'color'     => $s['color'],
                'avatarUrl' => $s['avatarUrl'],
                'group'     => $s['group'],
            ];
        }

        echo json_encode([
            'ok'        => true,
            'students'  => $simpleStudents,
            'relations' => $relations,
            'categories'=> get_category_defs(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;

    // ===== 保存分类+位置 =====
    case 'save':
        $targetId = isset($_POST['targetId']) ? (int)$_POST['targetId'] : 0;
        $category = isset($_POST['category']) ? $_POST['category'] : 'classmate';
        $posX     = isset($_POST['posX']) ? (float)$_POST['posX'] : null;
        $posY     = isset($_POST['posY']) ? (float)$_POST['posY'] : null;

        // 校验分类
        $validCats = ['close', 'classmate', 'roommate', 'teacher'];
        if (!in_array($category, $validCats)) {
            echo json_encode(['ok' => false, 'msg' => '无效分类'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($targetId <= 0) {
            echo json_encode(['ok' => false, 'msg' => '无效目标ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = upsert_relation($userId, $targetId, $category, $posX, $posY);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        break;

    // ===== 仅更新位置 =====
    case 'move':
        $targetId = isset($_POST['targetId']) ? (int)$_POST['targetId'] : 0;
        $posX     = isset($_POST['posX']) ? (float)$_POST['posX'] : null;
        $posY     = isset($_POST['posY']) ? (float)$_POST['posY'] : null;

        if ($targetId <= 0) {
            echo json_encode(['ok' => false, 'msg' => '无效目标ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = update_relation_position($userId, $targetId, $posX, $posY);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        break;

    // ===== 添加自定义恩师 =====
    case 'add_teacher':
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

        if ($name === '') {
            echo json_encode(['ok' => false, 'msg' => '请输入恩师姓名'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (mb_strlen($name) > 50) {
            echo json_encode(['ok' => false, 'msg' => '姓名过长'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insertId = add_custom_teacher($userId, $name, $note);
        if ($insertId) {
            echo json_encode([
                'ok'   => true,
                'data' => [
                    'id'         => $insertId,
                    'targetId'   => 0,
                    'category'   => 'teacher',
                    'customName' => $name,
                    'customNote' => $note,
                    'posX'       => null,
                    'posY'       => null,
                ],
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['ok' => false, 'msg' => '添加失败'], JSON_UNESCAPED_UNICODE);
        }
        break;

    // ===== 删除关系 =====
    case 'delete':
        $relationId = isset($_POST['relationId']) ? (int)$_POST['relationId'] : 0;

        if ($relationId <= 0) {
            echo json_encode(['ok' => false, 'msg' => '无效ID'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ok = delete_relation($userId, $relationId);
        echo json_encode(['ok' => $ok], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => '未知操作'], JSON_UNESCAPED_UNICODE);
        break;
}
