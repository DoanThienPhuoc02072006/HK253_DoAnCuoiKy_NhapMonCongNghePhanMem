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
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhân viên.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT ho_ten, avatar FROM nhan_vien WHERE id = ?');
    $stmt->execute([$id]);
    $nv = $stmt->fetch();
    if (!$nv) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân viên.']);
        exit;
    }

    $del = $pdo->prepare('DELETE FROM nhan_vien WHERE id = ?');
    $del->execute([$id]);

    if (!empty($nv['avatar']) && file_exists(__DIR__ . '/../uploads/avatars/' . $nv['avatar'])) {
        @unlink(__DIR__ . '/../uploads/avatars/' . $nv['avatar']);
    }

    ghi_nhat_ky('Xóa nhân viên: ' . $nv['ho_ten']);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Không thể xóa. Nhân viên có thể đang liên kết với dữ liệu khác.']);
}
