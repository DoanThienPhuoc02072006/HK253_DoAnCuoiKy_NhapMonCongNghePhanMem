<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Thống kê';
$page_subtitle = 'Tổng quan số liệu và báo cáo';
$active = 'statistics';

$year = (int)($_GET['y'] ?? date('Y'));
$month = (int)($_GET['m'] ?? date('n'));
$deptId = $_GET['dept'] ?? '';

$monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];
$departments = $pdo->query("SELECT * FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();

/* ----- Thẻ tổng quan ----- */
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM nhan_vien WHERE chuc_vu='Nhân viên'")->fetchColumn();
$reqThisMonth = $pdo->prepare("SELECT COUNT(*) FROM yeu_cau WHERE YEAR(thoi_gian)=? AND MONTH(thoi_gian)=?");
$reqThisMonth->execute([$year, $month]);
$reqThisMonth = $reqThisMonth->fetchColumn();

$meetThisMonth = $pdo->prepare("SELECT COUNT(*) FROM cuoc_hop WHERE YEAR(thoi_gian_bat_dau)=? AND MONTH(thoi_gian_bat_dau)=?");
$meetThisMonth->execute([$year, $month]);
$meetThisMonth = $meetThisMonth->fetchColumn();

$approvedTotal = $pdo->query("SELECT COUNT(*) FROM yeu_cau WHERE trang_thai='Đã phê duyệt'")->fetchColumn();

/* ----- Biểu đồ đường: yêu cầu 6 tháng gần nhất theo trạng thái ----- */
$lineLabels = []; $lineTotal = []; $lineApproved = []; $lineRejected = [];
for ($k = 5; $k >= 0; $k--) {
    $t = mktime(0,0,0,$month-$k,1,$year);
    $y = date('Y', $t); $m = date('n', $t);
    $lineLabels[] = 'T' . $m;
    $stmt = $pdo->prepare("SELECT
        COUNT(*) total,
        SUM(trang_thai='Đã phê duyệt') approved,
        SUM(trang_thai='Từ chối') rejected
        FROM yeu_cau WHERE YEAR(thoi_gian)=? AND MONTH(thoi_gian)=?");
    $stmt->execute([$y, $m]);
    $row = $stmt->fetch();
    $lineTotal[] = (int)$row['total'];
    $lineApproved[] = (int)$row['approved'];
    $lineRejected[] = (int)$row['rejected'];
}

/* ----- Yêu cầu theo trạng thái (donut) ----- */
$statusRow = $pdo->query("SELECT
    SUM(trang_thai='Đã phê duyệt') approved,
    SUM(trang_thai='Chờ xử lý') pending,
    SUM(trang_thai='Từ chối') rejected,
    COUNT(*) total FROM yeu_cau")->fetch();

/* ----- Cuộc họp trong tháng ----- */
$meetingStats = $pdo->prepare("SELECT
    COUNT(*) total,
    SUM(trang_thai='Đã diễn ra') done,
    SUM(trang_thai='Sắp diễn ra') upcoming,
    SUM(trang_thai='Đã hủy') cancelled
    FROM cuoc_hop WHERE YEAR(thoi_gian_bat_dau)=? AND MONTH(thoi_gian_bat_dau)=?");
$meetingStats->execute([$year, $month]);
$meetingStats = $meetingStats->fetch();

/* ----- Yêu cầu theo loại ----- */
$byType = $pdo->query("SELECT loai_yeu_cau, COUNT(*) c FROM yeu_cau GROUP BY loai_yeu_cau ORDER BY c DESC LIMIT 6")->fetchAll();
$maxType = 1; foreach ($byType as $t) $maxType = max($maxType, $t['c']);
$totalAll = max(1, (int)$statusRow['total']);

/* ----- Yêu cầu theo mức độ (donut) ----- */
$byLevel = $pdo->query("SELECT muc_do, COUNT(*) c FROM yeu_cau GROUP BY muc_do")->fetchAll(PDO::FETCH_KEY_PAIR);
$high = $byLevel['Cao'] ?? 0; $medium = $byLevel['Trung bình'] ?? 0; $low = $byLevel['Thấp'] ?? 0;

/* ----- Top nhân viên nhiều yêu cầu nhất ----- */
$top = $pdo->query("SELECT nv.ho_ten, nv.ma_nv, COUNT(yc.id) so_yc FROM yeu_cau yc
                     JOIN nhan_vien nv ON nv.id = yc.nguoi_tao_id
                     GROUP BY nv.id, nv.ho_ten, nv.ma_nv ORDER BY so_yc DESC LIMIT 5")->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="stat-grid">
                <div class="stat-card">
                    <div class="top"><span class="ic bg-blue"><i class="fa-solid fa-users"></i></span></div>
                    <span class="label">Tổng số nhân viên</span><span class="value"><?= (int)$totalEmployees ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-green"><i class="fa-regular fa-calendar-check"></i></span></div>
                    <span class="label">Yêu cầu trong tháng</span><span class="value"><?= (int)$reqThisMonth ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-orange"><i class="fa-regular fa-chart-bar"></i></span></div>
                    <span class="label">Cuộc họp trong tháng</span><span class="value"><?= (int)$meetThisMonth ?></span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-purple"><i class="fa-solid fa-circle-check"></i></span></div>
                    <span class="label">Yêu cầu đã duyệt</span><span class="value"><?= (int)$approvedTotal ?></span>
                </div>
            </div>

            <form method="GET" class="filters-row">
                <div class="select-box">
                    <select name="m" onchange="this.form.submit()">
                        <?php for ($mm=1; $mm<=12; $mm++): ?>
                            <option value="<?= $mm ?>" <?= $mm===$month?'selected':'' ?>><?= $monthNames[$mm] ?> <?= $year ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="select-box">
                    <select name="dept" onchange="this.form.submit()">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $deptId==$d['id']?'selected':'' ?>><?= e($d['ten_phong_ban']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <a class="btn" style="margin-left:auto;" href="employee_export.php"><i class="fa-solid fa-download"></i> Xuất báo cáo</a>
            </form>

            <div class="grid-3" style="margin-bottom:20px;">
                <div class="panel" style="grid-column:span 2;">
                    <h3>Yêu cầu của nhân viên</h3>
                    <div class="chart-box"><canvas id="lineChart"></canvas></div>
                </div>
                <div class="panel">
                    <h3>Yêu cầu theo trạng thái</h3>
                    <div class="chart-box sm"><canvas id="statusDonut"></canvas></div>
                    <div class="donut-legend">
                        <div class="item"><span class="dot" style="background:#2563eb;"></span>Đã duyệt<span class="val"><?= (int)$statusRow['approved'] ?></span></div>
                        <div class="item"><span class="dot" style="background:#16a34a;"></span>Đang chờ xử lý<span class="val"><?= (int)$statusRow['pending'] ?></span></div>
                        <div class="item"><span class="dot" style="background:#dc2626;"></span>Từ chối<span class="val"><?= (int)$statusRow['rejected'] ?></span></div>
                    </div>
                </div>
            </div>

            <div class="grid-3" style="margin-bottom:20px;">
                <div class="panel">
                    <h3>Yêu cầu theo loại</h3>
                    <?php foreach ($byType as $t): $pct = round($t['c']/$maxType*100); ?>
                        <div class="bar-row">
                            <div class="lbl"><?= e($t['loai_yeu_cau']) ?></div>
                            <div class="track"><div class="fill" style="width:<?= $pct ?>%;"></div></div>
                            <div class="num"><?= $t['c'] ?> (<?= round($t['c']/$totalAll*100,2) ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="panel">
                    <h3>Yêu cầu theo mức độ</h3>
                    <div class="chart-box sm"><canvas id="levelDonut"></canvas></div>
                    <div class="donut-legend">
                        <div class="item"><span class="dot" style="background:#dc2626;"></span>Cao<span class="val"><?= $high ?></span></div>
                        <div class="item"><span class="dot" style="background:#f59e0b;"></span>Trung bình<span class="val"><?= $medium ?></span></div>
                        <div class="item"><span class="dot" style="background:#16a34a;"></span>Thấp<span class="val"><?= $low ?></span></div>
                    </div>
                </div>
                <div class="panel">
                    <h3>Top nhân viên có nhiều yêu cầu nhất</h3>
                    <table class="data-table">
                        <thead><tr><th>#</th><th>Nhân viên</th><th>Số yêu cầu</th></tr></thead>
                        <tbody>
                        <?php $i=1; foreach ($top as $t): ?>
                            <tr><td><?= $i++ ?></td><td><?= e($t['ho_ten']) ?><br><small style="color:var(--gray-500);"><?= e($t['ma_nv']) ?></small></td><td><b><?= $t['so_yc'] ?></b></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$top): ?><tr><td colspan="3" style="text-align:center;color:var(--gray-500);">Chưa có dữ liệu</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="footer-note">Dữ liệu được cập nhật đến <?= date('H:i') ?> ngày <?= date('d/m/Y') ?></p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($lineLabels) ?>,
        datasets: [
            { label: 'Tổng yêu cầu', data: <?= json_encode($lineTotal) ?>, borderColor: '#2563eb', backgroundColor:'#2563eb', tension:.3 },
            { label: 'Đã duyệt', data: <?= json_encode($lineApproved) ?>, borderColor: '#16a34a', backgroundColor:'#16a34a', tension:.3 },
            { label: 'Từ chối', data: <?= json_encode($lineRejected) ?>, borderColor: '#dc2626', backgroundColor:'#dc2626', tension:.3 }
        ]
    },
    options: { plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}} }
});
new Chart(document.getElementById('statusDonut'), {
    type: 'doughnut',
    data: { labels:['Đã duyệt','Đang chờ xử lý','Từ chối'],
        datasets:[{data:[<?= (int)$statusRow['approved'] ?>,<?= (int)$statusRow['pending'] ?>,<?= (int)$statusRow['rejected'] ?>], backgroundColor:['#2563eb','#16a34a','#dc2626'], borderWidth:0}] },
    options: { cutout:'70%', plugins:{legend:{display:false}} }
});
new Chart(document.getElementById('levelDonut'), {
    type: 'doughnut',
    data: { labels:['Cao','Trung bình','Thấp'],
        datasets:[{data:[<?= $high ?>,<?= $medium ?>,<?= $low ?>], backgroundColor:['#dc2626','#f59e0b','#16a34a'], borderWidth:0}] },
    options: { cutout:'70%', plugins:{legend:{display:false}} }
});
</script>
</body>
</html>
