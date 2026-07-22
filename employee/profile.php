<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_employee();

$page_title = 'Hồ sơ cá nhân';
$page_subtitle = 'Quản lý thông tin tài khoản của bạn';
$active = 'profile';

$u = current_user();
$errors = [];
$success = '';

/* ----- Cập nhật thông tin cá nhân ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'info') {
    $sdt = trim($_POST['sdt'] ?? '');
    $ngay_sinh = $_POST['ngay_sinh'] ?: null;
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'Nam';
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $quoc_tich = trim($_POST['quoc_tich'] ?? 'Việt Nam');

    $uploadError = validate_upload_error($_FILES['avatar'] ?? null);
    if ($uploadError) $errors[] = $uploadError;

    $avatarName = $u['avatar'];
    if (!$errors && !empty($_FILES['avatar']['name'])) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) {
            $errors[] = 'Ảnh đại diện chỉ hỗ trợ định dạng JPG hoặc PNG.';
        } elseif (!ensure_upload_dir(__DIR__ . '/../uploads/avatars')) {
            $errors[] = 'Máy chủ không có quyền ghi vào thư mục uploads/avatars. Vui lòng liên hệ quản trị viên.';
        } else {
            $newAvatarName = 'nv_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../uploads/avatars/' . $newAvatarName)) {
                $avatarName = $newAvatarName;
            } else {
                $errors[] = 'Không thể lưu ảnh đại diện lên máy chủ, vui lòng thử lại.';
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("UPDATE nhan_vien SET sdt=?, ngay_sinh=?, gioi_tinh=?, dia_chi=?, quoc_tich=?, avatar=? WHERE id=?");
        $stmt->execute([$sdt, $ngay_sinh, $gioi_tinh, $dia_chi, $quoc_tich, $avatarName, $u['id']]);
        ghi_nhat_ky('Cập nhật thông tin cá nhân', $u['id']);
        $success = 'Cập nhật thông tin thành công.';

        $stmt2 = $pdo->prepare('SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv LEFT JOIN phong_ban pb ON pb.id=nv.phong_ban_id WHERE nv.id=?');
        $stmt2->execute([$u['id']]);
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
        ghi_nhat_ky('Đổi mật khẩu tài khoản', $u['id']);
        $pwSuccess = 'Đổi mật khẩu thành công.';
    }
}

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
                        <img id="sideAvatar" src="<?= !empty($u['avatar']) ? '../uploads/avatars/'.e($u['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&size=120&name='.urlencode($u['ho_ten']) ?>">
                    </div>
                    <h2><?= e($u['ho_ten']) ?></h2>
                    <span class="role-badge"><?= e($u['chuc_vu']) ?></span>
                    <div class="info-list">
                        <div class="row"><span class="ic"><i class="fa-solid fa-id-badge"></i> Mã nhân viên</span><b><?= e($u['ma_nv']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-envelope"></i> Email</span><b><?= e($u['email']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-building"></i> Phòng ban</span><b><?= e($u['ten_phong_ban'] ?? '—') ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-calendar"></i> Ngày vào làm</span><b><?= format_date($u['ngay_vao_lam']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-clock"></i> Trạng thái</span><b><span class="badge-status active"><?= e($u['trang_thai']) ?></span></b></div>
                    </div>
                    <p style="font-size:12px;color:var(--gray-500);margin-top:16px;">
                        Email, mã nhân viên, phòng ban và chức vụ do Trưởng phòng quản lý. Liên hệ Trưởng phòng nếu cần thay đổi.
                    </p>
                </div>

                <div>
                    <div class="tabs">
                        <a href="#" class="tab-link active" data-tab="tab-info">Thông tin cá nhân</a>
                        <a href="#" class="tab-link" data-tab="tab-account" id="doi-mat-khau-tab">Đổi mật khẩu</a>
                    </div>

                    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
                    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $er) echo '<div>' . e($er) . '</div>'; ?></div><?php endif; ?>

                    <div class="tab-content" id="tab-info">
                        <div class="panel">
                            <h3>Thông tin cá nhân</h3>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="form" value="info">
                                <div class="form-grid">
                                    <div class="form-group"><label>Số điện thoại</label><input type="text" name="sdt" value="<?= e($u['sdt']) ?>"></div>
                                    <div class="form-group"><label>Ngày sinh</label><input type="date" name="ngay_sinh" value="<?= e($u['ngay_sinh']) ?>"></div>
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
                                        <label>Ảnh đại diện</label>
                                        <input type="file" name="avatar" accept=".jpg,.jpeg,.png" onchange="previewAvatar(this,'sideAvatar')">
                                    </div>
                                </div>
                                <div class="form-actions"><button type="submit" class="btn btn-primary">Lưu thay đổi</button></div>
                            </form>
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
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
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
