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
<nav class="navbar navbar-expand-lg navbar-light bg-light d-none d-lg-block">
    <div class="navbar-brand-container">
        <a href="<?php echo BASE_URL; ?>forum.php" class="brand-link">
            <svg id="logo" data-name="Ebene 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 598.88 259.41">
              <defs>
                <style>
                  .cls-1 {
                    fill: var(--primary);
                  }
                </style>
              </defs>
              <path class="cls-1" d="M63.08,13.18c12.46,4.94,21.21,11.28,29.51,21.8,4.48,6.16,8.9,23.08,9.33,33.61.16,4.11-.19,6.13-1.49,9.33-2.66,6.49-6.25,15.24-10.08,21.67-7.4,11.48-21.02,17.27-33.22,22.91-4.4,2.11-9.64,2.25-13.96,3.57-.71.21-1.38.6-1.03,1.44,11.11,14.95,22.56,29.77,34.79,43.9,8.94,10.04,20.55,17.77,28.78,28.67,4.87,6.08,9.7,12.56,16.58,16.49,3.3,1.99,7.04,2.34,9.85,5.16,3.61,3.85,4.98,10.49,4.68,15.62-.15,1.52-.45,2.27-1.44,3.64-1.51,2.28-4.18,6.1-5.35,7.53-6.2,5.93-12.07,6.91-19.32,2.15-26.41-23.58-43.36-55.16-67.45-80.79-1.82-2.04-3.63-4.08-5.39-6.16-1.15-.83-7.93-12.24-8.12-7.77-.36,19.26.65,38.6,1.88,57.89,1.03,9.27,2.85,18.39,1.68,27.66-.52,3.6-.48,8.72-2.42,11.8-2.91,3.41-9.83,3.56-14.1,4.34-1.72.28-3.28,1.3-5.02,1.52-7.73,1.27-12.04-2.2-11.75-10.26,2.43-32.24,4.84-64.84,4.67-97.15.25-10.58.93-21.31.87-31.81-.13-10.71-1.1-21.03-.97-31.79-.08-23.04,1.56-45.59,1.09-68.25.15-2.57-.48-5.33.64-7.7,2.98-5.24,7.5-7.52,13.6-6.39,8.01.67,16.65,2.8,24.45,2.53,6.38.85,12.47,2.62,18.52,4.78l.19.08ZM32.59,100.21c3.81.27,20.83-3.78,24.22-5.17,3.61-2.39,10.01-6.78,12.59-9.14,6.45-8.87,16.06-27.46,3.02-34.46-4.62-2.68-11.71-4.66-17.62-5.87-6.58-.7-14.08,1.65-20.91,1.81-1.48-.07-2.08,1.33-3.28,1.9-.5.23-1.08.44-1.59.72-3.21,1.8-4.84,5.42-5.15,8.92.62,3.51,1.84,8.9,2.82,13.66,1.6,7.46,4.55,22.17,5.88,27.62h.03Z"/>
              <path class="cls-1" d="M417.79,13.14c8.4,27.75,6.96,57.32,8.19,86.01,1.09,31.15,5.3,61.95,6.42,93.19.76,14.32-.87,30.15,4.7,43.29,3.79,8.5-8.37,11.49-14.18,13.9-1.73,1.13-3.09,3.29-5.44,3.3-23.65,1.51-12.87-125.28-12.57-144.58-.21-2.95.4-24.04-2.14-23.41-2.65.07-5.74,1.39-8.19,1.24-2.1,0-4.65-.07-5.75,2.09-5.39,9.95-9.8,20.36-14.24,30.8-1.34,2.52-1.61,6.73-4.82,7.43-3.73.39-5.55-3.43-7.47-6.01-8.87-10.27-14.29-22.76-22.95-33.07-1.43-1.77-2.68-3.98-4.09-5.67-.58-.56-1.27-.13-2.54.45-2.88,1.37-7.46,3.81-9.07,4.9-.69.45-.82.92-1.01,2.03-.69,34.42-3.86,68.97-1.71,103.41,1.47,15.54,2.97,34.11,10.25,47.47,2.46,10.67-22.79,9.81-28.71,7.52-4.13-1.43-6.5-5.13-6.38-9.6-.54-11.52-.44-23.28-.54-34.79.05-12.87,1.09-25.9.27-38.89-3.15-38.94,3.36-77.72,4.99-116.58.82-8.66-.54-17.35-.11-25.97.32-2.08,1.41-4.4,1.82-6.75.21-1.08.69-2.14,1.67-2.71,2.5-1.46,5.81-.16,7.4,2.13,1.89,2.73,4.56,4.93,6.47,7.75,16.2,21.97,30.66,45.32,48.9,65.63,1.23,1.38,1.74.66,2.46-.59,12.84-23.79,25.95-48.31,34.35-73.97,1.34-2.08,4.76-3.56,6.99-4.58,2.97-1.22,6.12,1.57,6.97,4.47l.06.18Z"/>
              <path class="cls-1" d="M251.02,13.15c4.08,6.46,5.72,17.18.29,23.61-.95,1.57-1.1,3.68-2.94,4.55-12.33,1.61-24.85-2.29-37.25-3.22-7.45-.86-14.88-1.45-22.37-2.02-5.97-.52-13.26-1.67-19.2-1.74-5.2-.33-3.83,1.53-4.36,5.37-2,12.22.08,24.58-1.76,36.87-.66,5.07-.23,32.87-.52,45.52.08.59-.27.99.49,1.29,19.85-.52,39.89-3.59,59.74-4.57,4.74-.11,10.86-.63,14.96,2.11,5.73,6.66,4.07,16.33,3.13,24.36-.7,7.06-5.53,11.48-12.67,11.36-8.3.61-19.62-.98-26.63-.85-10.87.4-21.69,2.6-32.37,4.63-3.21.4-1.64,4.59-1.99,6.83.37,11.27-.92,22.76.46,34,.78,6.1,2.05,12.21,2.7,18.31.15.98.69,1.46,1.73,1.37,7.19-.85,14.5-.82,21.74-1.16,13.19-.56,26.12-3.74,39.26-4.03,6.06-.25,12.23-.29,18.2-1.31,7.65-1.84,14.57-4.53,21.95-2.03,1.92,1.02,3.23,4.33,3.39,6.7.1,1.95-.88,3.79-.9,5.73-.21,3.69-.38,9.56-1.27,13.03-2.93,8.71-14.66,4.46-21.6,5.05-31.96.62-64.22,1.19-95.93,5.96-8.22-1.26-9.63-2.89-9.45-11.16-3.79-52.93-5.78-105.68-7.73-158.75-.24-12.94-.08-26.11,1.43-38.81,1.01-8.24,4.76-15.91,5.64-24.15,1.46-7.91,35.71-3.42,43-4,14.33,0,28.92-.98,43.28-1.85,5.77-.2,13.8-3.24,17.46,2.83l.1.15Z"/>
              <path class="cls-1" d="M479.34,13.19c1.68,2.2,3.57,6.47,5.56,9.04,2.98,3.77,7.26,6.68,10.34,10.4,6.93,8.23,11.36,18.48,18.08,27.09,3.64,4.58,7.88,8.71,11.57,13.25.86.92,2.17,1.29,3.36,1.12,2.76-.32,12.53-2.35,15.99-3.33,1.22-.19,1.59-1.05,2.02-2.13,5.07-12.35,11.99-23.77,17.38-35.93,3.74-7.92,7.24-17.4,14.42-20.33,3.98-1.49,8.68-2.43,12.53.2.96.44,1.87,1.03,2.53,1.88,2.16,2.96,4.76,5.68,5.47,9.33.41,1.91.47,4.93-.15,6.58-13.92,14.63-26.94,35.24-35.44,52.7-3.95,8.13-6.54,15.77-10.31,23.51-1.63,3.55-4.42,7.92-5.87,11.44-1.52,3.65-2.12,6.65-3.45,10.75-3.65,11.27-9.74,22.24-13.93,33.15-9.88,30.19-16.54,61.34-22.59,92.47-.85,1.83-3.33,3.22-5.28,3.24-2.44-.16-5.1-1.31-7.54-1.5-2.56-.33-4.52-1.8-6.45-3.54-1.49-1.31-3.03-2.97-3.91-4.66-1.89-3.31-1.19-8.59-.53-12.3,7.9-38.24,21.8-74.77,35.33-111.18,1.72-4.68,4.4-9.65,5.45-14.44-.08-1.31-1.01-1.86-2.16-3.45-2.85-3.47-7.8-8.86-11.06-12.46-7.85-8.6-14.15-18.58-21.7-27.47-13.18-15.7-26.57-30.35-44.62-41.12-5.67-3.28-9.87-5.57-4.89-12.29,2.44-3.45,7.4-8.03,10.74-10.48,1.02-.7,2.03-1.22,3.26-1.63,4.07-1.43,9.81-1.59,12.82.46,4.44,3.02,9.68,7.54,12.97,11.53l.09.12Z"/>
            </svg>
            <div class="brand-claim">
                <span class="claim-line">FORUM FÜR</span>
                <span class="claim-line">MENSCHEN IN</span>
                <span class="claim-line">PSYCHOTHERAPIE</span>
            </div>
        </a>
    </div> 

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

<!-- Mobile Navigation (displays below lg breakpoint) -->
<div class="mobile-nav d-block d-lg-none">
    <!-- Mobile Brand Area -->
    <div class="mobile-brand-container">
        <a href="<?php echo BASE_URL; ?>forum.php" class="mobile-brand-link">
            <svg id="mobile-logo" data-name="Ebene 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 598.88 259.41">
              <defs>
                <style>
                  .cls-1 {
                    fill: var(--primary);
                  }
                </style>
              </defs>
              <path class="cls-1" d="M63.08,13.18c12.46,4.94,21.21,11.28,29.51,21.8,4.48,6.16,8.9,23.08,9.33,33.61.16,4.11-.19,6.13-1.49,9.33-2.66,6.49-6.25,15.24-10.08,21.67-7.4,11.48-21.02,17.27-33.22,22.91-4.4,2.11-9.64,2.25-13.96,3.57-.71.21-1.38.6-1.03,1.44,11.11,14.95,22.56,29.77,34.79,43.9,8.94,10.04,20.55,17.77,28.78,28.67,4.87,6.08,9.7,12.56,16.58,16.49,3.3,1.99,7.04,2.34,9.85,5.16,3.61,3.85,4.98,10.49,4.68,15.62-.15,1.52-.45,2.27-1.44,3.64-1.51,2.28-4.18,6.1-5.35,7.53-6.2,5.93-12.07,6.91-19.32,2.15-26.41-23.58-43.36-55.16-67.45-80.79-1.82-2.04-3.63-4.08-5.39-6.16-1.15-.83-7.93-12.24-8.12-7.77-.36,19.26.65,38.6,1.88,57.89,1.03,9.27,2.85,18.39,1.68,27.66-.52,3.6-.48,8.72-2.42,11.8-2.91,3.41-9.83,3.56-14.1,4.34-1.72.28-3.28,1.3-5.02,1.52-7.73,1.27-12.04-2.2-11.75-10.26,2.43-32.24,4.84-64.84,4.67-97.15.25-10.58.93-21.31.87-31.81-.13-10.71-1.1-21.03-.97-31.79-.08-23.04,1.56-45.59,1.09-68.25.15-2.57-.48-5.33.64-7.7,2.98-5.24,7.5-7.52,13.6-6.39,8.01.67,16.65,2.8,24.45,2.53,6.38.85,12.47,2.62,18.52,4.78l.19.08ZM32.59,100.21c3.81.27,20.83-3.78,24.22-5.17,3.61-2.39,10.01-6.78,12.59-9.14,6.45-8.87,16.06-27.46,3.02-34.46-4.62-2.68-11.71-4.66-17.62-5.87-6.58-.7-14.08,1.65-20.91,1.81-1.48-.07-2.08,1.33-3.28,1.9-.5.23-1.08.44-1.59.72-3.21,1.8-4.84,5.42-5.15,8.92.62,3.51,1.84,8.9,2.82,13.66,1.6,7.46,4.55,22.17,5.88,27.62h.03Z"/>
              <path class="cls-1" d="M417.79,13.14c8.4,27.75,6.96,57.32,8.19,86.01,1.09,31.15,5.3,61.95,6.42,93.19.76,14.32-.87,30.15,4.7,43.29,3.79,8.5-8.37,11.49-14.18,13.9-1.73,1.13-3.09,3.29-5.44,3.3-23.65,1.51-12.87-125.28-12.57-144.58-.21-2.95.4-24.04-2.14-23.41-2.65.07-5.74,1.39-8.19,1.24-2.1,0-4.65-.07-5.75,2.09-5.39,9.95-9.8,20.36-14.24,30.8-1.34,2.52-1.61,6.73-4.82,7.43-3.73.39-5.55-3.43-7.47-6.01-8.87-10.27-14.29-22.76-22.95-33.07-1.43-1.77-2.68-3.98-4.09-5.67-.58-.56-1.27-.13-2.54.45-2.88,1.37-7.46,3.81-9.07,4.9-.69.45-.82.92-1.01,2.03-.69,34.42-3.86,68.97-1.71,103.41,1.47,15.54,2.97,34.11,10.25,47.47,2.46,10.67-22.79,9.81-28.71,7.52-4.13-1.43-6.5-5.13-6.38-9.6-.54-11.52-.44-23.28-.54-34.79.05-12.87,1.09-25.9.27-38.89-3.15-38.94,3.36-77.72,4.99-116.58.82-8.66-.54-17.35-.11-25.97.32-2.08,1.41-4.4,1.82-6.75.21-1.08.69-2.14,1.67-2.71,2.5-1.46,5.81-.16,7.4,2.13,1.89,2.73,4.56,4.93,6.47,7.75,16.2,21.97,30.66,45.32,48.9,65.63,1.23,1.38,1.74.66,2.46-.59,12.84-23.79,25.95-48.31,34.35-73.97,1.34-2.08,4.76-3.56,6.99-4.58,2.97-1.22,6.12,1.57,6.97,4.47l.06.18Z"/>
              <path class="cls-1" d="M251.02,13.15c4.08,6.46,5.72,17.18.29,23.61-.95,1.57-1.1,3.68-2.94,4.55-12.33,1.61-24.85-2.29-37.25-3.22-7.45-.86-14.88-1.45-22.37-2.02-5.97-.52-13.26-1.67-19.2-1.74-5.2-.33-3.83,1.53-4.36,5.37-2,12.22.08,24.58-1.76,36.87-.66,5.07-.23,32.87-.52,45.52.08.59-.27.99.49,1.29,19.85-.52,39.89-3.59,59.74-4.57,4.74-.11,10.86-.63,14.96,2.11,5.73,6.66,4.07,16.33,3.13,24.36-.7,7.06-5.53,11.48-12.67,11.36-8.3.61-19.62-.98-26.63-.85-10.87.4-21.69,2.6-32.37,4.63-3.21.4-1.64,4.59-1.99,6.83.37,11.27-.92,22.76.46,34,.78,6.1,2.05,12.21,2.7,18.31.15.98.69,1.46,1.73,1.37,7.19-.85,14.5-.82,21.74-1.16,13.19-.56,26.12-3.74,39.26-4.03,6.06-.25,12.23-.29,18.2-1.31,7.65-1.84,14.57-4.53,21.95-2.03,1.92,1.02,3.23,4.33,3.39,6.7.1,1.95-.88,3.79-.9,5.73-.21,3.69-.38,9.56-1.27,13.03-2.93,8.71-14.66,4.46-21.6,5.05-31.96.62-64.22,1.19-95.93,5.96-8.22-1.26-9.63-2.89-9.45-11.16-3.79-52.93-5.78-105.68-7.73-158.75-.24-12.94-.08-26.11,1.43-38.81,1.01-8.24,4.76-15.91,5.64-24.15,1.46-7.91,35.71-3.42,43-4,14.33,0,28.92-.98,43.28-1.85,5.77-.2,13.8-3.24,17.46,2.83l.1.15Z"/>
              <path class="cls-1" d="M479.34,13.19c1.68,2.2,3.57,6.47,5.56,9.04,2.98,3.77,7.26,6.68,10.34,10.4,6.93,8.23,11.36,18.48,18.08,27.09,3.64,4.58,7.88,8.71,11.57,13.25.86.92,2.17,1.29,3.36,1.12,2.76-.32,12.53-2.35,15.99-3.33,1.22-.19,1.59-1.05,2.02-2.13,5.07-12.35,11.99-23.77,17.38-35.93,3.74-7.92,7.24-17.4,14.42-20.33,3.98-1.49,8.68-2.43,12.53.2.96.44,1.87,1.03,2.53,1.88,2.16,2.96,4.76,5.68,5.47,9.33.41,1.91.47,4.93-.15,6.58-13.92,14.63-26.94,35.24-35.44,52.7-3.95,8.13-6.54,15.77-10.31,23.51-1.63,3.55-4.42,7.92-5.87,11.44-1.52,3.65-2.12,6.65-3.45,10.75-3.65,11.27-9.74,22.24-13.93,33.15-9.88,30.19-16.54,61.34-22.59,92.47-.85,1.83-3.33,3.22-5.28,3.24-2.44-.16-5.1-1.31-7.54-1.5-2.56-.33-4.52-1.8-6.45-3.54-1.49-1.31-3.03-2.97-3.91-4.66-1.89-3.31-1.19-8.59-.53-12.3,7.9-38.24,21.8-74.77,35.33-111.18,1.72-4.68,4.4-9.65,5.45-14.44-.08-1.31-1.01-1.86-2.16-3.45-2.85-3.47-7.8-8.86-11.06-12.46-7.85-8.6-14.15-18.58-21.7-27.47-13.18-15.7-26.57-30.35-44.62-41.12-5.67-3.28-9.87-5.57-4.89-12.29,2.44-3.45,7.4-8.03,10.74-10.48,1.02-.7,2.03-1.22,3.26-1.63,4.07-1.43,9.81-1.59,12.82.46,4.44,3.02,9.68,7.54,12.97,11.53l.09.12Z"/>
            </svg>
            <div class="mobile-brand-claim">
                <span class="claim-line">FORUM FÜR</span>
                <span class="claim-line">MENSCHEN IN</span>
                <span class="claim-line">PSYCHOTHERAPIE</span>
            </div>
        </a>
    </div>
    
    <!-- Mobile Menu Trigger -->
    <div class="mobile-menu">
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