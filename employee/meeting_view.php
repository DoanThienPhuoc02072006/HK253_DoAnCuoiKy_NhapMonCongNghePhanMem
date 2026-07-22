<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_employee();

$u = current_user();
$myId = $u['id'];
$id = (int)($_GET['id'] ?? 0);

/* Chỉ cho xem nếu nhân viên thực sự được mời vào cuộc họp này */
$check = $pdo->prepare("SELECT 1 FROM cuoc_hop_thanh_vien WHERE cuoc_hop_id = ? AND nhan_vien_id = ?");
$check->execute([$id, $myId]);
if (!$check->fetchColumn()) {
    header('Location: meetings.php');
    exit;
}

$stmt = $pdo->prepare("SELECT ch.*, ph.ten_phong, nv.ho_ten as nguoi_tao FROM cuoc_hop ch
                        LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                        LEFT JOIN nhan_vien nv ON nv.id = ch.nguoi_tao_id
                        WHERE ch.id = ?");
$stmt->execute([$id]);
$meeting = $stmt->fetch();
if (!$meeting) { header('Location: meetings.php'); exit; }

$attendees = $pdo->prepare("SELECT nv.* FROM cuoc_hop_thanh_vien cv
                             JOIN nhan_vien nv ON nv.id = cv.nhan_vien_id WHERE cv.cuoc_hop_id = ?");
$attendees->execute([$id]);
$attendees = $attendees->fetchAll();

$page_title = 'Chi tiết cuộc họp';
$page_subtitle = $meeting['tieu_de'];
$active = 'meetings';

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">
            <div class="breadcrumb">
                <a href="meetings.php"><i class="fa-solid fa-house"></i> Lịch họp của tôi</a> <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                <span class="cur"><?= e($meeting['tieu_de']) ?></span>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <div class="head-row">
                        <h3><?= e($meeting['tieu_de']) ?></h3>
                        <span class="badge-status <?= $meeting['trang_thai']==='Đã hủy'?'inactive':'active' ?>"><?= e($meeting['trang_thai']) ?></span>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Phòng họp</label><input value="<?= e($meeting['ten_phong'] ?? '—') ?>" disabled></div>
                        <div class="form-group"><label>Người tạo</label><input value="<?= e($meeting['nguoi_tao'] ?? '—') ?>" disabled></div>
                        <div class="form-group"><label>Thời gian bắt đầu</label><input value="<?= format_date($meeting['thoi_gian_bat_dau'], true) ?>" disabled></div>
                        <div class="form-group"><label>Thời gian kết thúc</label><input value="<?= format_date($meeting['thoi_gian_ket_thuc'], true) ?>" disabled></div>
                        <div class="form-group" style="grid-column:1/-1;"><label>Mục đích / Nội dung</label><textarea disabled><?= e($meeting['noi_dung']) ?></textarea></div>
                        <?php if ($meeting['ghi_chu']): ?>
                        <div class="form-group" style="grid-column:1/-1;"><label>Ghi chú</label><textarea disabled><?= e($meeting['ghi_chu']) ?></textarea></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions"><a href="meetings.php" class="btn">Quay lại</a></div>
                </div>

                <div class="panel">
                    <h3>Thành viên tham dự (<?= count($attendees) ?>)</h3>
                    <div class="mini-list">
                        <?php foreach ($attendees as $a): ?>
                            <div class="mini-item person">
                                <img src="<?= !empty($a['avatar']) ? '../uploads/avatars/'.e($a['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name='.urlencode($a['ho_ten']) ?>">
                                <div class="txt"><b><?= e($a['ho_ten']) ?><?= $a['id']==$myId ? ' (Bạn)' : '' ?></b><span><?= e($a['ma_nv']) ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
