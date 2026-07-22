<?php
/**
 * includes/sidebar.php
 * Cần biến $active được set ở trang gọi include này
 * Ví dụ: $active = 'dashboard';
 *
 * LƯU Ý QUAN TRỌNG: vì include() dùng chung phạm vi biến (variable scope) với file
 * gọi nó, các biến tạm dùng trong file này phải đặt tên thật đặc thù (tiền tố __sidebar)
 * để không bao giờ vô tình ghi đè lên biến cùng tên ở trang gọi include (ví dụ $m, $item...).
 * Đây chính là nguyên nhân từng gây lỗi trang "Chi tiết cuộc họp" hiển thị trống dữ liệu.
 */
$active = $active ?? '';
$__sidebarMenu = [
    ['key' => 'dashboard',  'url' => 'index.php',            'icon' => 'fa-chart-pie',        'label' => 'Tổng quan'],
    ['key' => 'requests',   'url' => 'requests.php',         'icon' => 'fa-file-lines',       'label' => 'Yêu cầu của nhân viên'],
    ['key' => 'employees',  'url' => 'employees.php',        'icon' => 'fa-users',            'label' => 'Danh sách nhân viên'],
    ['key' => 'add_emp',    'url' => 'employee_add.php',     'icon' => 'fa-user-plus',        'label' => 'Thêm nhân viên'],
    ['key' => 'calendar',   'url' => 'calendar.php',         'icon' => 'fa-calendar-days',    'label' => 'Lịch phòng họp'],
    ['key' => 'meeting',    'url' => 'meeting_create.php',   'icon' => 'fa-video',            'label' => 'Tạo cuộc họp'],
    ['key' => 'statistics', 'url' => 'statistics.php',       'icon' => 'fa-chart-column',     'label' => 'Thống kê'],
    ['key' => 'profile',    'url' => 'profile.php',          'icon' => 'fa-gear',             'label' => 'Hồ sơ tài khoản trưởng phòng'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span class="icon"><i class="fa-solid fa-users"></i></span>
        HRM System
    </div>
    <nav>
        <?php foreach ($__sidebarMenu as $__sidebarMenuItem): ?>
            <a href="<?= e($__sidebarMenuItem['url']) ?>" class="<?= $active === $__sidebarMenuItem['key'] ? 'active' : '' ?>">
                <span class="ic"><i class="fa-solid <?= e($__sidebarMenuItem['icon']) ?>"></i></span>
                <?= e($__sidebarMenuItem['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="logout-box">
        <button class="logout-btn" onclick="openLogoutModal()">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </button>
    </div>
</aside>

<!-- Modal xác nhận đăng xuất (dùng chung mọi trang) -->
<div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
        <div class="modal-ic"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h3>Đăng xuất</h3>
        <p>Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?</p>
        <div class="modal-actions">
            <button class="btn" onclick="closeLogoutModal()">Hủy</button>
            <a class="btn btn-danger" href="logout.php" style="justify-content:center;">Đăng xuất</a>
        </div>
    </div>
</div>
<?php unset($__sidebarMenu, $__sidebarMenuItem); ?>
