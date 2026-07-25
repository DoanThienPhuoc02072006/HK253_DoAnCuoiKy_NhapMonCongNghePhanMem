<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . (($_SESSION['user_role'] ?? '') === 'Trưởng phòng' ? 'index.php' : 'employee/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM nhan_vien WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($pass, $user['mat_khau'])) {
            $error = 'Email hoặc mật khẩu không chính xác.';
        } elseif ($user['trang_thai'] !== 'Hoạt động') {
            $error = 'Tài khoản của bạn đã bị vô hiệu hóa.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ho_ten'];
            $_SESSION['user_role'] = $user['chuc_vu'];
            ghi_nhat_ky('Đăng nhập vào hệ thống', $user['id']);
            if ($user['chuc_vu'] === 'Trưởng phòng') {
                header('Location: index.php');
            } else {
                header('Location: employee/index.php');
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng nhập - XYZ System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="brand"><span class="icon"><i class="fa-solid fa-users"></i></span> XYZ System</div>
        <p class="sub">Đăng nhập vào hệ thống quản lý nhân sự</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="nguyenvana@company.com" value="<?= e($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>
            <button type="submit" class="btn btn-primary">Đăng nhập</button>
        </form>

        <div class="hint">
            <b>Tài khoản Trưởng phòng:</b> nguyenvana@company.com / 123456<br>
            <b>Tài khoản Nhân viên:</b> tranthib@example.com / 123456
        </div>
    </div>
</div>
</body>
</html>
