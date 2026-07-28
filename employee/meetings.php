<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_employee();

$page_title = 'Lịch họp của tôi';
$page_subtitle = 'Danh sách các cuộc họp bạn được mời tham dự';
$active = 'meetings';

$u = current_user();
$myId = $u['id'];

$filter = $_GET['filter'] ?? 'upcoming'; // upcoming | past | all

$where = ['cv.nhan_vien_id = ?'];
$params = [$myId];
if ($filter === 'upcoming') {
    $where[] = "ch.thoi_gian_bat_dau >= NOW()";
    $where[] = "ch.trang_thai != 'Đã hủy'";
} elseif ($filter === 'past') {
    $where[] = "ch.thoi_gian_bat_dau < NOW()";
}
$whereSql = implode(' AND ', $where);
$order = $filter === 'past' ? 'DESC' : 'ASC';

$stmt = $pdo->prepare("SELECT ch.*, ph.ten_phong FROM cuoc_hop_thanh_vien cv
                        JOIN cuoc_hop ch ON ch.id = cv.cuoc_hop_id
                        LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                        WHERE $whereSql
                        ORDER BY ch.thoi_gian_bat_dau $order");
$stmt->execute($params);
$meetings = $stmt->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="panel">
                <div class="view-toggle" style="width:fit-content;margin-bottom:18px;">
                    <a href="meetings.php?filter=upcoming" class="<?= $filter==='upcoming'?'active':'' ?>">Sắp tới</a>
                    <a href="meetings.php?filter=past" class="<?= $filter==='past'?'active':'' ?>">Đã diễn ra</a>
                    <a href="meetings.php?filter=all" class="<?= $filter==='all'?'active':'' ?>">Tất cả</a>
                </div>

                <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Tiêu đề</th><th>Phòng họp</th><th>Bắt đầu</th><th>Kết thúc</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$meetings): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--gray-500);padding:30px;">Không có cuộc họp nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($meetings as $m): ?>
                        <tr>
                            <td><b><?= e($m['tieu_de']) ?></b></td>
                            <td><?= e($m['ten_phong'] ?? '—') ?></td>
                            <td><?= format_date($m['thoi_gian_bat_dau'], true) ?></td>
                            <td><?= format_date($m['thoi_gian_ket_thuc'], true) ?></td>
                            <td><span class="badge-status <?= $m['trang_thai']==='Đã hủy'?'inactive':'active' ?>"><?= e($m['trang_thai']) ?></span></td>
                            <td><a class="icon-btn view" href="meeting_view.php?id=<?= $m['id'] ?>"><i class="fa-regular fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>

