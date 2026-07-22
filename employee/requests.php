<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_employee();

$page_title = 'Yêu cầu của tôi';
$page_subtitle = 'Gửi yêu cầu mới và theo dõi trạng thái xử lý';
$active = 'requests';

$u = current_user();
$myId = $u['id'];
$errors = [];
$success = '';

/* ----- Gửi yêu cầu mới ----- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loai = trim($_POST['loai_yeu_cau'] ?? '');
    $noiDung = trim($_POST['noi_dung'] ?? '');
    $mucDo = $_POST['muc_do'] ?? 'Trung bình';

    if ($loai === '') $errors[] = 'Vui lòng chọn loại yêu cầu.';
    if ($noiDung === '') $errors[] = 'Vui lòng nhập nội dung yêu cầu.';

    if (!$errors) {
        $stmt = $pdo->prepare("INSERT INTO yeu_cau (ma_yc, loai_yeu_cau, noi_dung, muc_do, nguoi_tao_id, trang_thai)
                                VALUES ('TEMP', ?, ?, ?, ?, 'Chờ xử lý')");
        $stmt->execute([$loai, $noiDung, $mucDo, $myId]);
        $newId = $pdo->lastInsertId();
        $maYc = 'YC' . str_pad($newId, 4, '0', STR_PAD_LEFT);
        $upd = $pdo->prepare("UPDATE yeu_cau SET ma_yc = ? WHERE id = ?");
        $upd->execute([$maYc, $newId]);
        ghi_nhat_ky('Gửi yêu cầu mới: ' . $loai, $myId);
        $success = 'Gửi yêu cầu thành công! Mã yêu cầu của bạn là ' . $maYc . '.';
    }
}

/* ----- Bộ lọc danh sách yêu cầu của tôi ----- */
$trangThai = $_GET['trang_thai'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

$where = ['nguoi_tao_id = ?'];
$params = [$myId];
if ($trangThai !== '') { $where[] = 'trang_thai = ?'; $params[] = $trangThai; }
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM yeu_cau WHERE $whereSql");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$stmt = $pdo->prepare("SELECT * FROM yeu_cau WHERE $whereSql ORDER BY thoi_gian DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$myRequests = $stmt->fetchAll();

$loaiOptions = [
    'Xin nghỉ phép', 'Xin nghỉ không lương', 'Đặt phòng họp', 'Xin làm việc từ xa',
    'Xin tăng ca', 'Xin ứng lương', 'Góp ý / phản hồi', 'Khác'
];

function statusClassEmp2($s) { if ($s === 'Đã phê duyệt') return 'approved'; if ($s === 'Từ chối') return 'rejected'; return 'pending'; }
function levelClassEmp($m) { return $m === 'Cao' ? 'high' : ($m === 'Trung bình' ? 'medium' : 'low'); }

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
            <?php if ($errors): ?><div class="alert alert-error"><?php foreach($errors as $er) echo '<div>'.e($er).'</div>'; ?></div><?php endif; ?>

            <div class="form-card" style="margin-bottom:20px;">
                <h3 style="margin-top:0;">Gửi yêu cầu mới</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Loại yêu cầu <span class="req">*</span></label>
                            <select name="loai_yeu_cau" required>
                                <option value="">Chọn loại yêu cầu</option>
                                <?php foreach ($loaiOptions as $opt): ?>
                                    <option value="<?= e($opt) ?>"><?= e($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mức độ ưu tiên</label>
                            <select name="muc_do">
                                <option value="Thấp">Thấp</option>
                                <option value="Trung bình" selected>Trung bình</option>
                                <option value="Cao">Cao</option>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label>Nội dung yêu cầu <span class="req">*</span></label>
                            <textarea name="noi_dung" placeholder="Mô tả chi tiết yêu cầu của bạn" required></textarea>
                        </div>
                    </div>
                    <div class="form-actions" style="justify-content:flex-start;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Gửi yêu cầu</button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="filters-row">
                    <h3 style="margin:0;flex:1;">Danh sách yêu cầu của tôi</h3>
                    <div class="select-box">
                        <select onchange="window.location.href='requests.php?trang_thai='+this.value">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chờ xử lý" <?= $trangThai==='Chờ xử lý'?'selected':'' ?>>Chờ xử lý</option>
                            <option value="Đã phê duyệt" <?= $trangThai==='Đã phê duyệt'?'selected':'' ?>>Đã phê duyệt</option>
                            <option value="Từ chối" <?= $trangThai==='Từ chối'?'selected':'' ?>>Từ chối</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Mã YC</th><th>Loại yêu cầu</th><th>Nội dung</th><th>Mức độ</th><th>Thời gian</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php if (!$myRequests): ?>
                        <tr><td colspan="6" style="text-align:center;color:var(--gray-500);padding:30px;">Bạn chưa có yêu cầu nào phù hợp.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($myRequests as $r): ?>
                        <tr>
                            <td><b><?= e($r['ma_yc']) ?></b></td>
                            <td><?= e($r['loai_yeu_cau']) ?></td>
                            <td style="max-width:280px;"><?= e($r['noi_dung']) ?></td>
                            <td><span class="badge-level <?= levelClassEmp($r['muc_do']) ?>"><?= e($r['muc_do']) ?></span></td>
                            <td><?= format_date($r['thoi_gian'], true) ?></td>
                            <td><span class="badge-status <?= statusClassEmp2($r['trang_thai']) ?>"><?= e($r['trang_thai']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <div class="pagination-row">
                    <span>Hiển thị <?= $myRequests ? $offset+1 : 0 ?> đến <?= min($offset+$perPage,$totalCount) ?> của <?= $totalCount ?> yêu cầu</span>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="requests.php?trang_thai=<?= urlencode($trangThai) ?>&page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
