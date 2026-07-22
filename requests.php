<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Yêu cầu của nhân viên';
$page_subtitle = 'Quản lý và xử lý yêu cầu của nhân viên';
$active = 'requests';

/* ----- Bộ đếm tổng quan ----- */
$counts = $pdo->query("SELECT
    COUNT(*) total,
    SUM(trang_thai='Chờ xử lý') pending,
    SUM(trang_thai='Đã phê duyệt') approved,
    SUM(trang_thai='Từ chối') rejected
    FROM yeu_cau")->fetch();

/* ----- Bộ lọc ----- */
$loai = $_GET['loai'] ?? '';
$trangThai = $_GET['trang_thai'] ?? '';
$tuNgay = $_GET['tu_ngay'] ?? date('Y-m-d', strtotime('-90 days'));
$denNgay = $_GET['den_ngay'] ?? date('Y-m-d');
$kw = trim($_GET['kw'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = ["DATE(yc.thoi_gian) BETWEEN ? AND ?"];
$params = [$tuNgay, $denNgay];
if ($loai !== '') { $where[] = 'yc.loai_yeu_cau = ?'; $params[] = $loai; }
if ($trangThai !== '') { $where[] = 'yc.trang_thai = ?'; $params[] = $trangThai; }
if ($kw !== '') { $where[] = '(yc.ma_yc LIKE ? OR yc.noi_dung LIKE ? OR nv.ho_ten LIKE ?)'; $params[] = "%$kw%"; $params[] = "%$kw%"; $params[] = "%$kw%"; }
$whereSql = implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM yeu_cau yc LEFT JOIN nhan_vien nv ON nv.id = yc.nguoi_tao_id WHERE $whereSql");
$totalStmt->execute($params);
$totalCount = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$sql = "SELECT yc.*, nv.ho_ten, nv.ma_nv FROM yeu_cau yc
        LEFT JOIN nhan_vien nv ON nv.id = yc.nguoi_tao_id
        WHERE $whereSql ORDER BY yc.thoi_gian DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$types = $pdo->query("SELECT DISTINCT loai_yeu_cau FROM yeu_cau ORDER BY loai_yeu_cau")->fetchAll(PDO::FETCH_COLUMN);

function qs2($extra = []) {
    $params = array_merge($_GET, $extra);
    return htmlspecialchars('?' . http_build_query($params));
}

function levelClass($m) {
    return $m === 'Cao' ? 'high' : ($m === 'Trung bình' ? 'medium' : 'low');
}
function statusClass($s) {
    if ($s === 'Đã phê duyệt') return 'approved';
    if ($s === 'Từ chối') return 'rejected';
    return 'pending';
}

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
                    <span class="label">Tổng số yêu cầu</span>
                    <span class="value"><?= (int)$counts['total'] ?></span>
                    <span class="delta" style="color:var(--gray-500);">Tất cả yêu cầu</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-orange"><i class="fa-regular fa-clock"></i></span></div>
                    <span class="label">Đang chờ xử lý</span>
                    <span class="value"><?= (int)$counts['pending'] ?></span>
                    <span class="delta" style="color:var(--gray-500);">Yêu cầu đang chờ</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-green"><i class="fa-regular fa-circle-check"></i></span></div>
                    <span class="label">Đã phê duyệt</span>
                    <span class="value"><?= (int)$counts['approved'] ?></span>
                    <span class="delta" style="color:var(--gray-500);">Yêu cầu đã duyệt</span>
                </div>
                <div class="stat-card">
                    <div class="top"><span class="ic bg-red"><i class="fa-regular fa-circle-xmark"></i></span></div>
                    <span class="label">Từ chối</span>
                    <span class="value"><?= (int)$counts['rejected'] ?></span>
                    <span class="delta" style="color:var(--gray-500);">Yêu cầu bị từ chối</span>
                </div>
            </div>

            <div class="panel">
                <form method="GET" class="filters-row">
                    <div class="select-box" style="min-width:170px;">
                        <select name="loai" onchange="this.form.submit()">
                            <option value="">Tất cả loại yêu cầu</option>
                            <?php foreach ($types as $t): ?>
                                <option value="<?= e($t) ?>" <?= $loai === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="select-box" style="min-width:170px;">
                        <select name="trang_thai" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chờ xử lý" <?= $trangThai==='Chờ xử lý'?'selected':'' ?>>Chờ xử lý</option>
                            <option value="Đã phê duyệt" <?= $trangThai==='Đã phê duyệt'?'selected':'' ?>>Đã phê duyệt</option>
                            <option value="Từ chối" <?= $trangThai==='Từ chối'?'selected':'' ?>>Từ chối</option>
                        </select>
                    </div>
                    <input type="date" name="tu_ngay" value="<?= e($tuNgay) ?>">
                    <input type="date" name="den_ngay" value="<?= e($denNgay) ?>">
                    <div class="search-box">
                        <span class="ic"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="kw" value="<?= e($kw) ?>" placeholder="Tìm kiếm...">
                    </div>
                    <button type="submit" class="btn btn-primary">Tìm</button>
                </form>

                <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã yêu cầu</th><th>Loại yêu cầu</th><th>Nội dung yêu cầu</th><th>Mức độ</th>
                            <th>Người tạo</th><th>Thời gian</th><th>Trạng thái</th><th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="8" style="text-align:center;color:var(--gray-500);padding:30px;">Không có yêu cầu nào phù hợp.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td><b><?= e($r['ma_yc']) ?></b></td>
                            <td><?= e($r['loai_yeu_cau']) ?></td>
                            <td style="max-width:280px;"><?= e($r['noi_dung']) ?></td>
                            <td><span class="badge-level <?= levelClass($r['muc_do']) ?>"><?= e($r['muc_do']) ?></span></td>
                            <td><?= e($r['ho_ten']) ?><br><small style="color:var(--gray-500);"><?= e($r['ma_nv']) ?></small></td>
                            <td><?= format_date($r['thoi_gian'], true) ?></td>
                            <td><span class="badge-status <?= statusClass($r['trang_thai']) ?>"><?= e($r['trang_thai']) ?></span></td>
                            <td>
                                <div class="action-icons">
                                    <?php if ($r['trang_thai'] === 'Chờ xử lý'): ?>
                                        <button class="icon-btn approve" title="Duyệt" onclick="xuLyYeuCau(<?= $r['id'] ?>,'duyet',this)"><i class="fa-solid fa-check"></i></button>
                                        <button class="icon-btn reject" title="Từ chối" onclick="xuLyYeuCau(<?= $r['id'] ?>,'tu_choi',this)"><i class="fa-solid fa-xmark"></i></button>
                                    <?php else: ?>
                                        <a class="icon-btn view" href="request_view.php?id=<?= $r['id'] ?>" title="Xem"><i class="fa-regular fa-eye"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <div class="pagination-row">
                    <span>Hiển thị <?= $requests ? $offset+1 : 0 ?> đến <?= min($offset+$perPage,$totalCount) ?> của <?= $totalCount ?> yêu cầu</span>
                    <div class="pagination">
                        <a href="<?= qs2(['page'=>1]) ?>">«</a>
                        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
                            <a href="<?= qs2(['page'=>$i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="<?= qs2(['page'=>$totalPages]) ?>">»</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
