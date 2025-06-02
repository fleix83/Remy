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
    <div class="background-swish">
    <div id="logo">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>forum.php">
            <img src="./uploads/logo/empiro_type.png" alt="Empiro Logo" style="width:150px; margin-top: 30px;">
            <!-- <h1 class="logo">MEINE<br>ERFAHRUNG</h1> -->
            <!-- <h1 class="logo">EMPIRO</h1>
            <h2 class="logo">MIT PSYCHOTHERAPEUTEN* <br> IN DER SCHWEIZ</h2> 
            <h2 class="logo">MEINE ERFAHRUNG <BR> MIT PSYCHOTHERAPEUTEN* <br> IN DER SCHWEIZ</h2> -->
        </a>
    </div>
    </div>

    <!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button> -->

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
        </a>
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