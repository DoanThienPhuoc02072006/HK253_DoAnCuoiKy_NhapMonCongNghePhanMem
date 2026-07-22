<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Lịch phòng họp';
$page_subtitle = 'Lịch phòng họp của phòng ban';
$active = 'calendar';

$flashWarning = $_SESSION['flash_warning'] ?? null;
unset($_SESSION['flash_warning']);

$year = (int)($_GET['y'] ?? date('Y'));
$month = (int)($_GET['m'] ?? date('n'));
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDayOfMonth = mktime(0,0,0,$month,1,$year);
$daysInMonth = (int)date('t', $firstDayOfMonth);
$startWeekday = (int)date('N', $firstDayOfMonth); // 1 (Mon) .. 7 (Sun)

$monthNames = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];

/* Lấy toàn bộ cuộc họp trong tháng */
$stmt = $pdo->prepare("SELECT ch.*, ph.ten_phong FROM cuoc_hop ch
                        LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                        WHERE YEAR(ch.thoi_gian_bat_dau) = ? AND MONTH(ch.thoi_gian_bat_dau) = ?
                        ORDER BY ch.thoi_gian_bat_dau ASC");
$stmt->execute([$year, $month]);
$meetings = $stmt->fetchAll();

$byDay = [];
$colorCycle = ['c1','c2','c3','c4','c5'];
$i = 0;
foreach ($meetings as $mt) {
    $d = (int)date('j', strtotime($mt['thoi_gian_bat_dau']));
    $mt['_color'] = $colorCycle[$i % count($colorCycle)];
    $byDay[$d][] = $mt;
    $i++;
}

$today = date('Y-m-d');

/* Cuộc họp sắp tới (toàn hệ thống, không giới hạn theo tháng đang xem) */
$upcoming = $pdo->query("SELECT ch.*, ph.ten_phong FROM cuoc_hop ch
                          LEFT JOIN phong_hop ph ON ph.id = ch.phong_hop_id
                          WHERE ch.thoi_gian_bat_dau >= NOW() ORDER BY ch.thoi_gian_bat_dau ASC LIMIT 4")->fetchAll();

/* Ngày có cuộc họp trong tháng (cho mini calendar) */
$daysWithMeeting = array_keys($byDay);

$prevLink = "calendar.php?y=" . ($month==1?$year-1:$year) . "&m=" . ($month==1?12:$month-1);
$nextLink = "calendar.php?y=" . ($month==12?$year+1:$year) . "&m=" . ($month==12?1:$month+1);

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <?php if ($flashWarning): ?>
                <div class="alert alert-warning" data-autohide><i class="fa-solid fa-triangle-exclamation"></i> <?= e($flashWarning) ?></div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="panel">
                    <div class="calendar-toolbar">
                        <a href="<?= $prevLink ?>" class="nav-btn"><i class="fa-solid fa-chevron-left"></i></a>
                        <a href="<?= $nextLink ?>" class="nav-btn"><i class="fa-solid fa-chevron-right"></i></a>
                        <a href="calendar.php" class="btn" style="padding:8px 14px;">Hôm nay</a>
                        <h2><?= $monthNames[$month] ?>, <?= $year ?></h2>
                        <a href="meeting_create.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tạo cuộc họp</a>
                    </div>

                    <div class="cal-grid">
                        <?php foreach (['T2','T3','T4','T5','T6','T7','CN'] as $d): ?>
                            <div class="cal-head"><?= $d ?></div>
                        <?php endforeach; ?>

                        <?php
                        // Ô trống trước ngày 1
                        $leading = $startWeekday - 1;
                        for ($k = 0; $k < $leading; $k++) {
                            $prevMonthDays = (int)date('t', mktime(0,0,0,$month-1,1,$year));
                            $d = $prevMonthDays - $leading + $k + 1;
                            echo '<div class="cal-cell other-month"><div class="daynum">' . $d . '</div></div>';
                        }
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                            $isToday = $dateStr === $today;
                            echo '<div class="cal-cell ' . ($isToday ? 'today' : '') . '">';
                            echo '<div class="daynum">' . $d . '</div>';
                            if (!empty($byDay[$d])) {
                                foreach ($byDay[$d] as $mt) {
                                    echo '<a class="cal-event ' . $mt['_color'] . '" href="meeting_view.php?id=' . $mt['id'] . '" title="' . e($mt['tieu_de']) . '">';
                                    echo '<b>' . date('H:i', strtotime($mt['thoi_gian_bat_dau'])) . ' ' . e($mt['tieu_de']) . '</b>';
                                    echo e($mt['ten_phong'] ?? '');
                                    echo '</a>';
                                }
                            }
                            echo '</div>';
                        }
                        // Ô trống sau ngày cuối tháng để đủ hàng
                        $trailing = (7 - (($leading + $daysInMonth) % 7)) % 7;
                        for ($k = 1; $k <= $trailing; $k++) {
                            echo '<div class="cal-cell other-month"><div class="daynum">' . $k . '</div></div>';
                        }
                        ?>
                    </div>
                </div>

                <div>
                    <div class="panel" style="margin-bottom:20px;">
                        <h3><i class="fa-regular fa-calendar"></i> Lịch tháng <?= $month ?>, <?= $year ?></h3>
                        <table class="mini-cal">
                            <thead><tr><th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th><th>CN</th></tr></thead>
                            <tbody>
                            <?php
                            $cellIndex = 0;
                            echo '<tr>';
                            for ($k = 0; $k < $leading; $k++) {
                                $prevMonthDays = (int)date('t', mktime(0,0,0,$month-1,1,$year));
                                $d = $prevMonthDays - $leading + $k + 1;
                                echo '<td class="other">' . $d . '</td>';
                                $cellIndex++;
                            }
                            for ($d = 1; $d <= $daysInMonth; $d++) {
                                if ($cellIndex > 0 && $cellIndex % 7 === 0) echo '</tr><tr>';
                                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                $cls = $dateStr === $today ? 'today' : '';
                                echo '<td class="' . $cls . '">' . $d;
                                if (in_array($d, $daysWithMeeting)) echo '<div class="dot"></div>';
                                echo '</td>';
                                $cellIndex++;
                            }
                            echo '</tr>';
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="panel">
                        <div class="head-row"><h3>Cuộc họp sắp tới</h3><a class="link" href="statistics.php">Xem tất cả</a></div>
                        <div class="mini-list">
                            <?php if (!$upcoming): ?>
                                <p style="color:var(--gray-500);font-size:13.5px;">Không có cuộc họp nào sắp tới.</p>
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
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
