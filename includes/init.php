<?php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection using absolute path
require_once __DIR__ . '/../config/database.php';
// require_once __DIR__ . '/../includes/header.php';

// Function to check if the current user is an admin
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'fr', 'it'])) {
    $_SESSION['language_preference'] = $_GET['lang'];
    
    // If user is logged in, update their preference in database
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET language_preference = ? WHERE id = ?");
        $stmt->execute([$_GET['lang'], $_SESSION['user_id']]);
    }
    
    // Redirect to remove the lang parameter from URL
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: $redirectUrl");
    exit;
}

// Any other common initializations...

// Debug: Log session data
error_log("Session data in init.php: " . print_r($_SESSION, true));
?>



