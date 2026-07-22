<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    ghi_nhat_ky('Đăng xuất khỏi hệ thống');
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
