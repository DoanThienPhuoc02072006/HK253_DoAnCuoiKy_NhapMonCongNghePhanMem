<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv LEFT JOIN phong_ban pb ON pb.id = nv.phong_ban_id WHERE nv.id = ?');
$stmt->execute([$id]);
$nv = $stmt->fetch();
if (!$nv) { header('Location: employees.php'); exit; }

$page_title = 'Chi tiết nhân viên';
$page_subtitle = 'Thông tin chi tiết nhân viên';
$active = 'employees';

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">
            <div class="breadcrumb">
                <a href="employees.php"><i class="fa-solid fa-house"></i> Danh sách nhân viên</a> <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                <span class="cur">Chi tiết nhân viên</span>
            </div>

            <div class="profile-grid">
                <div class="profile-side">
                    <div class="avatar-wrap">
                        <img src="<?= !empty($nv['avatar']) ? 'uploads/avatars/'.e($nv['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&size=120&name='.urlencode($nv['ho_ten']) ?>">
                    </div>
                    <h2><?= e($nv['ho_ten']) ?></h2>
                    <span class="role-badge"><?= e($nv['chuc_vu']) ?></span>
                    <div class="info-list">
                        <div class="row"><span class="ic"><i class="fa-solid fa-id-badge"></i> Mã NV</span><b><?= e($nv['ma_nv']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-envelope"></i> Email</span><b><?= e($nv['email']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-phone"></i> SĐT</span><b><?= e($nv['sdt']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-solid fa-building"></i> Phòng ban</span><b><?= e($nv['ten_phong_ban'] ?? '—') ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-calendar"></i> Ngày vào làm</span><b><?= format_date($nv['ngay_vao_lam']) ?></b></div>
                        <div class="row"><span class="ic"><i class="fa-regular fa-circle-check"></i> Trạng thái</span><b><span class="badge-status <?= $nv['trang_thai']==='Hoạt động'?'active':'inactive' ?>"><?= e($nv['trang_thai']) ?></span></b></div>
                    </div>
                    <div style="margin-top:20px;display:flex;gap:10px;">
                        <a href="employee_edit.php?id=<?= $nv['id'] ?>" class="btn btn-primary" style="flex:1;justify-content:center;"><i class="fa-solid fa-pen"></i> Sửa</a>
                        <a href="employees.php" class="btn" style="flex:1;justify-content:center;">Quay lại</a>
                    </div>
                </div>

                <div class="panel">
                    <h3>Thông tin cá nhân</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Giới tính</label><input value="<?= e($nv['gioi_tinh']) ?>" disabled></div>
                        <div class="form-group"><label>Ngày sinh</label><input value="<?= format_date($nv['ngay_sinh']) ?>" disabled></div>
                        <div class="form-group"><label>CCCD/CMND</label><input value="<?= e($nv['cccd'] ?: '—') ?>" disabled></div>
                        <div class="form-group"><label>Quốc tịch</label><input value="<?= e($nv['quoc_tich'] ?: '—') ?>" disabled></div>
                        <div class="form-group" style="grid-column:1/-1;"><label>Địa chỉ</label><textarea disabled><?= e($nv['dia_chi'] ?: '—') ?></textarea></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
