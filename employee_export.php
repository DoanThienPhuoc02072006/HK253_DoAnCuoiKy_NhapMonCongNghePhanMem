<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$dept = $_GET['dept'] ?? '';

$where = ["nv.chuc_vu = 'Nhân viên'"];
$params = [];
if ($q !== '') { $where[] = "(nv.ho_ten LIKE ? OR nv.ma_nv LIKE ? OR nv.email LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($status !== '') { $where[] = "nv.trang_thai = ?"; $params[] = $status; }
if ($dept !== '') { $where[] = "nv.phong_ban_id = ?"; $params[] = $dept; }
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv
                        LEFT JOIN phong_ban pb ON pb.id = nv.phong_ban_id
                        WHERE $whereSql ORDER BY nv.id ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename=danh_sach_nhan_vien.csv');
echo "\xEF\xBB\xBF"; // BOM để Excel hiển thị đúng tiếng Việt

$out = fopen('php://output', 'w');
fputcsv($out, ['Mã NV', 'Họ và tên', 'Email', 'SĐT', 'Chức vụ', 'Phòng ban', 'Trạng thái', 'Ngày vào làm']);
foreach ($rows as $r) {
    fputcsv($out, [$r['ma_nv'], $r['ho_ten'], $r['email'], $r['sdt'], $r['chuc_vu'], $r['ten_phong_ban'], $r['trang_thai'], format_date($r['ngay_vao_lam'])]);
}
fclose($out);
exit;
