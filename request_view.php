<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT yc.*, nv.ho_ten, nv.ma_nv, nv.email FROM yeu_cau yc
                        LEFT JOIN nhan_vien nv ON nv.id = yc.nguoi_tao_id WHERE yc.id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { header('Location: requests.php'); exit; }

$page_title = 'Chi tiết yêu cầu';
$page_subtitle = 'Thông tin chi tiết yêu cầu ' . $r['ma_yc'];
$active = 'requests';

function levelClass2($m) { return $m === 'Cao' ? 'high' : ($m === 'Trung bình' ? 'medium' : 'low'); }
function statusClass2($s) { if ($s === 'Đã phê duyệt') return 'approved'; if ($s === 'Từ chối') return 'rejected'; return 'pending'; }

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">
            <div class="breadcrumb">
                <a href="requests.php"><i class="fa-solid fa-house"></i> Yêu cầu của nhân viên</a> <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                <span class="cur"><?= e($r['ma_yc']) ?></span>
            </div>

            <div class="panel" style="max-width:720px;">
                <div class="head-row">
                    <h3>Yêu cầu <?= e($r['ma_yc']) ?></h3>
                    <span class="badge-status <?= statusClass2($r['trang_thai']) ?>"><?= e($r['trang_thai']) ?></span>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Loại yêu cầu</label><input value="<?= e($r['loai_yeu_cau']) ?>" disabled></div>
                    <div class="form-group"><label>Mức độ</label><input value="<?= e($r['muc_do']) ?>" disabled></div>
                    <div class="form-group"><label>Người tạo</label><input value="<?= e($r['ho_ten']) . ' (' . e($r['ma_nv']) . ')' ?>" disabled></div>
                    <div class="form-group"><label>Thời gian tạo</label><input value="<?= format_date($r['thoi_gian'], true) ?>" disabled></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Nội dung yêu cầu</label><textarea disabled><?= e($r['noi_dung']) ?></textarea></div>
                </div>

                <?php if ($r['trang_thai'] === 'Chờ xử lý'): ?>
                    <div class="form-actions">
                        <button class="btn btn-danger" onclick="xuLyYeuCau(<?= $r['id'] ?>,'tu_choi',this)">Từ chối</button>
                        <button class="btn btn-primary" onclick="xuLyYeuCau(<?= $r['id'] ?>,'duyet',this)">Phê duyệt</button>
                    </div>
                <?php else: ?>
                    <div class="form-actions"><a href="requests.php" class="btn">Quay lại danh sách</a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
