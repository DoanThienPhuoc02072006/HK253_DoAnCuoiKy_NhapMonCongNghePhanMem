<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Tổng quan';
$page_subtitle = 'Chào mừng bạn quay trở lại, ' . ($_SESSION['user_name'] ?? '') . '!';
$active = 'dashboard';

/* ----- Thống kê tổng quan (dùng khung 30 ngày gần nhất để tránh lệch ranh giới đầu/cuối tháng) ----- */
$totalRequestsMonth = $pdo->query("SELECT COUNT(*) FROM yeu_cau WHERE thoi_gian >= NOW() - INTERVAL 30 DAY")->fetchColumn();
$totalMeetingsMonth = $pdo->query("SELECT COUNT(*) FROM cuoc_hop WHERE thoi_gian_bat_dau >= NOW() - INTERVAL 30 DAY")->fetchColumn();
$approvedRequests   = $pdo->query("SELECT COUNT(*) FROM yeu_cau WHERE trang_thai = 'Đã phê duyệt'")->fetchColumn();
$totalEmployees     = $pdo->query("SELECT COUNT(*) FROM nhan_vien WHERE chuc_vu = 'Nhân viên'")->fetchColumn();
$totalRequestsAll   = max(1, $pdo->query("SELECT COUNT(*) FROM yeu_cau")->fetchColumn());
$approvedPct        = round($approvedRequests / $totalRequestsAll * 100, 2);

/* ----- Yêu cầu theo trạng thái ----- */
$byStatus = $pdo->query("SELECT trang_thai, COUNT(*) c FROM yeu_cau GROUP BY trang_thai")->fetchAll(PDO::FETCH_KEY_PAIR);
$dDuyet = $byStatus['Đã phê duyệt'] ?? 0;
$dChoXL = $byStatus['Chờ xử lý'] ?? 0;
$dTuChoi = $byStatus['Từ chối'] ?? 0;
$totalReq = max(1, $dDuyet + $dChoXL + $dTuChoi);

/* ----- Yêu cầu theo loại ----- */
$byType = $pdo->query("SELECT loai_yeu_cau, COUNT(*) c FROM yeu_cau GROUP BY loai_yeu_cau ORDER BY c DESC LIMIT 6")->fetchAll();
$maxType = 1;
foreach ($byType as $t) { $maxType = max($maxType, $t['c']); }

/* ----- Lịch phòng họp sắp tới ----- */
$upcoming = $pdo->query("SELECT ch.*, ph.ten_phong FROM cuoc_hop ch
                          LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                          WHERE ch.thoi_gian_bat_dau >= NOW()
                          ORDER BY ch.thoi_gian_bat_dau ASC LIMIT 4")->fetchAll();

/* ----- Nhân viên mới ----- */
$newEmployees = $pdo->query("SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv
                              LEFT JOIN phong_ban pb ON pb.id = nv.phong_ban_id
                              WHERE nv.chuc_vu='Nhân viên'
                              ORDER BY nv.ngay_vao_lam DESC, nv.id DESC LIMIT 3")->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="top"><span class="ic bg-blue"><i class="fa-regular fa-calendar-check"></i></span></div>
                    <span class="label">Yêu cầu trong tháng</span>
                    <span class="value"><?= (int)$totalRequestsMonth ?></span>
                    <span class="delta"><i class="fa-solid fa-arrow-up"></i> so với tháng trước</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-orange"><i class="fa-regular fa-chart-bar"></i></span></div>
                    <span class="label">Cuộc họp trong tháng</span>
                    <span class="value"><?= (int)$totalMeetingsMonth ?></span>
                    <span class="delta"><i class="fa-solid fa-arrow-up"></i> so với tháng trước</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-purple"><i class="fa-solid fa-circle-check"></i></span></div>
                    <span class="label">Yêu cầu đã duyệt</span>
                    <span class="value"><?= (int)$approvedRequests ?></span>
                    <span class="delta"><?= $approvedPct ?>% tổng yêu cầu</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-green"><i class="fa-solid fa-users"></i></span></div>
                    <span class="label">Nhân viên</span>
                    <span class="value"><?= (int)$totalEmployees ?></span>
                    <span class="delta"><i class="fa-solid fa-arrow-up"></i> so với tháng trước</span>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom:20px;">
                <div class="panel">
                    <h3>Yêu cầu theo trạng thái</h3>
                    <div class="donut-wrap">
                        <div class="chart-box"><canvas id="statusChart"></canvas></div>
                        <div class="donut-legend" style="flex:1;">
                            <div class="item"><span class="dot" style="background:#2563eb;"></span> Đã duyệt <span class="val"><?= $dDuyet ?> (<?= round($dDuyet/$totalReq*100,2) ?>%)</span></div>
                            <div class="item"><span class="dot" style="background:#16a34a;"></span> Đang chờ xử lý <span class="val"><?= $dChoXL ?> (<?= round($dChoXL/$totalReq*100,2) ?>%)</span></div>
                            <div class="item"><span class="dot" style="background:#dc2626;"></span> Từ chối <span class="val"><?= $dTuChoi ?> (<?= round($dTuChoi/$totalReq*100,2) ?>%)</span></div>
                            <div style="font-size:22px;font-weight:700;margin-top:6px;"><?= $totalReq ?><div style="font-size:12px;font-weight:400;color:var(--gray-500);">Tổng số</div></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="head-row"><h3>Lịch phòng họp sắp tới</h3><a class="link" href="calendar.php">Xem tất cả</a></div>
                    <div class="mini-list">
                        <?php if (!$upcoming): ?>
                            <p style="color:var(--gray-500);font-size:13.5px;">Chưa có cuộc họp nào sắp diễn ra.</p>
                        <?php endif; ?>
                        <?php foreach ($upcoming as $m): ?>
                            <a href="meeting_view.php?id=<?= $m['id'] ?>" class="mini-item" style="cursor:pointer;">
                                <div class="bar"></div>
                                <div class="time"><?= date('H:i', strtotime($m['thoi_gian_bat_dau'])) ?></div>
                                <div class="txt">
                                    <b><?= e($m['tieu_de']) ?></b>
                                    <span><?= e($m['ten_phong'] ?? 'Chưa chọn phòng') ?> · <?= format_date($m['thoi_gian_bat_dau']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid-2">
                <div class="panel">
                    <h3>Yêu cầu theo loại</h3>
                    <?php foreach ($byType as $t): $pct = round($t['c'] / $maxType * 100); ?>
                        <div class="bar-row">
                            <div class="lbl"><?= e($t['loai_yeu_cau']) ?></div>
                            <div class="track"><div class="fill" style="width:<?= $pct ?>%;"></div></div>
                            <div class="num"><?= $t['c'] ?> (<?= round($t['c']/$totalRequestsAll*100,2) ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="panel">
                    <div class="head-row"><h3>Nhân viên mới</h3><a class="link" href="employees.php">Xem tất cả</a></div>
                    <div class="mini-list">
                        <?php foreach ($newEmployees as $nv): ?>
                            <div class="mini-item person">
                                <img src="<?= !empty($nv['avatar']) ? 'uploads/avatars/'.e($nv['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name='.urlencode($nv['ho_ten']) ?>" alt="">
                                <div class="txt">
                                    <b><?= e($nv['ho_ten']) ?></b>
                                    <span><?= e($nv['ma_nv']) ?> · <?= e($nv['ten_phong_ban'] ?? '—') ?></span>
                                </div>
                                <div class="meta"><?= format_date($nv['ngay_vao_lam']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Đã duyệt', 'Đang chờ xử lý', 'Từ chối'],
        datasets: [{
            data: [<?= $dDuyet ?>, <?= $dChoXL ?>, <?= $dTuChoi ?>],
            backgroundColor: ['#2563eb', '#16a34a', '#dc2626'],
            borderWidth: 0
        }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } } }
});
</script>
</body>
</html>
