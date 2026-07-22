<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Hồ sơ tài khoản trưởng phòng';
$page_subtitle = 'Quản lý thông tin tài khoản của bạn';
$active = 'profile';

$u = current_user();
$errors = [];
$success = '';

/* ----- Cập nhật thông tin cá nhân ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'info') {
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ngay_sinh = $_POST['ngay_sinh'] ?: null;
    $sdt = trim($_POST['sdt'] ?? '');
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'Nam';
    $quoc_tich = trim($_POST['quoc_tich'] ?? 'Việt Nam');
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $tinh_trang_hon_nhan = $_POST['tinh_trang_hon_nhan'] ?? '';
    $cccd = trim($_POST['cccd'] ?? '');
    $ngay_cap = $_POST['ngay_cap'] ?: null;
    $noi_cap = trim($_POST['noi_cap'] ?? '');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');

    if ($ho_ten === '') $errors[] = 'Vui lòng nhập họ và tên.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';

    $uploadError = validate_upload_error($_FILES['avatar'] ?? null);
    if ($uploadError) $errors[] = $uploadError;

    $avatarName = $u['avatar'];
    if (!$errors && !empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) {
            $errors[] = 'Ảnh đại diện chỉ hỗ trợ định dạng JPG hoặc PNG.';
        } elseif (!ensure_upload_dir(__DIR__ . '/uploads/avatars')) {
            $errors[] = 'Máy chủ không có quyền ghi vào thư mục uploads/avatars. Vui lòng liên hệ quản trị viên.';
        } else {
            $newAvatarName = 'nv_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/uploads/avatars/' . $newAvatarName)) {
                $avatarName = $newAvatarName;
            } else {
                $errors[] = 'Không thể lưu ảnh đại diện lên máy chủ, vui lòng thử lại.';
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE nhan_vien SET ho_ten=?, email=?, ngay_sinh=?, sdt=?, gioi_tinh=?, quoc_tich=?, dia_chi=?, tinh_trang_hon_nhan=?, cccd=?, ngay_cap=?, noi_cap=?, ghi_chu=?, avatar=? WHERE id=?");
        $stmt->execute([$ho_ten, $email, $ngay_sinh, $sdt, $gioi_tinh, $quoc_tich, $dia_chi, $tinh_trang_hon_nhan, $cccd, $ngay_cap, $noi_cap, $ghi_chu, $avatarName, $u['id']]);
        $_SESSION['user_name'] = $ho_ten;
        ghi_nhat_ky('Cập nhật thông tin cá nhân');
        $success = 'Cập nhật thông tin thành công.';
        $u = current_user(); // refresh - note: static cache won't refresh; refetch manually
        $stmt2 = $pdo->prepare('SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv LEFT JOIN phong_ban pb ON pb.id=nv.phong_ban_id WHERE nv.id=?');
        $stmt2->execute([$_SESSION['user_id']]);
        $u = $stmt2->fetch();
    }
}

/* ----- Đổi mật khẩu ----- */
$pwErrors = [];
$pwSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $u['mat_khau'])) $pwErrors[] = 'Mật khẩu hiện tại không đúng.';
    if (strlen($new) < 6) $pwErrors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    if ($new !== $confirm) $pwErrors[] = 'Xác nhận mật khẩu không khớp.';

    if (!$pwErrors) {
        $stmt = $pdo->prepare('UPDATE nhan_vien SET mat_khau=? WHERE id=?');
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
        ghi_nhat_ky('Đổi mật khẩu tài khoản');
        $pwSuccess = 'Đổi mật khẩu thành công.';
    }
}

$logs = $pdo->prepare('SELECT * FROM nhat_ky_hoat_dong WHERE nhan_vien_id = ? ORDER BY thoi_gian DESC LIMIT 15');
$logs->execute([$u['id']]);
$logs = $logs->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="profile-grid">
                <div class="profile-side">
                    <div class="avatar-wrap">
                        <img id="sideAvatar" src="<?= !empty($u['avatar']) ? 'uploads/avatars/'.e($u['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&size=120&name='.urlencode($u['ho_ten']) ?>">
                    </div>
                    <h2><?= e($u['ho_ten']) ?></h2>
                    <span class="role-badge"><?= e($u['chuc_vu']) ?></span>
                    <div class="info-list">
                        <div class="row"><span class="ic"><i class="fa-solid fa-id-badge"></i> Mã nhân viên</span><b><?= e($u['ma_nv']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-envelope"></i> Email</span><b><?= e($u['email']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-phone"></i> Số điện thoại</span><b><?= e($u['sdt']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-building"></i> Phòng ban</span><b><?= e($u['ten_phong_ban'] ?? '—') ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-user-tie"></i> Chức vụ</span><b><?= e($u['chuc_vu']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-calendar"></i> Ngày tham gia</span><b><?= format_date($u['ngay_vao_lam']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-clock"></i> Trạng thái</span><b><span class="badge-status active"><?= e($u['trang_thai']) ?></span></b></div>
                    </div>
                </div>

                <div>
                    <div class="tabs">
                        <a href="#" class="tab-link active" data-tab="tab-info">Thông tin cá nhân</a>
                        <a href="#" class="tab-link" data-tab="tab-work">Thông tin công việc</a>
                        <a href="#" class="tab-link" data-tab="tab-account" id="doi-mat-khau-tab">Cài đặt tài khoản</a>
                        <a href="#" class="tab-link" data-tab="tab-logs">Nhật ký hoạt động</a>
                    </div>

                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($errors): ?><div class="alert alert-error"><?php foreach($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>

                    <div class="tab-content" id="tab-info">
                        <div class="panel">
                            <div class="head-row"><h3>Thông tin cá nhân</h3></div>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="form" value="info">
                                <div class="form-grid">
                                    <div class="form-group"><label>Họ và tên</label><input type="text" name="ho_ten" value="<?= e($u['ho_ten']) ?>" required></div>
                                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($u['email']) ?>" required></div>
                                    <div class="form-group"><label>Ngày sinh</label><input type="date" name="ngay_sinh" value="<?= e($u['ngay_sinh']) ?>"></div>
                                    <div class="form-group"><label>Số điện thoại</label><input type="text" name="sdt" value="<?= e($u['sdt']) ?>"></div>
                                    <div class="form-group">
                                        <label>Giới tính</label>
                                        <select name="gioi_tinh">
                                            <option value="Nam" <?= $u['gioi_tinh']==='Nam'?'selected':'' ?>>Nam</option>
                                            <option value="Nữ" <?= $u['gioi_tinh']==='Nữ'?'selected':'' ?>>Nữ</option>
                                            <option value="Khác" <?= $u['gioi_tinh']==='Khác'?'selected':'' ?>>Khác</option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label>Quốc tịch</label><input type="text" name="quoc_tich" value="<?= e($u['quoc_tich']) ?>"></div>
                                    <div class="form-group" style="grid-column:1/-1;"><label>Địa chỉ</label><input type="text" name="dia_chi" value="<?= e($u['dia_chi']) ?>"></div>
                                    <div class="form-group">
                                        <label>Tình trạng hôn nhân</label>
                                        <select name="tinh_trang_hon_nhan">
                                            <?php foreach (['Độc thân','Đã kết hôn','Khác'] as $opt): ?>
                                                <option value="<?= $opt ?>" <?= $u['tinh_trang_hon_nhan']===$opt?'selected':'' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group"><label>CCCD/CMND</label><input type="text" name="cccd" value="<?= e($u['cccd']) ?>"></div>
                                    <div class="form-group"><label>Ngày cấp</label><input type="date" name="ngay_cap" value="<?= e($u['ngay_cap']) ?>"></div>
                                    <div class="form-group"><label>Nơi cấp</label><input type="text" name="noi_cap" value="<?= e($u['noi_cap']) ?>"></div>
                                    <div class="form-group" style="grid-column:1/-1;"><label>Ghi chú</label><textarea name="ghi_chu"><?= e($u['ghi_chu']) ?></textarea></div>
                                    <div class="form-group">
                                        <label>Ảnh đại diện</label>
                                        <input type="file" name="avatar" accept=".jpg,.jpeg,.png" onchange="previewAvatar(this,'sideAvatar')">
                                    </div>
                                </div>
                                <div class="form-actions"><button type="submit" class="btn btn-primary">Lưu thay đổi</button></div>
                            </form>
                        </div>
                    </div>

                    <div class="tab-content" id="tab-work" style="display:none;">
                        <div class="panel">
                            <h3>Thông tin công việc</h3>
                            <div class="form-grid">
                                <div class="form-group"><label>Mã nhân viên</label><input value="<?= e($u['ma_nv']) ?>" disabled></div>
                                <div class="form-group"><label>Chức vụ</label><input value="<?= e($u['chuc_vu']) ?>" disabled></div>
                                <div class="form-group"><label>Phòng ban</label><input value="<?= e($u['ten_phong_ban'] ?? '—') ?>" disabled></div>
                                <div class="form-group"><label>Ngày vào làm</label><input value="<?= format_date($u['ngay_vao_lam']) ?>" disabled></div>
                                <div class="form-group"><label>Trạng thái</label><input value="<?= e($u['trang_thai']) ?>" disabled></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="tab-account" style="display:none;">
                        <div class="panel">
                            <h3>Đổi mật khẩu</h3>
                            <?php if ($pwSuccess): ?><div class="alert alert-success"><?= e($pwSuccess) ?></div><?php endif; ?>
                            <?php if ($pwErrors): ?><div class="alert alert-error"><?php foreach($pwErrors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>
                            <form method="POST" style="max-width:420px;">
                                <input type="hidden" name="form" value="password">
                                <div class="form-group" style="margin-bottom:16px;"><label>Mật khẩu hiện tại</label><input type="password" name="current_password" required></div>
                                <div class="form-group" style="margin-bottom:16px;"><label>Mật khẩu mới</label><input type="password" name="new_password" required></div>
                                <div class="form-group" style="margin-bottom:16px;"><label>Xác nhận mật khẩu mới</label><input type="password" name="confirm_password" required></div>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</button>
                            </form>
                        </div>
                    </div>

                    <div class="tab-content" id="tab-logs" style="display:none;">
                        <div class="panel">
                            <h3>Nhật ký hoạt động</h3>
                            <div class="mini-list">
                                <?php foreach ($logs as $lg): ?>
                                    <div class="mini-item"><div class="bar"></div>
                                        <div class="txt"><b><?= e($lg['noi_dung']) ?></b><span><?= format_date($lg['thoi_gian'], true) ?></span></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!$logs): ?><p style="color:var(--gray-500);font-size:13.5px;">Chưa có hoạt động nào.</p><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
<script>
document.querySelectorAll('.tab-link').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('.tab-link').forEach(function (t) { t.classList.remove('active'); });
        document.querySelectorAll('.tab-content').forEach(function (c) { c.style.display = 'none'; });
        this.classList.add('active');
        document.getElementById(this.dataset.tab).style.display = 'block';
    });
});
if (window.location.hash === '#doi-mat-khau') {
    document.getElementById('doi-mat-khau-tab').click();
}
</script>
</body>
</html>
