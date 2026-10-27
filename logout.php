<?php
require_once __DIR__ . '/config/db.php';
unset($_SESSION['user']);
$_SESSION['flash_success'] = 'Bạn đã đăng xuất thành công!';
header('Location: index.php');
exit;
