<?php
/**
 * employee/includes/sidebar.php
 * Cần biến $active được set ở trang gọi include này
 */
$active = $active ?? '';
$menu = [
    ['key' => 'dashboard', 'url' => 'index.php',    'icon' => 'fa-house',           'label' => 'Tổng quan'],
    ['key' => 'requests',  'url' => 'requests.php', 'icon' => 'fa-file-lines',      'label' => 'Yêu cầu của tôi'],
    ['key' => 'meetings',  'url' => 'meetings.php', 'icon' => 'fa-calendar-days',   'label' => 'Lịch họp của tôi'],
    ['key' => 'profile',   'url' => 'profile.php',  'icon' => 'fa-user',            'label' => 'Hồ sơ cá nhân'],
];
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <span class="icon"><i class="fa-solid fa-users"></i></span>
        HRM System
    </div>
    <nav>
        <?php foreach ($menu as $m): ?>
            <a href="<?= e($m['url']) ?>" class="<?= $active === $m['key'] ? 'active' : '' ?>">
                <span class="ic"><i class="fa-solid <?= e($m['icon']) ?>"></i></span>
                <?= e($m['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="logout-box">
        <button class="logout-btn" onclick="openLogoutModal()">
            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
        </button>
    </div>
</aside>

<div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
        <div class="modal-ic"><i class="fa-solid fa-right-from-bracket"></i></div>
        <h3>Đăng xuất</h3>
        <p>Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?</p>
        <div class="modal-actions">
            <button class="btn" onclick="closeLogoutModal()">Hủy</button>
            <a class="btn btn-danger" href="../logout.php" style="justify-content:center;">Đăng xuất</a>
        </div>
    </div>
</div>
