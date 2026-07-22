<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM nhan_vien WHERE id = ?');
$stmt->execute([$id]);
$nv = $stmt->fetch();
if (!$nv) {
    header('Location: employees.php');
    exit;
}

$page_title = 'Sửa nhân viên';
$page_subtitle = 'Cập nhật thông tin nhân viên';
$active = 'employees';
$errors = [];
$old = $nv;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $ho_ten = trim($_POST['ho_ten'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $sdt    = trim($_POST['sdt'] ?? '');
    $ma_nv  = trim($_POST['ma_nv'] ?? '');
    $phong_ban_id = $_POST['phong_ban_id'] ?? null;
    $chuc_vu = $_POST['chuc_vu'] ?? 'Nhân viên';
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'Nam';
    $ngay_sinh = $_POST['ngay_sinh'] ?: null;
    $ngay_vao_lam = $_POST['ngay_vao_lam'] ?: null;
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $trang_thai = $_POST['trang_thai'] ?? 'Hoạt động';

    if ($ho_ten === '') $errors[] = 'Vui lòng nhập họ và tên.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (!$phong_ban_id) $errors[] = 'Vui lòng chọn phòng ban.';

    if (!$errors) {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM nhan_vien WHERE (email = ? OR ma_nv = ?) AND id != ?');
        $chk->execute([$email, $ma_nv, $id]);
        if ($chk->fetchColumn() > 0) $errors[] = 'Email hoặc mã nhân viên đã được sử dụng bởi nhân viên khác.';
    }

    $uploadError = validate_upload_error($_FILES['avatar'] ?? null);
    if ($uploadError) $errors[] = $uploadError;

    $avatarName = $nv['avatar'];
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
        $stmt = $pdo->prepare("UPDATE nhan_vien SET ma_nv=?, ho_ten=?, email=?, sdt=?, chuc_vu=?, gioi_tinh=?, ngay_sinh=?, ngay_vao_lam=?, dia_chi=?, phong_ban_id=?, avatar=?, trang_thai=? WHERE id=?");
        $stmt->execute([$ma_nv, $ho_ten, $email, $sdt, $chuc_vu, $gioi_tinh, $ngay_sinh, $ngay_vao_lam, $dia_chi, $phong_ban_id, $avatarName, $trang_thai, $id]);
        ghi_nhat_ky('Cập nhật thông tin nhân viên: ' . $ho_ten);
        header('Location: employees.php?updated=1');
        exit;
    }
}

$departments = $pdo->query("SELECT * FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">
            <div class="breadcrumb">
                <a href="employees.php"><i class="fa-solid fa-house"></i> Danh sách nhân viên</a> <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                <span class="cur">Sửa nhân viên</span>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <h3 style="margin-top:0;">Thông tin nhân viên</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Họ và tên <span class="req">*</span></label>
                            <input type="text" name="ho_ten" value="<?= e($old['ho_ten']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Mã nhân viên <span class="req">*</span></label>
                            <input type="text" name="ma_nv" value="<?= e($old['ma_nv']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="req">*</span></label>
                            <input type="email" name="email" value="<?= e($old['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phòng ban <span class="req">*</span></label>
                            <select name="phong_ban_id" required>
                                <option value="">Chọn phòng ban</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($old['phong_ban_id'] == $d['id']) ? 'selected' : '' ?>><?= e($d['ten_phong_ban']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="sdt" value="<?= e($old['sdt']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Ngày sinh</label>
                            <input type="date" name="ngay_sinh" value="<?= e($old['ngay_sinh']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Chức vụ</label>
                            <select name="chuc_vu">
                                <option value="Nhân viên" <?= $old['chuc_vu'] === 'Nhân viên' ? 'selected' : '' ?>>Nhân viên</option>
                                <option value="Trưởng phòng" <?= $old['chuc_vu'] === 'Trưởng phòng' ? 'selected' : '' ?>>Trưởng phòng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ngày vào làm</label>
                            <input type="date" name="ngay_vao_lam" value="<?= e($old['ngay_vao_lam']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Giới tính</label>
                            <div class="radio-row">
                                <label><input type="radio" name="gioi_tinh" value="Nam" <?= $old['gioi_tinh']==='Nam'?'checked':'' ?>> Nam</label>
                                <label><input type="radio" name="gioi_tinh" value="Nữ" <?= $old['gioi_tinh']==='Nữ'?'checked':'' ?>> Nữ</label>
                                <label><input type="radio" name="gioi_tinh" value="Khác" <?= $old['gioi_tinh']==='Khác'?'checked':'' ?>> Khác</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="trang_thai">
                                <option value="Hoạt động" <?= $old['trang_thai']==='Hoạt động'?'selected':'' ?>>Hoạt động</option>
                                <option value="Nghỉ việc" <?= $old['trang_thai']==='Nghỉ việc'?'selected':'' ?>>Nghỉ việc</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label>Địa chỉ</label>
                            <textarea name="dia_chi"><?= e($old['dia_chi']) ?></textarea>
                        </div>
                    </div>

                    <div style="margin-top:22px;max-width:280px;">
                        <label style="font-size:13.5px;font-weight:600;margin-bottom:7px;display:block;">Ảnh đại diện</label>
                        <div class="upload-box">
                            <?php if (!empty($nv['avatar'])): ?>
                                <img id="avatarPreview" class="preview" src="uploads/avatars/<?= e($nv['avatar']) ?>">
                            <?php else: ?>
                                <div class="big-ic"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                Kéo thả ảnh vào đây hoặc click để chọn ảnh<br><small>JPG, PNG (tối đa 2MB)</small>
                                <img id="avatarPreview" style="display:none;">
                            <?php endif; ?>
                            <input type="file" name="avatar" accept=".jpg,.jpeg,.png" onchange="previewAvatar(this,'avatarPreview')">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="employees.php" class="btn">Hủy</a>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
