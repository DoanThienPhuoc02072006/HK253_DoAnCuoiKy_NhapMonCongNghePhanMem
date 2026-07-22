<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Tạo cuộc họp';
$page_subtitle = 'Tạo cuộc họp mới';
$active = 'meeting';

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $tieu_de = trim($_POST['tieu_de'] ?? '');
    $noi_dung = trim($_POST['noi_dung'] ?? '');
    $phong_hop_id = $_POST['phong_hop_id'] ?? null;
    $ngay_bd = $_POST['ngay_bd'] ?? '';
    $gio_bd = $_POST['gio_bd'] ?? '';
    $ngay_kt = $_POST['ngay_kt'] ?? '';
    $gio_kt = $_POST['gio_kt'] ?? '';
    $nhac_nho = $_POST['nhac_nho'] ?? '';
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    $quyen_truy_cap = $_POST['quyen_truy_cap'] ?? 'Chỉ thành viên được mời';
    $thanhVien = $_POST['thanh_vien'] ?? [];

    if ($tieu_de === '') $errors[] = 'Vui lòng nhập tiêu đề cuộc họp.';
    if ($noi_dung === '') $errors[] = 'Vui lòng nhập mục đích/nội dung cuộc họp.';

    // Xác thực phòng họp thực sự tồn tại trong CSDL (không chỉ kiểm tra khác rỗng),
    // tránh trường hợp ID không hợp lệ khiến LEFT JOIN sau này trả về NULL khi xem chi tiết.
    if (!$phong_hop_id) {
        $errors[] = 'Vui lòng chọn phòng họp.';
    } else {
        $checkRoom = $pdo->prepare('SELECT COUNT(*) FROM phong_hop WHERE id = ?');
        $checkRoom->execute([$phong_hop_id]);
        if ($checkRoom->fetchColumn() == 0) $errors[] = 'Phòng họp đã chọn không hợp lệ, vui lòng chọn lại.';
    }

    if ($ngay_bd === '' || $gio_bd === '') $errors[] = 'Vui lòng chọn thời gian bắt đầu.';
    if ($ngay_kt === '' || $gio_kt === '') $errors[] = 'Vui lòng chọn thời gian kết thúc.';
    if (empty($thanhVien)) $errors[] = 'Vui lòng chọn ít nhất một thành viên tham dự.';

    // Xác thực toàn bộ thành viên được chọn là những nhân viên có thật đang Hoạt động,
    // tránh trường hợp ID giả/không hợp lệ lọt vào bảng cuoc_hop_thanh_vien.
    $validAttendeeIds = [];
    if (!$errors && $thanhVien) {
        $placeholders = implode(',', array_fill(0, count($thanhVien), '?'));
        $checkAttendees = $pdo->prepare("SELECT id FROM nhan_vien WHERE id IN ($placeholders) AND chuc_vu = 'Nhân viên' AND trang_thai = 'Hoạt động'");
        $checkAttendees->execute(array_map('intval', $thanhVien));
        $validAttendeeIds = $checkAttendees->fetchAll(PDO::FETCH_COLUMN);
        if (!$validAttendeeIds) $errors[] = 'Danh sách thành viên đã chọn không hợp lệ, vui lòng chọn lại.';
    }

    // Xác thực ngày/giờ đúng định dạng thay vì để MySQL âm thầm lưu ngày rỗng (0000-00-00)
    // khi chuỗi không hợp lệ — đây chính là nguyên nhân khiến trang chi tiết cuộc họp
    // hiển thị trống dù cuộc họp đã được "tạo thành công".
    $start = $end = null;
    if (!$errors) {
        $startDt = DateTime::createFromFormat('Y-m-d H:i', $ngay_bd . ' ' . $gio_bd);
        $endDt = DateTime::createFromFormat('Y-m-d H:i', $ngay_kt . ' ' . $gio_kt);
        if (!$startDt || $startDt->format('Y-m-d H:i') !== $ngay_bd . ' ' . $gio_bd) {
            $errors[] = 'Thời gian bắt đầu không hợp lệ, vui lòng chọn lại.';
        } elseif (!$endDt || $endDt->format('Y-m-d H:i') !== $ngay_kt . ' ' . $gio_kt) {
            $errors[] = 'Thời gian kết thúc không hợp lệ, vui lòng chọn lại.';
        } elseif ($endDt <= $startDt) {
            $errors[] = 'Thời gian kết thúc phải sau thời gian bắt đầu.';
        } else {
            $start = $startDt->format('Y-m-d H:i:00');
            $end = $endDt->format('Y-m-d H:i:00');
        }
    }

    // Tệp đính kèm là TÙY CHỌN: nếu tệp không hợp lệ (sai định dạng, lỗi upload...),
    // KHÔNG được chặn việc tạo cuộc họp — chỉ bỏ qua việc lưu tệp và báo nhẹ cho người dùng.
    // Đây là điểm khác biệt quan trọng so với các trường bắt buộc (tiêu đề, phòng họp...).
    $tepDinhKem = null;
    $attachmentWarning = null;
    if (!empty($_FILES['tep_dinh_kem']['name'])) {
        $uploadError = validate_upload_error($_FILES['tep_dinh_kem']);
        $ext = strtolower(pathinfo($_FILES['tep_dinh_kem']['name'], PATHINFO_EXTENSION));
        if ($uploadError) {
            $attachmentWarning = $uploadError;
        } elseif (!in_array($ext, ['pdf','doc','docx','ppt','pptx','xls','xlsx'])) {
            $attachmentWarning = 'Tệp đính kèm chỉ hỗ trợ định dạng PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX — tệp của bạn đã KHÔNG được lưu, nhưng cuộc họp vẫn được tạo bình thường.';
        } elseif (!ensure_upload_dir(__DIR__ . '/uploads/attachments')) {
            $attachmentWarning = 'Máy chủ không có quyền ghi vào thư mục uploads/attachments nên tệp đính kèm KHÔNG được lưu, nhưng cuộc họp vẫn được tạo bình thường.';
        } else {
            $newFileName = 'file_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['tep_dinh_kem']['tmp_name'], __DIR__ . '/uploads/attachments/' . $newFileName)) {
                $tepDinhKem = $newFileName;
            } else {
                $attachmentWarning = 'Không thể lưu tệp đính kèm lên máy chủ, nhưng cuộc họp vẫn được tạo bình thường.';
            }
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO cuoc_hop
                (tieu_de, noi_dung, phong_hop_id, thoi_gian_bat_dau, thoi_gian_ket_thuc, nhac_nho, ghi_chu, quyen_truy_cap, tep_dinh_kem, nguoi_tao_id)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$tieu_de, $noi_dung, $phong_hop_id, $start, $end, $nhac_nho, $ghi_chu, $quyen_truy_cap, $tepDinhKem, $_SESSION['user_id']]);
            $meetingId = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO cuoc_hop_thanh_vien (cuoc_hop_id, nhan_vien_id) VALUES (?,?)");
            foreach ($validAttendeeIds as $nvId) {
                $ins->execute([$meetingId, (int)$nvId]);
            }
            $pdo->commit();
            ghi_nhat_ky('Tạo cuộc họp mới: ' . $tieu_de);
            if ($attachmentWarning) {
                $_SESSION['flash_warning'] = $attachmentWarning;
            }
            header('Location: calendar.php?created=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Không thể tạo cuộc họp, vui lòng thử lại.';
        }
    }
}

$rooms = $pdo->query("SELECT * FROM phong_hop ORDER BY ten_phong")->fetchAll();
$employees = $pdo->query("SELECT * FROM nhan_vien WHERE chuc_vu = 'Nhân viên' AND trang_thai='Hoạt động' ORDER BY ho_ten")->fetchAll();

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">
            <div class="breadcrumb">
                <a href="calendar.php"><i class="fa-solid fa-house"></i> Lịch phòng họp</a> <i class="fa-solid fa-chevron-right" style="font-size:10px;"></i>
                <span class="cur">Tạo cuộc họp</span>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-error"><?php foreach ($errors as $err) echo '<div>' . e($err) . '</div>'; ?></div>
            <?php endif; ?>

            <div class="form-card">
                <h3 style="margin-top:0;">Thông tin cuộc họp</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div style="display:flex;flex-direction:column;gap:20px;">
                            <div class="form-group">
                                <label>Tiêu đề cuộc họp <span class="req">*</span></label>
                                <input type="text" name="tieu_de" value="<?= e($old['tieu_de'] ?? '') ?>" placeholder="Nhập tiêu đề cuộc họp" required>
                            </div>
                            <div class="form-group">
                                <label>Mục đích / Nội dung <span class="req">*</span></label>
                                <textarea name="noi_dung" placeholder="Nhập mục đích hoặc nội dung cuộc họp" required><?= e($old['noi_dung'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Phòng họp <span class="req">*</span></label>
                                <select name="phong_hop_id" required>
                                    <option value="">Chọn phòng họp</option>
                                    <?php foreach ($rooms as $r): ?>
                                        <option value="<?= $r['id'] ?>" <?= (($old['phong_hop_id'] ?? '') == $r['id']) ? 'selected' : '' ?>><?= e($r['ten_phong']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-grid" style="grid-template-columns:1fr 1fr;">
                                <div class="form-group">
                                    <label>Thời gian bắt đầu <span class="req">*</span></label>
                                    <input type="date" name="ngay_bd" value="<?= e($old['ngay_bd'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <input type="time" name="gio_bd" value="<?= e($old['gio_bd'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Thời gian kết thúc <span class="req">*</span></label>
                                    <input type="date" name="ngay_kt" value="<?= e($old['ngay_kt'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <input type="time" name="gio_kt" value="<?= e($old['gio_kt'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Nhắc nhở</label>
                                <select name="nhac_nho">
                                    <option value="">Chọn thời gian nhắc</option>
                                    <option value="5" <?= (($old['nhac_nho']??'')=='5')?'selected':'' ?>>5 phút trước</option>
                                    <option value="15" <?= (($old['nhac_nho']??'')=='15')?'selected':'' ?>>15 phút trước</option>
                                    <option value="30" <?= (($old['nhac_nho']??'')=='30')?'selected':'' ?>>30 phút trước</option>
                                    <option value="60" <?= (($old['nhac_nho']??'')=='60')?'selected':'' ?>>1 giờ trước</option>
                                    <option value="1440" <?= (($old['nhac_nho']??'')=='1440')?'selected':'' ?>>1 ngày trước</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ghi chú</label>
                                <textarea name="ghi_chu" placeholder="Nhập ghi chú (nếu có)"><?= e($old['ghi_chu'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:16px;">
                            <div class="form-group">
                                <label>Thành viên tham dự <span class="req">*</span></label>
                                <div class="search-box" style="margin-bottom:10px;">
                                    <span class="ic"><i class="fa-solid fa-magnifying-glass"></i></span>
                                    <input type="text" id="attendeeSearch" placeholder="Tìm kiếm nhân viên..." onkeyup="filterAttendees(this.value)">
                                </div>
                                <div class="attendee-list" id="attendeeList">
                                    <?php foreach ($employees as $nv): ?>
                                        <label class="attendee-item" data-name="<?= e(mb_strtolower($nv['ho_ten'])) ?>">
                                            <input type="checkbox" class="attendee-checkbox" name="thanh_vien[]" value="<?= $nv['id'] ?>" checked>
                                            <img src="<?= !empty($nv['avatar']) ? 'uploads/avatars/'.e($nv['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name='.urlencode($nv['ho_ten']) ?>">
                                            <?= e($nv['ho_ten']) ?> (<?= e($nv['ma_nv']) ?>)
                                            <span class="role">Nhân viên</span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <div class="attendee-links">
                                    <a href="#" onclick="toggleAllAttendees(true);return false;">Chọn tất cả</a>
                                    <a href="#" class="danger" onclick="toggleAllAttendees(false);return false;">Bỏ chọn tất cả</a>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Tệp đính kèm</label>
                                <div class="upload-box">
                                    <div class="big-ic"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                                    Kéo thả tệp vào đây hoặc click để chọn tệp<br>
                                    <small>Hỗ trợ: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX (Tối đa 10MB/tệp)</small>
                                    <input type="file" name="tep_dinh_kem">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Quyền truy cập <span class="req">*</span></label>
                                <select name="quyen_truy_cap" required>
                                    <option value="Chỉ thành viên được mời">Chỉ thành viên được mời</option>
                                    <option value="Toàn bộ phòng ban">Toàn bộ phòng ban</option>
                                    <option value="Công khai">Công khai</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="calendar.php" class="btn">Hủy</a>
                        <button type="submit" class="btn btn-primary">Tạo cuộc họp</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
<script>
function filterAttendees(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#attendeeList .attendee-item').forEach(function (item) {
        item.style.display = item.dataset.name.indexOf(q) > -1 ? '' : 'none';
    });
}
</script>
</body>
</html>
