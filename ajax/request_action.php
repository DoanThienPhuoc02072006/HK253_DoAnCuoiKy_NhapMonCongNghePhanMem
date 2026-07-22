<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này.']);
    exit;
}

$currentUser = current_user();
if (!$currentUser || $currentUser['chuc_vu'] !== 'Trưởng phòng') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này.']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$hanhDong = $_POST['hanh_dong'] ?? '';

if (!$id || !in_array($hanhDong, ['duyet', 'tu_choi'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$trangThai = $hanhDong === 'duyet' ? 'Đã phê duyệt' : 'Từ chối';

try {
    $stmt = $pdo->prepare('UPDATE yeu_cau SET trang_thai = ? WHERE id = ?');
    $stmt->execute([$trangThai, $id]);
    ghi_nhat_ky('Xử lý yêu cầu #' . $id . ' -> ' . $trangThai);
    echo json_encode(['success' => true, 'trang_thai' => $trangThai]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra, vui lòng thử lại.']);
}
