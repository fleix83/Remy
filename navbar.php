<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/language_handler.php';
require_once __DIR__ . '/includes/language_utils.php';
require_once __DIR__ . '/includes/date_function.php';
require_once __DIR__ . '/includes/header.php';



if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Add language switcher
$availableLanguages = [
    'de' => 'Deutsch',
    'fr' => 'Français',
    'it' => 'Italiano'
];

// Function to check if user has required role
function hasRole($requiredRole) {
    $userRole = $_SESSION['role'] ?? '';
    if ($requiredRole === 'moderator') {
        return $userRole === 'moderator' || $userRole === 'admin';
    } elseif ($requiredRole === 'admin') {
        return $userRole === 'admin';
    }
    return false;
}

// Get the avatar filename from the session
$avatarFilename = $_SESSION['avatar'] ?? 'default-avatar.png';

// Build the full avatar path
$avatarPath = BASE_URL . '/uploads/avatars/' . $avatarFilename;

// Generate a unique query parameter to prevent caching
$avatarCacheBuster = '?t=' . time(); // You can also use a hash of the file if preferred
?>

<head>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="position-relative"></div>
    <!-- <a class="navbar-brand" href="<?php echo BASE_URL; ?>forum.php">Remy</a>  -->
    <svg id="logo" width="30%" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 584 240.6">
  <!-- Generator: Adobe Illustrator 29.1.0, SVG Export Plug-In . SVG Version: 2.1.0 Build 142)  -->
  <defs>
    <style>
      .st0 {
        fill: #ffdd7e;
      }
    </style>
  </defs>
  <path class="st0" d="M400.7,4.5c1.5-4.8,8.6-6.7,10.3-.9,3.3,11.7,3.5,24.8,3.6,36.9,2.3,16.4,1.2,54.5.7,78.4-.3,18.6-.4,36.7,3.2,54.8.5,10.2-.5,20.5.2,31,0,7.2.5,14.4,1.3,21.6.9,5.3-2.6,10.6-8.4,9.4-9.2-1.6-2.4-13.8-4.3-20-1.1-10.9-2.2-21.8-1.9-32.9-.7-6.1-.4-14.9-.5-20.8-1.3-8-1.1-18.6-1.5-26.3-2.2-13-.7-25.4-1.5-38.4-.3-4.9-.5-10.1-.8-15.1-.4-4,.8-10.4-.6-14.2-3.9-2.4-10,.4-14.4,1.3-3.5,1.3-4,5.6-5.8,8.4-1.7,3-3.3,6-4.2,9.3-1.7,5.5-4.6,10.1-6.4,15.6-1.6,4.6-3.2,12.1-9.1,12.5-5.6-1.7-5.9-10.8-9.4-15.1-8.4-12.8-18.3-24.8-24.9-38.7-1.9-3.9-7.7-3.3-10.6-.9-2.2,2.2-.7,5.1-1,7.8-.3,18.8,1.2,40-1.2,59,0,15.8,0,31.5,1.1,47.1-.2,9-2.6,17.5-1.4,26.7.1,8.5-1.7,17.3-.7,25.7,1.5,7.4-1.2,8.6-7.7,10.4-3.8,2.2-8.4,1.3-8.6-3.9-.8-15.1.9-30.2,1.9-45.3.5-14.5.6-29,1.9-43.5,1-12.7-2.3-26.4,0-39.3.4-11.7-1-22.9-.9-34.3-.9-6.6-2.7-12.9-3.1-19.6.1-5,1.2-9.8,1.4-14.9,0-6.8-.4-14.3-.6-21.1-.1-3.4-2.1-8.5,1.8-10.3,5.9-1.7,6.9,5.8,10.1,9,8.5,11.2,16,22.7,26,32.8,6.8,10.3,13.2,20.6,18.7,31.6,2.2,3.5,2.6,9.2,5.7,12.1,3.3-2.4,4.2-8.3,6.5-11.7,5.2-11.5,11-23.2,16.6-34.6,2.5-6.5,6.2-12.8,9.6-18.5,3.3-6.6,6.1-13.9,8.8-20.9h0Z"/>
  <path class="st0" d="M4.1,4.5c2-2.8,6-1.7,9-1.7,9.5-1.1,18.9.8,28.4.9,11.8,3.1,21.8,11.1,31.2,18.6,8.5,8.4,13.2,19.9,16.6,31.1,7.3,41.1-16.5,46.3-48,60.6-5.1,1.5-10.4,3-15.4,4.6-.4.3-.6.4-.6.9,5.7,7.8,12.9,15,19.6,22.2,4.8,5.7,7.6,12.3,12.9,17.6,4,3.8,8.3,7.6,11.1,12.3,6,11.2,14.4,21.1,20.6,32.1,5.7,7.3,12.1,14.4,17.5,22,3.7,4,3.1,19-4.9,13-4.4-4.1-6.7-9.6-10.6-14.1-14.4-16.1-27.7-33-41.1-50.2-10.2-13.2-22.2-25.2-31.3-39.2-3.6-5.1-2.8,10-2.9,11.7.2,5.9-1.2,11.9-.7,18.1,1.6,16.2.4,33.1.5,49.2.4,5.7,0,11.2-.8,16.8.1,3.4,1.3,9.8-3.7,9.6-9.8-1.7-8.6-4.1-9-12.8.4-6.8-1.8-13.1-1.8-19.8.3-2.2,1.2-4.3,1.3-6.6.1-3.2-.9-5.8-.8-8.9.3-5.7-.7-13.7.6-18.6.3-6.6,0-35.8.1-50.1-.5-12.8-1.4-24.2-1.1-36.9C0,79.2-.3,70.7.3,62.8c1.3-12.5,1.9-25.5,2.9-38.1.4-6.6-1.6-13.8.7-20h0ZM60.3,36c-2,0-4.1.3-5.9.7-4.9.8-11.6.6-16.7,1-3.4.2-6.3,1.2-9.8,1.3-3.6.7-8,.1-11.5,1.1-1.3.7-1.3,1.9-1.3,3.6,0,3.9,0,8.9.6,12.6.3,2.6-.8,5-1,7.5-.3,3.9.3,8.4.1,12.3-.7,4.8-.6,10.6-.7,15.2-.8,4.5-.8,9.8-.6,14.6.1,1.7.6,2.5,2,2.6,2.4,0,5.8-.3,8.2-1.2,4.7-2.2,9.1-4.4,14.1-5.9,4.2-1.2,7.9-3.2,12-5,2.3-1,5.8-2.3,8.8-3.4,3.1-1.2,6.6-2.2,8.9-4.7,1.7-2.4,3.9-3.9,4.3-7,.2-1.6.2-3.3.6-4.9,1.4-4.3,2.1-8.8,1.5-13.3-.9-4.4.9-10.2-2-14.2-2.1-2.7-3.5-5.4-4.9-8.6-1.2-3.2-3-5-6.6-4.5h-.2Z"/>
  <path class="st0" d="M143.5,5.2c5.9-1.7,11.9-1.1,18.1-1,12.6-.6,26.2-.5,38.5-.5,12.1-1.7,24.4-.7,36.6-1.7,5.4-1,12.5,6.3,6.7,10.3-6.1,2.3-13.6,2.2-19.8,3.3-4.8,1.6-10,.7-14.9,1.4-2.4.4-3.4,1.5-6,1.7-16.2.7-32.8.5-48.8,3.3-3.1.9-2.1,4.9-2.5,7.4-1.8,7.9-.8,15.8-.3,23.6.2,19-1.4,43-.6,59.9.8,4.9,8,2,11.4,2.2,10.1.6,19.6.3,29.6-.7,8.2-.5,15.4.8,23.5,1.3,5.5.5,9.8-2,14.9-.6,6.5,2.4,7,8.3,5.8,14.3-.3,3.5-.1,7-1,10.4-3.6,8.3-15,6.8-22.3,6.3-4.3-1.8-6.1-1.6-10.9-1.5-14.4.1-37.6-.3-45.3.2-5.4,0-4,2.2-4.2,6.9.7,17.7-2.1,36.4,2.7,53.4,1.3,3.9-3.1,16.1,4.3,13.5,10.2-.9,30.3-.1,37.8-.4,6.3,1.7,14.6.7,20.7,2.1,10.7.5,27.2-.9,38.3.5,4.4,2.3,4.8,11.1-1.4,11.5-13.5.9-27-1.4-40.4-1.6-7.7-.6-15.1,1.7-22.8,2.8-10.5,1.1-21.2,1.3-31.8,2-17.2,6.6-15.9,2-17.1-13.8.2-11.5-.3-23.3-3-34.6-.5-5.1.3-11.2.3-16.6-.3-7.7-1.5-15-1.4-22.6-1.2-13.5.6-26.7-1.9-40-2.5-20.9-2.9-42.2-2-63.1.6-8.7.9-17.7,3.9-25.7,1.9-4.3,1.3-10.6,5-13.8h.1Z"/>
  <path class="st0" d="M455.5,4.8c1-3.5,5.8-4.4,8.9-3.2,1.8.7,3.2,2.9,4.7,4.4,3,2.8,5.3,6.2,7.4,9.7,2.7,3.9,6.1,8,8.3,12.3,7,10.1,15.6,19.5,22.8,29.7,2.1,2.9,4.6,5.6,6.9,8.3,2,2.5,4.1,5.6,7.7,5.4,7.4-1.1,15.2-2,21.9-5.4,1.9-1.9,2.6-4.9,3.5-7.3,1.2-3.7,2.8-7.4,4.4-10.8,4.9-8.7,10.9-17.6,13.1-27.6,1-5.1.5-11.1,5.2-14.5,4.9-3.5,17-6,13.2,4-1.4,10.1-7.2,18.9-11.7,27.9-.6,1.3-.7,2.7-1,4.1-.4,2.8-2.3,5.3-3.9,7.4-1.3,2.3-2.9,4.3-4,6.7-5.1,12.5-8.1,26.1-13.9,38.4-3,7-7,14-10.2,20.8-4.5,9.3-8.7,19-13.2,28.4-5.6,11.5-10.4,23.4-15.5,35.1-1.4,3.2-2.4,7.1-3.7,10.5-1.8,4.5-4,8.9-5.3,13.6-3.5,10.7-5.7,22.3-11.1,32.1-5.1,7.4-12.8,5.5-9.8-4.4,3.9-16.8,10.2-32.2,17.5-47.7,4.3-12.7,8.4-25.5,12.7-38.3,2-4.8,4.7-7.9,6.2-12.5,1.8-6.4,4.5-11.4,7-17.2,3.9-10.3,4.8-11.2-3.5-19.3-7.2-8-15-15.9-20.9-24.8-7.5-11.3-16.7-21.6-24.5-32.7-2.4-3.1-3.1-7.2-5.4-10.3-3.7-4.5-6.7-9.6-10.5-14.2-2.1-2.3-4.4-5.1-3.4-8.4v-.2Z"/>
</svg>      

    <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator')): ?>
                <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle position-relative" href="#" id="navbarAvatar" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img src="<?php echo htmlspecialchars($avatarPath . $avatarCacheBuster); ?>" alt="User Avatar" class="navbar-avatar">
            <!-- Notification badge on the avatar -->
            <span class="position-absolute top-0 start-100 translate-middle notification-badge notification-count badge rounded-pill bg-danger" style="display: none;">
                0
            </span>
        
                    <div class="dropdown-menu panel-dropdown" aria-labelledby="navbarPanel">
                        <?php if (hasRole('moderator')): ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/moderation.php"><?= __('nav_moderation') ?></a>
                        <?php endif; ?>
                        <?php if (hasRole('admin')): ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/user_admin.php"><?= __('nav_user_admin') ?></a>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/category_panel.php"><?= __('nav_category_panel') ?></a>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/designation_panel.php"><?= __('nav_designation_panel') ?></a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>
                        
            <li class="nav-item">
                <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>forum.php"><?= __('nav_forum') ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>guidelines.php"><?= __('nav_guidelines') ?></a>
            </li>

           

            <!-- Language Switcher -->
            <!-- <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="langDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= $availableLanguages[getCurrentLanguage()] ?>
                </a>
                <ul class="dropdown-menu" aria-labelledby="langDropdown">
                    <?php foreach ($availableLanguages as $code => $name): ?>
                        <li>
                            <a class="dropdown-item <?= getCurrentLanguage() === $code ? 'active' : '' ?>" 
                               href="?lang=<?= $code ?>">
                                <?= $name ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li> -->

            <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="navbarAvatar" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($avatarPath . $avatarCacheBuster); ?>" alt="User Avatar" class="navbar-avatar">
                        <!-- Add notification badge -->
                        <span class="position-absolute top-0 start-100 translate-middle notification-badge badge rounded-pill bg-danger" style="display: none;">
                            0
                        </span>
                    </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarAvatar">
                <!-- Show the most recent 5 notifications at the top -->
                <div class="notifications-wrapper">
                    <div class="notifications-list px-2 py-2">
                        <!-- Notifications will be loaded here via JavaScript -->
                    </div>
                </div>
                    <div class="dropdown-divider"></div>

                        <!-- Language Selection as nested dropdown -->
                        <div class="dropdown-divider"></div>
                        <div class="dropdown"> <!-- Changed from dropend to dropstart -->
                            <a class="dropdown-item menu d-flex justify-content-between align-items-center" href="#" id="languageDropdown" role="button">
                                <i class="fas fa-chevron-left"></i> <!-- Changed from right to left -->
                                <span><?= __('nav_language') ?> (<?= $availableLanguages[getCurrentLanguage()] ?>)</span>
                            </a>
                            <ul class="dropdown-menu dropdown-submenu" aria-labelledby="languageDropdown">
                                <?php foreach ($availableLanguages as $code => $name): ?>
                                    <li>
                                        <a class="dropdown-item submenu <?= getCurrentLanguage() === $code ? 'active' : '' ?>" 
                                        href="?lang=<?= $code ?>">
                                        <?= $name ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php"><?= __('nav_logout') ?></a>
                    </div>
                </li>
            <?php else: ?>
                <!-- Keep the non-logged in version as is -->
                <li class="nav-item">
                    <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>login.php"><?= __('nav_login') ?></a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Mobile Menu (displays below md breakpoint) -->
<div class="mobile-menu d-block d-lg-none">
<button class="mobile-menu-trigger" id="mobileMenuTrigger">
        <div class="d-inline-block position-relative">
            <img src="<?php echo htmlspecialchars($avatarPath . $avatarCacheBuster); ?>" 
                 alt="Menu" 
                 class="mobile-avatar">
            <!-- Notification badge for mobile -->
            <span class="position-absolute top-0 start-100 translate-middle notification-badge badge rounded-pill bg-danger" style="display: none;">
                0
            </span>
        </div>
    </button>
</div>
    
    <div class="mobile-menu-panel" id="mobileMenuPanel">
        <!-- User Header -->
        <div class="mobile-user-header">
            <img src="<?php echo htmlspecialchars($avatarPath . $avatarCacheBuster); ?>" 
                 alt="<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>" 
                 class="mobile-panel-avatar">
            <span class="mobile-username"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
        </div>

        <!-- Notifications Section -->
        <div class="notifications-section">
            <div class="notifications-header d-flex justify-content-between px-3 py-2">
                <span class="fw-bold">Notifications</span>
                <span class="badge bg-danger rounded-pill notification-count" style="display: none;">0</span>
            </div>
            <div class="notifications-list">
                <!-- Notifications will be loaded here via JavaScript -->
            </div>
            <a href="notifications.php" class="dropdown-item text-center py-2">
                <?= __('view_all_notifications') ?>
            </a>
        </div>
    
        <div class="dropdown-divider"></div>

        <!-- Menu Items -->
        <div class="mobile-menu-items">
            <a href="<?php echo BASE_URL; ?>forum.php" class="mobile-menu-item">
                <i class="bi bi-house"></i> <?= __('nav_forum') ?>
            </a>

            <a href="<?php echo BASE_URL; ?>guidelines.php" class="mobile-menu-item">
                <i class="bi bi-info-circle"></i> <?= __('nav_guidelines') ?>
            </a>

            <!-- Language Submenu -->
            <div class="mobile-submenu">
                <button class="mobile-menu-item submenu-trigger">
                    <i class="bi bi-globe"></i> <?= __('nav_language') ?>
                    <i class="bi bi-chevron-down submenu-icon"></i>
                </button>
                <div class="submenu-content">
                    <?php foreach ($availableLanguages as $code => $name): ?>
                        <a href="?lang=<?= $code ?>" 
                           class="mobile-menu-item submenu-item <?= getCurrentLanguage() === $code ? 'active' : '' ?>">
                            <?= $name ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Profile Link -->
            <a href="<?php echo BASE_URL; ?>user.php" class="mobile-menu-item">
                <i class="bi bi-person"></i> <?= __('nav_profile') ?>
            </a>

            <!-- Panel Submenu (if user has permissions) -->
            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator')): ?>
                <div class="mobile-submenu">
                    <button class="mobile-menu-item submenu-trigger">
                        <i class="bi bi-gear"></i> <?= __('nav_panel') ?>
                        <i class="bi bi-chevron-down submenu-icon"></i>
                    </button>
                    <div class="submenu-content">
                        <?php if (hasRole('moderator')): ?>
                            <a href="<?php echo BASE_URL; ?>panel/moderation.php" class="mobile-menu-item submenu-item">
                                <i class="bi bi-shield"></i> <?= __('nav_moderation') ?>
                            </a>
                        <?php endif; ?>
                        <?php if (hasRole('admin')): ?>
                            <a href="<?php echo BASE_URL; ?>panel/user_admin.php" class="mobile-menu-item submenu-item">
                                <i class="bi bi-people"></i> <?= __('nav_user_admin') ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>panel/category_panel.php" class="mobile-menu-item submenu-item">
                                <i class="bi bi-tags"></i> <?= __('nav_category_panel') ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>panel/designation_panel.php" class="mobile-menu-item submenu-item">
                                <i class="bi bi-list-check"></i> <?= __('nav_designation_panel') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Logout Divider and Link -->
            <div class="mobile-menu-divider"></div>
            <a href="<?php echo BASE_URL; ?>logout.php" class="mobile-menu-item">
                <i class="bi bi-box-arrow-right"></i> <?= __('nav_logout') ?>
            </a>
        </div>
    </div>
</div>

<style>
    .navbar-expand-lg .navbar-nav  {
        flex-direction: row;
        align-items: center;
    }

    .bg-light {
        background-color: none !important;
    }

    .navbar-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .dropdown-menu {
        background-color: #ffffff;
    }
    .dropdown-menu>a {
        margin: 0 0;
    }

    .nav-item.dropdown {
        position: relative;
    }

    .dropdown-menu.panel-dropdown {
        margin-top: 5px;
    }

    .dropdown-item.menu {
        margin: 0 0;
    }

    .dropdown-item {
        padding: 0.5rem 1rem;
        width: 90%;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Main dropdown menu styles */
    .nav-item.dropdown > .dropdown-menu,
    .dropdown-menu-right {
        left: auto;
        right: 0;
    }

    /* Show main dropdown on hover for desktop only */
    @media (min-width: 992px) {
        .nav-item.dropdown:hover > .dropdown-menu,
        .nav-item.dropdown:hover > .dropdown-menu-right {
            display: block;
        }

        .nav-item.dropdown .dropdown-toggle::after {
            display: none;
        }
    }

    /* Language submenu specific styles */
    .dropdown-submenu {
    display: none !important; /* Force hide initially */
    position: absolute;
    left: auto;
    right: 100%; /* Changed from left:100% to right:100% */
    top: 0;
    margin-top: -1px;
}
    .dropdown-submenu.show {
        display: block !important;
    }

    ul.dropdown-menu.dropdown-submenu > li:hover {
        background-color: #f8f9fa;
        color: #000;
    }

    .dropdown-item.submenu.active {
        margin: 0 0;
    }

    /* Mobile adjustments */
    @media (max-width: 991px) {
        .navbar-nav .dropdown-menu,
        .dropdown-submenu {
            position: static;
            float: none;
        }

        .dropdown-submenu {
            margin-left: 1rem;
        }
    }

   /* Chevron icon styling */
    .dropdown-item .svg-inline--fa  { 
        font-size: 0.8em;
        margin-right: 0.5rem; 
    }

    .dropdown-item.active {
        background-color: #f8f9fa;
        color: #000;
    }

    .dropstart .dropdown-item {
        display: flex;
        flex-direction: row-reverse; /* Reverse the direction of flex items */
        justify-content: space-between;
    }

    /* Mobile adjustments */
    @media (max-width: 991px) {
        .navbar-nav .dropdown-menu,
        .dropdown-submenu {
            position: static;
            float: none;
        }

        .dropdown-submenu {
            margin-right: 1rem; /* Changed from margin-left to margin-right */
        }
        
        /* Keep the submenu on the left side even on mobile */
        .dropstart .dropdown-item {
            flex-direction: row-reverse;
        }
    }

/* Mobile Menu Styles */
.mobile-menu {
    position: relative;
}

.mobile-menu-trigger {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1020;
    background: none;
    border: none;
    padding: 0;
}

.mobile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mobile-menu-panel {
    position: fixed;
    top: 0;
    right: -100%;
    width: 33%;
    min-width: 230px;
    max-width: 350px;
    height: 100vh;
    background: #fff;
    z-index: 1030;
    transition: right 0.2s ease;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
}

.mobile-menu-panel.active {
    right: 0;
}

.mobile-user-header {
    padding: 1.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-bottom: 1px solid #eee;
}

.mobile-panel-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.mobile-username {
    font-weight: 500;
    font-size: 1.1rem;
}

.mobile-menu-items {
    padding: 1rem 0;
}

.mobile-menu-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: #333;
    text-decoration: none;
    transition: background-color 0.15s;
    gap: 0.75rem;
    width: 100%;
    border: none;
    background: none;
    text-align: left;
}

.mobile-menu-item:hover {
    background-color: #f8f9fa;
    color: #333;
}

.mobile-menu-item.active {
    background-color: #e9ecef;
}

.submenu-trigger {
    justify-content: flex-start;
}

.submenu-icon {
    transition: transform 0.15s;
}

.submenu-trigger[aria-expanded="true"] .submenu-icon {
    transform: rotate(180deg);
}

.submenu-content {
    display: none;
    background-color: #f8f9fa;
}

.submenu-content.active {
    display: block;
}

.submenu-item {
    padding-left: 3rem;
}

.mobile-menu-divider {
    height: 1px;
    background-color: #dee2e6;
    margin: 0.5rem 0;
}

.dropdown-menu {
    min-width: 280px;
    padding: 0.5rem 0;
}

.avatar-small {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.notifications-section {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,.05);
    cursor: pointer;
}

.notification-item:hover {
    background-color: rgba(0,0,0,.03);
}

.notification-item.unread {
    background-color: rgba(13,110,253,.05);
}

.notification-text {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.dropdown-submenu {
    position: absolute;
    left: 100%;
    top: 0;
    margin-top: -1px;
    border-radius: 0 0.25rem 0.25rem 0;
}

.dropdown-item {
    padding: 0.5rem 1rem;
}

.dropdown-item i {
    width: 1.25rem;
    text-align: center;
}

.dropdown-header {
    background-color: rgba(0,0,0,.03);
    padding: 0.75rem 1rem;
}

.notification-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    margin-left: -0.3rem;
    margin-top: 0.1rem;
    z-index: 100;
}

.notifications-wrapper {
    max-height: 300px;
    overflow-y: auto;
}

.notification-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.notification-text {
    font-size: 0.875rem;
    color: #333;
    margin-bottom: 4px;
}

.notification-time {
    font-size: 0.75rem;
    color: #666;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .dropdown-submenu {
        position: static;
        margin-left: 1rem;
    }
}

/* Test Add these styles to your existing CSS */
.mobile-menu-panel .notification-item {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    clear: both;
    font-weight: 400;
    text-align: inherit;
    text-decoration: none;
    white-space: normal;
    background-color: transparent;
    border: 0;
    position: relative;
    z-index: 100; /* Ensure it's above other mobile menu elements */
}

.mobile-menu-panel .notification-item:hover {
    background-color: rgba(0,0,0,.05);
}

.mobile-menu-panel .notifications-list {
    position: relative;
    z-index: 99;
}

/* Ensure mobile menu doesn't interfere with notification clicks */
.mobile-menu-panel .notifications-section {
    pointer-events: auto;
    z-index: 1032;
}
</style>

<script>

    // Translations clientside
    if (typeof __ === 'undefined') {
    function __(key) {
        const translations = {
            'just_now': 'Just now',
            'minutes_ago': '%d minutes ago',
            'hours_ago': '%d hours ago',
            'no_notifications': 'No notifications'
        };
        return translations[key] || key;
         }
    }

     // Format timestamp
     function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // difference in seconds

        if (diff < 60) {
            return __('just_now');
        } else if (diff < 3600) {
            // Replace '%d' in your translation string with the minutes value
            return __('minutes_ago').replace('%d', Math.floor(diff / 60));
        } else if (diff < 86400) {
            // Replace '%d' in your translation string with the hours value
            return __('hours_ago').replace('%d', Math.floor(diff / 3600));
        } else {
            return formatCustomDate(timestamp);
        }
    } 

    // formatCustomDate as JS This formats timestamps that are older than 1 day.
    function formatCustomDate(timestamp) {
        const date = new Date(timestamp);
        // Adjust locale and options as needed (here using German formatting)
        return date.toLocaleDateString('de-DE', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                }) + ' ' +
               date.toLocaleTimeString('de-DE', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
    }
</script>

<!-- Dropdown User Menu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const languageDropdown = document.getElementById('languageDropdown');
    
    if (languageDropdown) {
        languageDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const submenu = this.nextElementSibling;
            if (submenu) {
                // Toggle show class instead of inline style
                submenu.classList.toggle('show');
            }
        });

        // Close submenu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropend')) {
                const submenu = languageDropdown.nextElementSibling;
                if (submenu) {
                    submenu.classList.remove('show');
                }
            }
        });

        // Close submenu when main dropdown closes
        const mainDropdown = languageDropdown.closest('.dropdown-menu');
        if (mainDropdown) {
            document.addEventListener('click', function(e) {
                if (!mainDropdown.contains(e.target)) {
                    const submenu = languageDropdown.nextElementSibling;
                    if (submenu) {
                        submenu.classList.remove('show');
                    }
                }
            });
        }
    }
});

// Mobile Menu JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuTrigger = document.getElementById('mobileMenuTrigger');
    const mobileMenuPanel = document.getElementById('mobileMenuPanel');
    const submenuTriggers = document.querySelectorAll('.submenu-trigger');

    if (mobileMenuTrigger && mobileMenuPanel) {
        mobileMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenuPanel.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!mobileMenuPanel.contains(e.target) && 
                !mobileMenuTrigger.contains(e.target)) {
                mobileMenuPanel.classList.remove('active');
            }
        });

        // Prevent menu close when clicking inside
        mobileMenuPanel.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Submenu toggles
    submenuTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const submenuContent = this.nextElementSibling;
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Close other submenus
            document.querySelectorAll('.submenu-content.active').forEach(submenu => {
                if (submenu !== submenuContent) {
                    submenu.classList.remove('active');
                    submenu.previousElementSibling.setAttribute('aria-expanded', 'false');
                }
            });

            // Toggle current submenu
            submenuContent.classList.toggle('active');
            this.setAttribute('aria-expanded', !isExpanded);
        });
    });
});
</script>

<!-- Notifications -->
<script>
$(document).ready(function() {
    function updateNotifications() {
    $.ajax({
        url: 'get_notifications.php',
        method: 'GET',
        data: { latest: true },
        success: function(data) {
            // Update badge count
            const count = data.unread_count || 0;
            const $badges = $('.notification-badge');
            
            if (count > 0) {
                $badges.text(count).show();
            } else {
                $badges.hide();
            }

            // Get both lists but handle them separately
            const $desktopList = $('.dropdown-menu .notifications-list');
            const $mobileList = $('.mobile-menu-panel .notifications-list');
            
            $desktopList.empty();
            $mobileList.empty();

            if (data.notifications && data.notifications.length > 0) {
                data.notifications.forEach(function(notification) {
                    const details = JSON.parse(notification.details);
                    let notificationText = '';
                    
                    if (notification.type === 'post_reply') {
                        notificationText = `${details.username} commented on your post "${details.post_title}"`;
                    } else if (notification.type === 'private_message') {
                        notificationText = `${details.username} sent you a message`;
                    }

                    // Create desktop notification
                    const $desktopItem = $(`
                        <a href="${notification.link}" 
                           class="notification-item ${notification.is_read ? '' : 'unread'}" 
                           data-notification-id="${notification.id}"
                           data-context="desktop">
                            <div class="notification-text">${escapeHtml(notificationText)}</div>
                            <small class="text-muted">${formatTimestamp(notification.created_at)}</small>
                        </a>
                    `);
                    $desktopList.append($desktopItem);

                    // Create mobile notification
                    const $mobileItem = $(`
                        <a href="${notification.link}" 
                           class="notification-item ${notification.is_read ? '' : 'unread'} mobile-notification" 
                           data-notification-id="${notification.id}"
                           data-context="mobile">
                            <div class="notification-text">${escapeHtml(notificationText)}</div>
                            <small class="text-muted">${formatTimestamp(notification.created_at)}</small>
                        </a>
                    `);
                    $mobileList.append($mobileItem);
                });
            } else {
                const emptyMessage = `
                    <div class="notification-item text-muted text-center">
                        ${__('no_notifications')}
                    </div>
                `;
                $desktopList.append(emptyMessage);
                $mobileList.append(emptyMessage);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching notifications:', error);
        }
    });
}

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Format timestamp helper
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // difference in seconds

        if (diff < 60) return __('just_now');
        if (diff < 3600) return __('minutes_ago').replace('%d', Math.floor(diff / 60));
        if (diff < 86400) return __('hours_ago').replace('%d', Math.floor(diff / 3600));
        
        return formatCustomDate(timestamp);
    }


     // Handle notification clicks
    $(document).on('click', '.notification-item', function(e) {
        // Only process if we have a notification ID
        const $this = $(this);
        const notificationId = $this.data('notification-id');
        
        if (!notificationId) {
            return; // Skip if no notification ID
        }
        
        console.log('Clicked notification:', notificationId);
        
        // Prevent default behavior
        e.preventDefault();
        e.stopPropagation();
        
        const link = $this.attr('href');
        const isMobile = $this.closest('.mobile-menu-panel').length > 0;

        // Mark as read BEFORE navigation
        $.ajax({
            url: 'mark_notification_read.php',
            method: 'POST',
            async: false, // Make this synchronous to ensure it completes
            data: { notification_id: notificationId }
        })
        .done(function(response) {
            console.log('Mark as read response:', response);
            
            if (response.success) {
                // Update UI
                $(`.notification-item[data-notification-id="${notificationId}"]`).removeClass('unread');
                
                // Update badge count
                const newCount = parseInt(response.unread_count);
                const $badges = $('.notification-badge');
                if (newCount > 0) {
                    $badges.text(newCount).show();
                } else {
                    $badges.hide();
                }

                // Close mobile menu if needed
                if (isMobile) {
                    $('#mobileMenuPanel').removeClass('active');
                }
            }
        })
            .fail(function(error) {
                console.error('Error marking notification as read:', error);
            })
        .always(function() {
            // Navigate after AJAX completes, whether it succeeds or fails
            window.location.href = link;
        });


    // Mark as read with direct AJAX call
    $.ajax({
        url: 'mark_notification_read.php',
        method: 'POST',
        data: { notification_id: notificationId },
        success: function(response) {
            console.log('Mark as read response:', response);
            
            if (response.success) {
                // Update all instances of this notification
                $(`.notification-item[data-notification-id="${notificationId}"]`).removeClass('unread');
                
                // Update badge count
                const newCount = parseInt(response.unread_count);
                const $badges = $('.notification-badge');
                
                if (newCount > 0) {
                    $badges.text(newCount).show();
                } else {
                    $badges.hide();
                }

                // Close mobile menu if needed
                if (isMobile) {
                    $('#mobileMenuPanel').removeClass('active');
                }

                // Navigate after updates
                window.location.href = link;
            }
        },
        error: function(xhr, status, error) {
            console.error('Error details:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            // Navigate even if marking as read fails
            window.location.href = link;
        }
    });
});

// Prevent mobile menu from capturing notification clicks
$('.mobile-menu-panel .notifications-section').on('click', function(e) {
    e.stopPropagation();
});

// Initialize notifications and set up periodic updates
updateNotifications();
setInterval(updateNotifications, 30000);// Update every 30 seconds
});

</script>