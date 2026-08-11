<?php
/**
 * 数据库配置文件
 * PHP 7.4 兼容
 */

// ====== 数据库配置 ======
$DB_HOST = 'localhost';
$DB_NAME = 'sersle53cq41jg0';
$DB_USER = 'sersle53cq41jg0';
$DB_PASS = 'J1J1SHIDVIK2';
$DB_CHARSET = 'utf8mb4';

// ====== 星座映射 ======
$CONSTELLATIONS = [
    '0'  => '白羊座',
    '1'  => '金牛座',
    '2'  => '双子座',
    '3'  => '巨蟹座',
    '4'  => '狮子座',
    '5'  => '处女座',
    '6'  => '天秤座',
    '7'  => '天蝎座',
    '8'  => '射手座',
    '9'  => '摩羯座',
    '10' => '水瓶座',
    '11' => '双鱼座',
];

// ====== 头像颜色池 ======
$AVATAR_COLORS = [
    '#667eea', '#f5576c', '#4facfe', '#43e97b', '#fa709a',
    '#38f9d7', '#fee140', '#a8edea', '#30cfd0', '#330867',
    '#5ee7df', '#b490ca', '#f093fb', '#4facfe', '#00f2fe',
];

/**
 * 获取数据库连接
 * @return mysqli
 */
function db_connect() {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_CHARSET;

    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    if ($conn->connect_error) {
        die('数据库连接失败: ' . $conn->connect_error);
    }

    $conn->set_charset($DB_CHARSET);
    return $conn;
}

/**
 * 根据用户组获取分类
 * @param string $group
 * @return string
 */
function get_category_by_group($group) {
    switch ($group) {
        case 'Admin':
            return 'close';    // 管理员 → 挚友
        case 'Monitor':
            return 'close';    // 班委 → 挚友
        default:
            return 'classmate'; // 默认 → 同窗
    }
}

/**
 * 根据用户组获取分类中文名
 * @param string $group
 * @return string
 */
function get_group_label($group) {
    switch ($group) {
        case 'Admin':
            return '管理员';
        case 'Monitor':
            return '班委';
        default:
            return '同学';
    }
}

/**
 * 解析 UserData JSON，提取展示信息
 * @param string|null $userDataJson
 * @return array
 */
function parse_user_data($userDataJson) {
    $result = [
        'sign'         => '这家伙很懒惰，什么都没写！',
        'photo'        => '',
        'qq'           => '',
        'wechat'       => '',
        'birthday'     => '',
        'gender'       => '',
        'motto'        => '',
        'constellation'=> '',
        'hometown'     => '',
        'nowlive'      => '',
        'email'        => '',
        'phone'        => '',
        'like_thing'   => '',
        'dislike_thing'=> '',
        'like_item'    => '',
        'dislike_item' => '',
        'good_at'      => '',
    ];

    if (empty($userDataJson)) {
        return $result;
    }

    $data = json_decode($userDataJson, true);
    if (!is_array($data)) {
        return $result;
    }

    // Public
    if (isset($data['Public'])) {
        $p = $data['Public'];
        $result['sign']  = isset($p['Sign']) ? $p['Sign'] : $result['sign'];
        $result['photo'] = isset($p['Photo']) ? $p['Photo'] : '';
    }

    // SocialAccount
    if (isset($data['SocialAccount'])) {
        $s = $data['SocialAccount'];
        $result['qq']     = isset($s['QQ']) ? $s['QQ'] : '';
        $result['wechat'] = isset($s['WeChat']) ? $s['WeChat'] : '';
    }

    // MyInfo
    if (isset($data['MyInfo'])) {
        $m = $data['MyInfo'];
        $result['birthday']      = isset($m['Birthday']) ? $m['Birthday'] : '';
        $result['gender']        = isset($m['Gender']) ? $m['Gender'] : '';
        $result['motto']         = isset($m['Motto']) ? $m['Motto'] : '';
        $result['constellation'] = isset($m['Constellation']) ? $m['Constellation'] : '';
    }

    // Location
    if (isset($data['Location'])) {
        $l = $data['Location'];
        $result['hometown'] = isset($l['Hometown']) ? $l['Hometown'] : '';
        $result['nowlive']  = isset($l['NowLive']) ? $l['NowLive'] : '';
    }

    // ContactMe
    if (isset($data['ContactMe'])) {
        $c = $data['ContactMe'];
        $result['email'] = isset($c['Email']) ? $c['Email'] : '';
        $result['phone'] = isset($c['Phone']) ? $c['Phone'] : '';
    }

    // LikeAndDislike
    if (isset($data['LikeAndDislike'])) {
        $lad = $data['LikeAndDislike'];
        $result['like_thing']    = isset($lad['MyLikeThing']) ? $lad['MyLikeThing'] : '';
        $result['dislike_thing'] = isset($lad['MyDislikeThing']) ? $lad['MyDislikeThing'] : '';
        $result['like_item']     = isset($lad['MyLikeItem']) ? $lad['MyLikeItem'] : '';
        $result['dislike_item']  = isset($lad['MyDislikeItem']) ? $lad['MyDislikeItem'] : '';
        $result['good_at']       = isset($lad['BeGoodAt']) ? $lad['BeGoodAt'] : '';
    }

    return $result;
}

/**
 * 从数据库获取所有同学数据
 * @return array
 */
function fetch_all_students() {
    global $CONSTELLATIONS, $AVATAR_COLORS;

    $conn = db_connect();

    $sql = "SELECT `ID`, `Username`, `HeadUrl`, `Status`, `Group`, `UserData` FROM `xlch_user` ORDER BY `ID` ASC";
    $result = $conn->query($sql);

    if (!$result) {
        $conn->close();
        return [];
    }

    $students = [];

    while ($row = $result->fetch_assoc()) {
        $id       = (int)$row['ID'];
        $name     = $row['Username'];
        $headUrl  = $row['HeadUrl'];
        $status   = $row['Status'];
        $group    = $row['Group'];
        $userData = parse_user_data($row['UserData']);

        // 取名字第一个字作为头像文字
        $initial = mb_substr($name, 0, 1, 'UTF-8');

        // 根据 ID 分配颜色
        $color = $AVATAR_COLORS[$id % count($AVATAR_COLORS)];

        // 分类
        $category = get_category_by_group($group);

        // 个性签名作为引语
        $quote = $userData['sign'];

        // 星座
        $constellationText = '';
        if (isset($CONSTELLATIONS[$userData['constellation']])) {
            $constellationText = $CONSTELLATIONS[$userData['constellation']];
        }

        // 性别
        $genderText = '';
        if ($userData['gender'] === '0') {
            $genderText = '男';
        } elseif ($userData['gender'] === '1') {
            $genderText = '女';
        }

        // 构建标签
        $tags = [];
        $groupLabel = get_group_label($group);
        $tags[] = $groupLabel;
        if (!empty($genderText)) {
            $tags[] = $genderText;
        }
        if (!empty($constellationText)) {
            $tags[] = $constellationText;
        }
        if (!empty($userData['good_at'])) {
            $tags[] = $userData['good_at'];
        }

        // 构建详情信息
        $info = [];

        if (!empty($constellationText)) {
            $info['星座'] = $constellationText;
        }
        if (!empty($userData['birthday'])) {
            $info['生日'] = $userData['birthday'];
        }
        if (!empty($genderText)) {
            $info['性别'] = $genderText;
        }
        if (!empty($userData['motto'])) {
            $info['座右铭'] = $userData['motto'];
        }
        if (!empty($userData['hometown'])) {
            $info['家乡'] = $userData['hometown'];
        }
        if (!empty($userData['nowlive'])) {
            $info['现居'] = $userData['nowlive'];
        }
        if (!empty($userData['like_thing'])) {
            $info['爱好'] = $userData['like_thing'];
        }
        if (!empty($userData['dislike_thing'])) {
            $info['不喜欢'] = $userData['dislike_thing'];
        }
        if (!empty($userData['good_at'])) {
            $info['擅长'] = $userData['good_at'];
        }
        if (!empty($userData['qq'])) {
            $info['QQ'] = $userData['qq'];
        }
        if (!empty($userData['wechat'])) {
            $info['微信'] = $userData['wechat'];
        }
        if (!empty($userData['email'])) {
            $info['邮箱'] = $userData['email'];
        }

        // 留言 = 个性签名
        $info['留言'] = $userData['sign'];

        // 头像图片（如果 HeadUrl 是有效 URL）
        $avatarUrl = '';
        if (!empty($headUrl) && (strpos($headUrl, 'http') === 0 || strpos($headUrl, '/Upload/') === 0)) {
            $avatarUrl = $headUrl;
        }
        // 优先使用 UserData 中的 Photo
        if (!empty($userData['photo']) && strpos($userData['photo'], '/Upload/Default/') === false) {
            $avatarUrl = $userData['photo'];
        }

        $students[] = [
            'id'        => $id,
            'name'      => $name,
            'initial'   => $initial,
            'quote'     => $quote,
            'tags'      => $tags,
            'category'  => $category,
            'color'     => $color,
            'avatarUrl' => $avatarUrl,
            'group'     => $group,
            'status'    => $status,
            'info'      => $info,
        ];
    }

    $conn->close();

    return $students;
}

// ========================================
// moon_relation 表 — 用户自定义分类关系
// ========================================

/**
 * 确保 moon_relation 表存在（自动建表）
 */
function ensure_moon_tables() {
    $conn = db_connect();

    $sql = "CREATE TABLE IF NOT EXISTS `moon_relation` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `UserID` int(11) NOT NULL COMMENT '所属用户ID（当前登录者）',
        `TargetID` int(11) NOT NULL DEFAULT 0 COMMENT '目标用户ID（xlch_user.ID），0表示自定义恩师',
        `Category` varchar(20) NOT NULL DEFAULT '同窗' COMMENT '分类: 挚友/同窗/萍水相逢/恩师',
        `PosX` float DEFAULT NULL COMMENT '图谱X坐标(%)',
        `PosY` float DEFAULT NULL COMMENT '图谱Y坐标(%)',
        `CustomName` varchar(50) NOT NULL DEFAULT '' COMMENT '自定义名称（恩师专用）',
        `CustomNote` varchar(200) NOT NULL DEFAULT '' COMMENT '备注',
        `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `UpdatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`ID`),
        UNIQUE KEY `uniq_user_target` (`UserID`, `TargetID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户自定义关系分类'";

    $conn->query($sql);
    $conn->close();
}

/**
 * 分类定义
 */
function get_category_defs() {
    return [
        'close'     => ['label' => '挚友', 'icon' => '⭐', 'color' => '#f5576c'],
        'classmate' => ['label' => '同窗', 'icon' => '👥', 'color' => '#4facfe'],
        'roommate'  => ['label' => '萍水相逢', 'icon' => '🌿', 'color' => '#43e97b'],
        'teacher'   => ['label' => '恩师', 'icon' => '📚', 'color' => '#fee140'],
    ];
}

/**
 * 获取当前用户的所有关系数据
 * @param int $userId
 * @return array
 */
function fetch_user_relations($userId) {
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT * FROM `moon_relation` WHERE `UserID` = ? ORDER BY `Category`, `ID`");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $relations = [];
    while ($row = $result->fetch_assoc()) {
        $relations[] = [
            'id'         => (int)$row['ID'],
            'targetId'   => (int)$row['TargetID'],
            'category'   => $row['Category'],
            'posX'       => $row['PosX'] !== null ? (float)$row['PosX'] : null,
            'posY'       => $row['PosY'] !== null ? (float)$row['PosY'] : null,
            'customName' => $row['CustomName'],
            'customNote' => $row['CustomNote'],
        ];
    }

    $stmt->close();
    $conn->close();

    return $relations;
}

/**
 * 保存/更新一条关系（UPSERT）
 * @param int $userId
 * @param int $targetId
 * @param string $category
 * @param float|null $posX
 * @param float|null $posY
 * @return bool
 */
function upsert_relation($userId, $targetId, $category, $posX, $posY) {
    $conn = db_connect();

    $stmt = $conn->prepare(
        "INSERT INTO `moon_relation` (`UserID`, `TargetID`, `Category`, `PosX`, `PosY`)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE `Category` = VALUES(`Category`), `PosX` = VALUES(`PosX`), `PosY` = VALUES(`PosY`)"
    );
    $stmt->bind_param('iisdd', $userId, $targetId, $category, $posX, $posY);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}

/**
 * 仅更新位置
 */
function update_relation_position($userId, $targetId, $posX, $posY) {
    $conn = db_connect();

    $stmt = $conn->prepare(
        "UPDATE `moon_relation` SET `PosX` = ?, `PosY` = ? WHERE `UserID` = ? AND `TargetID` = ?"
    );
    $stmt->bind_param('ddii', $posX, $posY, $userId, $targetId);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}

/**
 * 添加自定义恩师（不在 xlch_user 中）
 * @param int $userId
 * @param string $name
 * @param string $note
 * @return int|false 新记录ID
 */
function add_custom_teacher($userId, $name, $note) {
    $conn = db_connect();

    // TargetID=0 表示自定义，用 CustomName 区分
    $stmt = $conn->prepare(
        "INSERT INTO `moon_relation` (`UserID`, `TargetID`, `Category`, `CustomName`, `CustomNote`)
         VALUES (?, 0, 'teacher', ?, ?)"
    );
    $stmt->bind_param('iss', $userId, $name, $note);
    $ok = $stmt->execute();
    $insertId = $ok ? $stmt->insert_id : false;
    $stmt->close();
    $conn->close();

    return $insertId;
}

/**
 * 删除一条关系
 * @param int $userId
 * @param int $relationId
 * @return bool
 */
function delete_relation($userId, $relationId) {
    $conn = db_connect();

    $stmt = $conn->prepare("DELETE FROM `moon_relation` WHERE `ID` = ? AND `UserID` = ?");
    $stmt->bind_param('ii', $relationId, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}

// ========================================
// xlch_image — 时间轴数据
// ========================================

/**
 * 获取图片时间轴数据（关联相册目录）
 * @param int $limit 每个目录最多取几张，0=全部
 * @return array
 */
/**
 * 从相册标题中解析日期
 * 支持格式：
 *   2024.10.1 / 2023.8.1 / 2020.10.13  (点分隔)
 *   20250512                              (8位连续数字)
 *   2021 09 30 / 2023 9 1                (空格分隔)
 *   2020_12_30                            (下划线分隔)
 *   2023                                  (仅年份)
 *   2024-1-28 / 2024-10.1                 (混合分隔)
 * @param string $title
 * @return array|null ['year'=>int,'month'=>int,'day'=>int,'dateStr'=>'YYYY-MM-DD'] 或 null
 */
function parse_date_from_title($title) {
    if (empty($title)) {
        return null;
    }

    // 格式1: YYYY.MM.DD 或 YYYY.M.D (支持 . - _ / 空格 混合分隔)
    if (preg_match('/(20\d{2})[\.\-\/_\s]+(\d{1,2})(?:[\.\-\/_\s]+(\d{1,2}))?/', $title, $m)) {
        $year  = (int)$m[1];
        $month = (int)$m[2];
        $day   = isset($m[3]) ? (int)$m[3] : 1;
        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
            return [
                'year'    => $year,
                'month'   => $month,
                'day'     => $day,
                'dateStr' => sprintf('%04d-%02d-%02d', $year, $month, $day),
            ];
        }
    }

    // 格式2: YYYYMMDD (8位连续数字)
    if (preg_match('/(20\d{2})(\d{2})(\d{2})/', $title, $m)) {
        $year  = (int)$m[1];
        $month = (int)$m[2];
        $day   = (int)$m[3];
        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
            return [
                'year'    => $year,
                'month'   => $month,
                'day'     => $day,
                'dateStr' => sprintf('%04d-%02d-%02d', $year, $month, $day),
            ];
        }
    }

    // 格式3: 仅 YYYY (4位年份)
    if (preg_match('/(?<!\d)(20\d{2})(?!\d)/', $title, $m)) {
        $year = (int)$m[1];
        return [
            'year'    => $year,
            'month'   => 1,
            'day'     => 1,
            'dateStr' => sprintf('%04d-01-01', $year),
        ];
    }

    return null;
}

function fetch_image_timeline($limit = 0) {
    $conn = db_connect();

    // 查询所有目录
    $dirSql = "SELECT `ID`, `Name`, `Bewrite`, `AddDate` FROM `xlch_image_dir` ORDER BY `AddDate` ASC";
    $dirResult = $conn->query($dirSql);

    if (!$dirResult) {
        $conn->close();
        return [];
    }

    $dirs = [];
    while ($row = $dirResult->fetch_assoc()) {
        $dirs[] = $row;
    }

    $timeline = [];

    foreach ($dirs as $dir) {
        $dirId = (int)$dir['ID'];

        if ($limit > 0) {
            $imgSql = "SELECT `ID`, `Url`, `Name`, `AddDate` FROM `xlch_image` WHERE `DirId` = ? ORDER BY `ID` ASC LIMIT " . (int)$limit;
        } else {
            $imgSql = "SELECT `ID`, `Url`, `Name`, `AddDate` FROM `xlch_image` WHERE `DirId` = ? ORDER BY `ID` ASC";
        }

        $stmt = $conn->prepare($imgSql);
        $stmt->bind_param('i', $dirId);
        $stmt->execute();
        $imgResult = $stmt->get_result();

        $images = [];
        while ($img = $imgResult->fetch_assoc()) {
            $url = $img['Url'];
            // 补全相对路径：只有 http 开头的 URL 可预览
            if (strpos($url, 'http') !== 0) {
                continue; // 跳过相对路径
            }
            $images[] = [
                'id'   => (int)$img['ID'],
                'url'  => $url,
                'date' => $img['AddDate'],
            ];
        }

        $stmt->close();

        if (count($images) > 0) {
            // 从标题解析日期；解析失败则使用相册上传时间
            $parsed = parse_date_from_title($dir['Name']);

            if ($parsed !== null) {
                $sortDate  = $parsed['dateStr'];
                $dateLabel = $parsed['year'] . '.' . sprintf('%02d', $parsed['month']) . '.' . sprintf('%02d', $parsed['day']);
            } else {
                $sortDate  = $dir['AddDate'];
                $dateLabel = date('Y.m.d', strtotime($dir['AddDate']));
            }

            $timeline[] = [
                'dirId'     => $dirId,
                'title'     => $dir['Name'],
                'desc'      => $dir['Bewrite'],
                'date'      => $sortDate,
                'dateLabel' => $dateLabel,
                'images'    => $images,
                'count'     => count($images),
            ];
        }
    }

    $conn->close();

    // 按解析后的日期排序
    usort($timeline, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    return $timeline;
}

/**
 * 获取单个相册目录的全部数据（含所有图片）
 * @param int $dirId
 * @return array|null ['dirId','title','desc','dateLabel','images'=>[...]]
 */
function fetch_album_by_dirid($dirId) {
    $dirId = (int)$dirId;
    if ($dirId <= 0) {
        return null;
    }

    $conn = db_connect();

    // 查询目录信息
    $stmt = $conn->prepare("SELECT `ID`, `Name`, `Bewrite`, `AddDate` FROM `xlch_image_dir` WHERE `ID` = ? LIMIT 1");
    $stmt->bind_param('i', $dirId);
    $stmt->execute();
    $dirResult = $stmt->get_result();

    if ($dirResult->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return null;
    }

    $dir = $dirResult->fetch_assoc();
    $stmt->close();

    // 查询该目录下所有图片
    $stmt = $conn->prepare("SELECT `ID`, `Url`, `Name`, `AddDate` FROM `xlch_image` WHERE `DirId` = ? ORDER BY `ID` ASC");
    $stmt->bind_param('i', $dirId);
    $stmt->execute();
    $imgResult = $stmt->get_result();

    $images = [];
    while ($img = $imgResult->fetch_assoc()) {
        $url = $img['Url'];
        if (strpos($url, 'http') !== 0) {
            continue;
        }
        $images[] = [
            'id'   => (int)$img['ID'],
            'url'  => $url,
            'name' => $img['Name'],
            'date' => $img['AddDate'],
        ];
    }

    $stmt->close();
    $conn->close();

    if (count($images) === 0) {
        return null;
    }

    // 解析日期
    $parsed = parse_date_from_title($dir['Name']);
    if ($parsed !== null) {
        $dateLabel = $parsed['year'] . '.' . sprintf('%02d', $parsed['month']) . '.' . sprintf('%02d', $parsed['day']);
    } else {
        $dateLabel = date('Y.m.d', strtotime($dir['AddDate']));
    }

    return [
        'dirId'     => $dirId,
        'title'     => $dir['Name'],
        'desc'      => $dir['Bewrite'],
        'dateLabel' => $dateLabel,
        'images'    => $images,
        'count'     => count($images),
    ];
}

// ========================================
// moon_album 表 — 用户自建相册
// ========================================

/**
 * 确保 moon_album 和 moon_photo(含AlbumID) 表存在（自动建表）
 */
function ensure_moon_photo_tables() {
    $conn = db_connect();

    // 相册表
    $sql = "CREATE TABLE IF NOT EXISTS `moon_album` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `UserID` int(11) NOT NULL COMMENT '创建者ID',
        `Name` varchar(100) NOT NULL COMMENT '相册名称',
        `Description` varchar(255) NOT NULL DEFAULT '' COMMENT '相册描述',
        `AddDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`ID`),
        KEY `idx_user` (`UserID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户自建相册'";

    $conn->query($sql);

    // 图片表
    $sql = "CREATE TABLE IF NOT EXISTS `moon_photo` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `UserID` int(11) NOT NULL COMMENT '上传者ID',
        `AlbumID` int(11) NOT NULL DEFAULT 0 COMMENT '所属相册ID（0=未分类）',
        `Filename` varchar(255) NOT NULL COMMENT '存储文件名',
        `OriginalName` varchar(255) NOT NULL COMMENT '原始文件名',
        `Url` varchar(500) NOT NULL COMMENT '访问URL',
        `Title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
        `Description` text COMMENT '描述',
        `AddDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`ID`),
        KEY `idx_user` (`UserID`),
        KEY `idx_album` (`AlbumID`),
        KEY `idx_date` (`AddDate`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户上传图片'";

    $conn->query($sql);

    // 如果旧表缺少 AlbumID 列则追加
    $check = $conn->query("SHOW COLUMNS FROM `moon_photo` LIKE 'AlbumID'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `moon_photo` ADD COLUMN `AlbumID` int(11) NOT NULL DEFAULT 0 COMMENT '所属相册ID' AFTER `UserID`, ADD INDEX `idx_album` (`AlbumID`)");
    }

    $conn->close();
}

/**
 * 获取用户自建相册列表
 */
function fetch_user_albums($userId) {
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT a.*, COUNT(p.ID) AS PhotoCount FROM `moon_album` a LEFT JOIN `moon_photo` p ON a.ID = p.AlbumID WHERE a.UserID = ? GROUP BY a.ID ORDER BY a.AddDate DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $albums = [];
    while ($row = $result->fetch_assoc()) {
        $albums[] = [
            'id'          => (int)$row['ID'],
            'name'        => $row['Name'],
            'description' => $row['Description'],
            'photoCount'  => (int)$row['PhotoCount'],
            'date'        => $row['AddDate'],
            'dateLabel'   => date('Y.m.d', strtotime($row['AddDate'])),
        ];
    }

    $stmt->close();
    $conn->close();
    return $albums;
}

/**
 * 创建相册
 */
function create_album($userId, $name, $description) {
    $conn = db_connect();

    $stmt = $conn->prepare("INSERT INTO `moon_album` (`UserID`, `Name`, `Description`) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $userId, $name, $description);
    $ok = $stmt->execute();
    $insertId = $ok ? $stmt->insert_id : false;
    $stmt->close();
    $conn->close();

    return $insertId;
}

/**
 * 获取用户上传的图片
 * @param int $userId
 * @return array
 */
function fetch_user_photos($userId) {
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT * FROM `moon_photo` WHERE `UserID` = ? ORDER BY `AddDate` DESC");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $photos = [];
    while ($row = $result->fetch_assoc()) {
        $photos[] = [
            'id'          => (int)$row['ID'],
            'url'         => $row['Url'],
            'title'       => $row['Title'],
            'description' => $row['Description'],
            'albumId'     => (int)$row['AlbumID'],
            'date'        => $row['AddDate'],
            'dateLabel'   => date('Y.m.d', strtotime($row['AddDate'])),
            'originalName'=> $row['OriginalName'],
        ];
    }

    $stmt->close();
    $conn->close();

    return $photos;
}

/**
 * 获取所有用户上传的图片（用于时间轴整合）
 * @return array
 */
function fetch_all_photos() {
    $conn = db_connect();

    $sql = "SELECT p.*, u.Username FROM `moon_photo` p LEFT JOIN `xlch_user` u ON p.`UserID` = u.`ID` ORDER BY p.`AddDate` ASC";
    $result = $conn->query($sql);

    $photos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $photos[] = [
                'id'          => (int)$row['ID'],
                'url'         => $row['Url'],
                'title'       => $row['Title'],
                'description' => $row['Description'],
                'date'        => $row['AddDate'],
                'dateLabel'   => date('Y.m.d', strtotime($row['AddDate'])),
                'uploader'    => $row['Username'] ?: '未知用户',
            ];
        }
    }

    $conn->close();
    return $photos;
}

/**
 * 保存图片记录
 * @param int $userId
 * @param string $filename
 * @param string $originalName
 * @param string $url
 * @param string $title
 * @param string $description
 * @param int $albumId  所属相册ID（0=未分类）
 * @return int|false
 */
function save_photo($userId, $filename, $originalName, $url, $title, $description, $albumId = 0) {
    $conn = db_connect();

    $stmt = $conn->prepare(
        "INSERT INTO `moon_photo` (`UserID`, `AlbumID`, `Filename`, `OriginalName`, `Url`, `Title`, `Description`) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iisssss', $userId, $albumId, $filename, $originalName, $url, $title, $description);
    $ok = $stmt->execute();
    $insertId = $ok ? $stmt->insert_id : false;
    $stmt->close();
    $conn->close();

    return $insertId;
}

/**
 * 删除图片记录
 * @param int $userId
 * @param int $photoId
 * @return bool
 */
function delete_photo($userId, $photoId) {
    $conn = db_connect();

    $stmt = $conn->prepare("DELETE FROM `moon_photo` WHERE `ID` = ? AND `UserID` = ?");
    $stmt->bind_param('ii', $photoId, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}

/**
 * 获取用户上传图片整合为时间轴格式
 * @return array
 */
function fetch_photo_timeline() {
    $photos = fetch_all_photos();

    if (count($photos) === 0) {
        return [];
    }

    // 按日期分组
    $grouped = [];
    foreach ($photos as $photo) {
        $dateKey = date('Y-m-d', strtotime($photo['date']));
        if (!isset($grouped[$dateKey])) {
            $grouped[$dateKey] = [
                'title' => '同学上传 · ' . $photo['dateLabel'],
                'desc' => '同学们分享的美好瞬间',
                'date' => $photo['date'],
                'dateLabel' => $photo['dateLabel'],
                'images' => [],
            ];
        }
        $grouped[$dateKey]['images'][] = [
            'id'   => $photo['id'],
            'url'  => $photo['url'],
            'date' => $photo['date'],
        ];
    }

    $timeline = [];
    foreach ($grouped as $item) {
        $item['count'] = count($item['images']);
        $item['dirId'] = 0;
        $timeline[] = $item;
    }

    return $timeline;
}

// ========================================
// 用户资料编辑
// ========================================

/**
 * 获取单个用户的完整数据（含 UserData 原始 JSON 和解析后数据）
 * @param int $userId
 * @return array|null
 */
function fetch_user_full_data($userId) {
    $conn = db_connect();

    $stmt = $conn->prepare("SELECT `ID`, `Username`, `HeadUrl`, `Status`, `Group`, `UserData`, `Password` FROM `xlch_user` WHERE `ID` = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return null;
    }

    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    return [
        'id'       => (int)$row['ID'],
        'username' => $row['Username'],
        'headUrl'  => $row['HeadUrl'],
        'status'   => $row['Status'],
        'group'    => $row['Group'],
        'password' => $row['Password'],
        'userData' => $row['UserData'],
        'parsed'   => parse_user_data($row['UserData']),
    ];
}

/**
 * 更新用户 UserData 中的指定字段
 * @param int $userId
 * @param array $fields  扁平化的字段 [sign, qq, wechat, birthday, gender, motto, constellation, hometown, nowlive, email, phone, like_thing, dislike_thing, good_at]
 * @return bool
 */
function update_user_profile($userId, $fields) {
    $conn = db_connect();

    // 先读取现有 UserData
    $stmt = $conn->prepare("SELECT `UserData` FROM `xlch_user` WHERE `ID` = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return false;
    }

    $row = $result->fetch_assoc();
    $stmt->close();

    $data = json_decode($row['UserData'], true);
    if (!is_array($data)) {
        $data = [];
    }

    // 确保各分区存在
    if (!isset($data['Public']) || !is_array($data['Public'])) {
        $data['Public'] = [];
    }
    if (!isset($data['SocialAccount']) || !is_array($data['SocialAccount'])) {
        $data['SocialAccount'] = [];
    }
    if (!isset($data['MyInfo']) || !is_array($data['MyInfo'])) {
        $data['MyInfo'] = [];
    }
    if (!isset($data['Location']) || !is_array($data['Location'])) {
        $data['Location'] = [];
    }
    if (!isset($data['ContactMe']) || !is_array($data['ContactMe'])) {
        $data['ContactMe'] = [];
    }
    if (!isset($data['LikeAndDislike']) || !is_array($data['LikeAndDislike'])) {
        $data['LikeAndDislike'] = [];
    }

    // 更新各字段
    if (isset($fields['sign'])) {
        $data['Public']['Sign'] = $fields['sign'];
    }
    if (isset($fields['qq'])) {
        $data['SocialAccount']['QQ'] = $fields['qq'];
    }
    if (isset($fields['wechat'])) {
        $data['SocialAccount']['WeChat'] = $fields['wechat'];
    }
    if (isset($fields['birthday'])) {
        $data['MyInfo']['Birthday'] = $fields['birthday'];
    }
    if (isset($fields['gender'])) {
        $data['MyInfo']['Gender'] = $fields['gender'];
    }
    if (isset($fields['motto'])) {
        $data['MyInfo']['Motto'] = $fields['motto'];
    }
    if (isset($fields['constellation'])) {
        $data['MyInfo']['Constellation'] = $fields['constellation'];
    }
    if (isset($fields['hometown'])) {
        $data['Location']['Hometown'] = $fields['hometown'];
    }
    if (isset($fields['nowlive'])) {
        $data['Location']['NowLive'] = $fields['nowlive'];
    }
    if (isset($fields['email'])) {
        $data['ContactMe']['Email'] = $fields['email'];
    }
    if (isset($fields['phone'])) {
        $data['ContactMe']['Phone'] = $fields['phone'];
    }
    if (isset($fields['like_thing'])) {
        $data['LikeAndDislike']['MyLikeThing'] = $fields['like_thing'];
    }
    if (isset($fields['dislike_thing'])) {
        $data['LikeAndDislike']['MyDislikeThing'] = $fields['dislike_thing'];
    }
    if (isset($fields['good_at'])) {
        $data['LikeAndDislike']['BeGoodAt'] = $fields['good_at'];
    }

    $newJson = json_encode($data, JSON_UNESCAPED_UNICODE);

    $stmt2 = $conn->prepare("UPDATE `xlch_user` SET `UserData` = ? WHERE `ID` = ?");
    $stmt2->bind_param('si', $newJson, $userId);
    $ok = $stmt2->execute();
    $stmt2->close();
    $conn->close();

    return $ok;
}

/**
 * 更新用户头像 URL
 * @param int $userId
 * @param string $headUrl
 * @return bool
 */
function update_user_headurl($userId, $headUrl) {
    $conn = db_connect();

    $stmt = $conn->prepare("UPDATE `xlch_user` SET `HeadUrl` = ? WHERE `ID` = ?");
    $stmt->bind_param('si', $headUrl, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}

/**
 * 更新用户密码
 * @param int $userId
 * @param string $newPassword 明文密码
 * @return bool
 */
function update_user_password($userId, $newPassword) {
    $conn = db_connect();

    $stmt = $conn->prepare("UPDATE `xlch_user` SET `Password` = ? WHERE `ID` = ?");
    $stmt->bind_param('si', $newPassword, $userId);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();

    return $ok;
}
