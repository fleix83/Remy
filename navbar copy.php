<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/language_handler.php';
require_once __DIR__ . '/includes/language_utils.php';

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
$avatarPath = BASE_URL . 'uploads/avatars/' . $avatarFilename;

// Generate a unique query parameter to prevent caching
$avatarCacheBuster = '?t=' . time(); // You can also use a hash of the file if preferred
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>forum.php">
        <img src="<?php echo BASE_URL; ?>uploads/logo/logo.png" class="logo">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"  aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <?php
            // Check if the user is logged in and is an admin or moderator
            if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator')):
            ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle badge bg-navlinks" href="#" id="navbarPanel" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Panel
                    </a>
                    <div class="dropdown-menu panel-dropdown" aria-labelledby="navbarPanel">
                        <?php if (hasRole('moderator')): ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/moderation.php">Moderation</a>
                        <?php endif; ?>
                        <?php if (hasRole('admin')): ?>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/user_admin.php">User Admin</a>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/category_panel.php">Category Panel</a>
                            <a class="dropdown-item" href="<?php echo BASE_URL; ?>panel/designation_panel.php">Designation Panel</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endif; ?>
            <!-- Other nav items -->
            <li class="nav-item">
                <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>forum.php">Forum</a>
            </li>
            <li class="nav-item">
                <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>guidelines.php">Community-Guidelines</a>
            </li>


             <!-- Language Switcher -->
             <li class="nav-item dropdown">
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
            </li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Avatar with dropdown menu -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarAvatar" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="<?php echo htmlspecialchars($avatarPath . $avatarCacheBuster); ?>" alt="User Avatar" class="navbar-avatar">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarAvatar">
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>user.php">Benutzerprofil</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout.php">Abmelden</a>
                    </div>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link badge bg-navlinks" href="<?php echo BASE_URL; ?>login.php">Anmelden</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Navbar Styling -->
<style>
    .navbar-expand-lg .navbar-nav  {
        flex-direction: row;
        align-items: center;
    }

    .bg-light {background-color: none !important;}

    /* Custom styles for avatar and dropdown */
    .navbar-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    /* Trigger dropdown on hover */
    @media (min-width: 992px) {
        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
        }
        .nav-item.dropdown .dropdown-toggle::after {
            display: none;
        }
    }

    /* Ensure dropdown menu has a white background */
    .dropdown-menu {
        background-color: #ffffff;
    }

    /* Adjust dropdown position */
    .nav-item.dropdown {
        position: relative;
    }

    .dropdown-menu.panel-dropdown {
        margin-top: 5px;
    }

    /* Style for panel dropdown */
    .navbar-nav .dropdown-menu {
        left: auto;
        right: 0;
    }

    .navbar-nav .dropdown:hover .dropdown-menu {
        display: block;
    }

    .dropdown-item {
        padding: 0.5rem 1rem;
        width: 90%;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Ensure both dropdowns have consistent styling */
    .nav-item.dropdown .dropdown-menu {
        left: auto;
        right: 0;
    }

    @media (max-width: 991px) {
        .navbar-nav .dropdown-menu {
            position: static;
            float: none;
        }
    }
</style>