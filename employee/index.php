<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_employee();

$page_title = 'Tổng quan';
$page_subtitle = 'Chào mừng bạn quay trở lại, ' . ($_SESSION['user_name'] ?? '') . '!';
$active = 'dashboard';

$u = current_user();
$myId = $u['id'];

/* ----- Thống kê yêu cầu của tôi ----- */
$reqStats = $pdo->prepare("SELECT
    COUNT(*) total,
    SUM(trang_thai='Chờ xử lý') pending,
    SUM(trang_thai='Đã phê duyệt') approved,
    SUM(trang_thai='Từ chối') rejected
    FROM yeu_cau WHERE nguoi_tao_id = ?");
$reqStats->execute([$myId]);
$reqStats = $reqStats->fetch();

/* ----- Cuộc họp sắp tới mà tôi được mời ----- */
$upcoming = $pdo->prepare("SELECT ch.*, ph.ten_phong FROM cuoc_hop_thanh_vien cv
                            JOIN cuoc_hop ch ON ch.id = cv.cuoc_hop_id
                            LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                            WHERE cv.nhan_vien_id = ? AND ch.thoi_gian_bat_dau >= NOW() AND ch.trang_thai != 'Đã hủy'
                            ORDER BY ch.thoi_gian_bat_dau ASC LIMIT 5");
$upcoming->execute([$myId]);
$upcoming = $upcoming->fetchAll();

/* ----- Yêu cầu gần đây của tôi ----- */
$recentRequests = $pdo->prepare("SELECT * FROM yeu_cau WHERE nguoi_tao_id = ? ORDER BY thoi_gian DESC LIMIT 5");
$recentRequests->execute([$myId]);
$recentRequests = $recentRequests->fetchAll();

function statusClassEmp($s) { if ($s === 'Đã phê duyệt') return 'approved'; if ($s === 'Từ chối') return 'rejected'; return 'pending'; }

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="top"><span class="ic bg-blue"><i class="fa-regular fa-file-lines"></i></span></div>
                    <span class="label">Tổng yêu cầu của tôi</span>
                    <span class="value"><?= (int)$reqStats['total'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-orange"><i class="fa-regular fa-clock"></i></span></div>
                    <span class="label">Đang chờ xử lý</span>
                    <span class="value"><?= (int)$reqStats['pending'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-green"><i class="fa-regular fa-circle-check"></i></span></div>
                    <span class="label">Đã phê duyệt</span>
                    <span class="value"><?= (int)$reqStats['approved'] ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-red"><i class="fa-regular fa-circle-xmark"></i></span></div>
                    <span class="label">Từ chối</span>
                    <span class="value"><?= (int)$reqStats['rejected'] ?></span>
                </div>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <div class="head-row"><h3>Yêu cầu gần đây của tôi</h3><a class="link" href="requests.php">Xem tất cả</a></div>
                    <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Mã YC</th><th>Loại yêu cầu</th><th>Thời gian</th><th>Trạng thái</th></tr></thead>
                        <tbody>
                        <?php if (!$recentRequests): ?>
                            <tr><td colspan="4" style="text-align:center;color:var(--gray-500);padding:24px;">Bạn chưa gửi yêu cầu nào.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentRequests as $r): ?>
                            <tr>
                                <td><b><?= e($r['ma_yc']) ?></b></td>
                                <td><?= e($r['loai_yeu_cau']) ?></td>
                                <td><?= format_date($r['thoi_gian'], true) ?></td>
                                <td><span class="badge-status <?= statusClassEmp($r['trang_thai']) ?>"><?= e($r['trang_thai']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div style="margin-top:16px;"><a href="requests.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Gửi yêu cầu mới</a></div>
                </div>

                <div class="panel">
                    <div class="head-row"><h3>Cuộc họp sắp tới</h3><a class="link" href="meetings.php">Xem tất cả</a></div>
                    <div class="mini-list">
                        <?php if (!$upcoming): ?>
                            <p style="color:var(--gray-500);font-size:13.5px;">Bạn chưa được mời cuộc họp nào sắp tới.</p>
                        <?php endif; ?>
                        <?php foreach ($upcoming as $m): ?>
                            <a href="meeting_view.php?id=<?= $m['id'] ?>" class="mini-item">
                                <div class="bar"></div>
                                <div class="time"><?= date('H:i', strtotime($m['thoi_gian_bat_dau'])) ?></div>
                                <div class="txt">
                                    <b><?= e($m['tieu_de']) ?></b>
                                    <span><?= e($m['ten_phong'] ?? '—') ?> · <?= format_date($m['thoi_gian_bat_dau']) ?></span>
                                </div>
                            </a>
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
