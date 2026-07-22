<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_manager();

$page_title = 'Danh sách nhân viên';
$page_subtitle = 'Danh sách nhân viên trong phòng ban';
$active = 'employees';

/* ----- Bộ lọc & tìm kiếm ----- */
$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$dept = $_GET['dept'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$offset = ($page - 1) * $perPage;

$where = ["nv.chuc_vu = 'Nhân viên'"];
$params = [];

if ($q !== '') {
    $where[] = "(nv.ho_ten LIKE ? OR nv.ma_nv LIKE ? OR nv.email LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}
if ($status !== '') {
    $where[] = "nv.trang_thai = ?";
    $params[] = $status;
}
if ($dept !== '') {
    $where[] = "nv.phong_ban_id = ?";
    $params[] = $dept;
}
$whereSql = implode(' AND ', $where);

$total = $pdo->prepare("SELECT COUNT(*) FROM nhan_vien nv WHERE $whereSql");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$sql = "SELECT nv.*, pb.ten_phong_ban FROM nhan_vien nv
        LEFT JOIN phong_ban pb ON pb.id = nv.phong_ban_id
        WHERE $whereSql
        ORDER BY nv.id ASC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM phong_ban ORDER BY ten_phong_ban")->fetchAll();

function qs($extra = []) {
    $params = array_merge($_GET, $extra);
    return htmlspecialchars('?' . http_build_query($params));
}

include __DIR__ . '/includes/head_meta.php';
?>
<div class="app">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
        <?php include __DIR__ . '/includes/header.php'; ?>
        <div class="content">

            <div class="panel">
                <form method="GET" class="filters-row">
                    <div class="search-box">
                        <span class="ic"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Tìm kiếm nhân viên...">
                    </div>
                    <div class="select-box">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Hoạt động" <?= $status === 'Hoạt động' ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="Nghỉ việc" <?= $status === 'Nghỉ việc' ? 'selected' : '' ?>>Nghỉ việc</option>
                        </select>
                    </div>
                    <div class="select-box">
                        <select name="dept" onchange="this.form.submit()">
                            <option value="">Tất cả phòng ban</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $dept == $d['id'] ? 'selected' : '' ?>><?= e($d['ten_phong_ban']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn"><i class="fa-solid fa-filter"></i> Bộ lọc</button>
                    <a class="btn" href="employee_export.php?<?= http_build_query($_GET) ?>"><i class="fa-solid fa-download"></i> Xuất Excel</a>
                    <a class="btn btn-primary" href="employee_add.php"><i class="fa-solid fa-plus"></i> Thêm nhân viên</a>
                </form>

                <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã nhân viên</th><th>Họ và tên</th><th>Chức vụ</th><th>Email</th>
                            <th>SĐT</th><th>Trạng thái</th><th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$employees): ?>
                        <tr><td colspan="7" style="text-align:center;color:var(--gray-500);padding:30px;">Không tìm thấy nhân viên nào.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($employees as $nv): ?>
                        <tr>
                            <td><?= e($nv['ma_nv']) ?></td>
                            <td>
                                <div class="person-cell">
                                    <img src="<?= !empty($nv['avatar']) ? 'uploads/avatars/'.e($nv['avatar']) : 'https://ui-avatars.com/api/?background=2563eb&color=fff&name='.urlencode($nv['ho_ten']) ?>" alt="">
                                    <?= e($nv['ho_ten']) ?>
                                </div>
                            </td>
                            <td><?= e($nv['chuc_vu']) ?></td>
                            <td><?= e($nv['email']) ?></td>
                            <td><?= e($nv['sdt']) ?></td>
                            <td><span class="badge-status <?= $nv['trang_thai'] === 'Hoạt động' ? 'active' : 'inactive' ?>"><?= e($nv['trang_thai']) ?></span></td>
                            <td>
                                <div class="action-icons">
                                    <a class="icon-btn view" href="employee_view.php?id=<?= $nv['id'] ?>" title="Xem"><i class="fa-regular fa-eye"></i></a>
                                    <a class="icon-btn edit" href="employee_edit.php?id=<?= $nv['id'] ?>" title="Sửa"><i class="fa-solid fa-pen"></i></a>
                                    <button class="icon-btn delete" title="Xóa" onclick="xoaNhanVien(<?= $nv['id'] ?>, this)"><i class="fa-regular fa-trash-can"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>

                <div class="pagination-row">
                    <span>Hiển thị <?= $employees ? $offset+1 : 0 ?> đến <?= min($offset+$perPage,$totalCount) ?> của <?= $totalCount ?> nhân viên</span>
                    <div class="pagination">
                        <a href="<?= qs(['page'=>max(1,$page-1)]) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= qs(['page'=>$i]) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        <a href="<?= qs(['page'=>min($totalPages,$page+1)]) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
