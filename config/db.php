<?php
/**
 * config/db.php
 * Kết nối cơ sở dữ liệu MySQL bằng PDO
 * Cấu hình mặc định dùng cho XAMPP (user: root, không mật khẩu)
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'hrm_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
}
