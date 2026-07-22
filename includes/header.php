<?php
/**
 * includes/header.php
 * Cần các biến: $page_title, $page_subtitle
 */
$u = current_user();
$avatar = !empty($u['avatar']) ? 'uploads/avatars/' . $u['avatar'] : 'assets/img/avatar-default.png';
?>
<header class="topbar">
    <div class="title-block">
        <button class="icon-btn" id="menuToggle" style="display:none;"><i class="fa-solid fa-bars"></i></button>
        <h1><?= e($page_title ?? '') ?></h1>
        <p><?= e($page_subtitle ?? '') ?></p>
    </div>
    <div class="right">
        <div class="bell"><i class="fa-regular fa-bell"></i><span class="badge">3</span></div>
        <div class="user" id="userMenuBtn">
            <img src="<?= e($avatar) ?>" alt="avatar" onerror="this.src='https://ui-avatars.com/api/?background=2563eb&color=fff&name=<?= urlencode($u['ho_ten'] ?? 'User') ?>'">
            <div class="info">
                <b><?= e($u['ho_ten'] ?? '') ?></b>
                <span><?= e($u['chuc_vu'] ?? '') ?></span>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:11px;color:#94a3b8;"></i>
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php"><i class="fa-regular fa-user"></i> Hồ sơ của tôi</a>
                <a href="profile.php#doi-mat-khau"><i class="fa-solid fa-lock"></i> Đổi mật khẩu</a>
                <a href="#" onclick="openLogoutModal();return false;"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
</header>
