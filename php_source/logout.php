<?php
/**
 * 注销登录
 */
require_once __DIR__ . '/auth.php';

do_logout();

header('Location: login.php');
exit;
