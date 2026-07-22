<?php
/**
 * includes/auth.php
 * Quản lý phiên đăng nhập (session) và các hàm phân quyền
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/** Bắt buộc phải đăng nhập mới được truy cập trang hiện tại */
function require_login()
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/** Chỉ Trưởng phòng mới được truy cập (dùng ở các trang quản lý gốc /) */
function require_manager()
{
    require_login();
    $u = current_user();
    if (!$u || $u['chuc_vu'] !== 'Trưởng phòng') {
        header('Location: login.php');
        exit;
    }
}

/** Chỉ Nhân viên mới được truy cập (dùng ở các trang trong thư mục /employee) */
function require_employee()
{
    require_login();
    $u = current_user();
    if (!$u || $u['chuc_vu'] !== 'Nhân viên') {
        header('Location: ../login.php');
        exit;
    }
}

/** Trả về đường dẫn tuyệt đối tính từ thư mục gốc website */
function base_url($path = '')
{
    $root = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // Nếu đang ở trong thư mục con (ví dụ /ajax), lùi về gốc
    return '/' . ltrim($path, '/');
}

/** Lấy thông tin người dùng hiện tại đang đăng nhập */
function current_user()
{
    global $pdo;
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv
                            LEFT JOIN phong_ban pb ON pb.id = nv.phong_ban_id
                            WHERE nv.id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user;
}

/** Ghi log hoạt động của người dùng */
function ghi_nhat_ky($noi_dung, $nhan_vien_id = null)
{
    global $pdo;
    $nhan_vien_id = $nhan_vien_id ?? ($_SESSION['user_id'] ?? null);
    if (!$nhan_vien_id) return;
    $stmt = $pdo->prepare('INSERT INTO nhat_ky_hoat_dong (nhan_vien_id, noi_dung) VALUES (?, ?)');
    $stmt->execute([$nhan_vien_id, $noi_dung]);
}

/** Định dạng ngày giờ kiểu Việt Nam */
function format_date($date, $withTime = false)
{
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $withTime ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
}

/** Escape output an toàn */
function e($str)
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Đảm bảo thư mục upload tồn tại và có quyền ghi trước khi move_uploaded_file().
 * Trả về true nếu thư mục sẵn sàng để ghi file, false nếu không thể tạo/ghi được
 * (ví dụ do lỗi phân quyền trên máy chủ) — giúp tính năng upload không làm sập
 * cả trang khi thư mục uploads/ chưa tồn tại hoặc thiếu quyền ghi.
 */
function ensure_upload_dir($absolutePath)
{
    if (!is_dir($absolutePath)) {
        @mkdir($absolutePath, 0777, true);
    }
    if (is_dir($absolutePath) && !is_writable($absolutePath)) {
        @chmod($absolutePath, 0777);
    }
    return is_dir($absolutePath) && is_writable($absolutePath);
}

/**
 * Kiểm tra một mục $_FILES[...] có thực sự upload thành công hay không và trả về
 * thông báo lỗi tiếng Việt rõ ràng nếu thất bại (thay vì âm thầm bỏ qua như trước,
 * khiến hệ thống lưu tên file vào CSDL dù file thật chưa từng được ghi lên server).
 *
 * @return string|null null nếu hợp lệ, hoặc chuỗi thông báo lỗi nếu có vấn đề
 */
function validate_upload_error($fileArray)
{
    if (empty($fileArray) || empty($fileArray['name'])) {
        return null; // không có file nào được chọn, không phải lỗi
    }
    switch ($fileArray['error']) {
        case UPLOAD_ERR_OK:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Tệp bạn chọn vượt quá dung lượng cho phép của máy chủ. Vui lòng chọn tệp nhỏ hơn.';
        case UPLOAD_ERR_PARTIAL:
            return 'Tệp chỉ được tải lên một phần, vui lòng thử lại.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return 'Máy chủ không thể lưu tệp tải lên (lỗi thư mục tạm). Vui lòng liên hệ quản trị viên.';
        case UPLOAD_ERR_EXTENSION:
            return 'Việc tải tệp lên bị chặn bởi một extension của PHP trên máy chủ.';
        default:
            return 'Có lỗi không xác định khi tải tệp lên, vui lòng thử lại.';
    }
}
