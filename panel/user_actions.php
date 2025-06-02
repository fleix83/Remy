<?php

require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Ensure no output has been sent yet
if (headers_sent()) {
    $output = ob_get_clean();
    error_log("Headers already sent. Output: " . $output);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
    exit;
}

header('Content-Type: application/json');

// Define is_admin() if not defined
if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}

// Debug: Log the session data
error_log("Session data in user_actions.php: " . print_r($_SESSION, true));

// Check if user is an admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if (!$action) {
        echo json_encode(['success' => false, 'message' => 'No action provided']);
        exit;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'No user ID provided']);
        exit;
    }

    try {
        switch ($action) {
            case 'delete_user':
                // Start a transaction
                $pdo->beginTransaction();

                try {
                    // Delete user's comments
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
                    $stmt->execute([$user_id]);

                    // Delete user's posts
                    $stmt = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
                    $stmt->execute([$user_id]);

                    // Delete user's messages
                    $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
                    $stmt->execute([$user_id, $user_id]);

                    // Delete user's post tags
                    $stmt = $pdo->prepare("
                        DELETE pt FROM post_tags pt
                        INNER JOIN posts p ON pt.post_id = p.id
                        WHERE p.user_id = ?
                    ");
                    $stmt->execute([$user_id]);

                    // Finally, delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);

                    // Commit the transaction
                    $pdo->commit();

                    echo json_encode(['success' => true, 'message' => 'User and all associated data deleted successfully']);
                } catch (Exception $e) {
                    // An error occurred; rollback the transaction
                    $pdo->rollBack();
                    throw new Exception('Failed to delete user: ' . $e->getMessage());
                }
                break;

            case 'update_role':
                $new_role = $_POST['role'] ?? '';
                if (in_array($new_role, ['user', 'moderator', 'admin'])) {
                    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                    $stmt->execute([$new_role, $user_id]);

                    echo json_encode(['success' => true, 'message' => 'User role updated successfully']);
                } else {
                    throw new Exception('Invalid role');
                }
                break;

            case 'update_status':
                $is_active = $_POST['is_active'] ?? '';
                if ($is_active !== '') {
                    // Convert string to boolean
                    $is_active = filter_var($is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($is_active === null) {
                        throw new Exception('Invalid status value');
                    }
                    $is_banned = $is_active ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
                    $stmt->execute([$is_banned, $user_id]);

                    echo json_encode(['success' => true, 'message' => 'User status updated successfully']);
                } else {
                    throw new Exception('Invalid status value');
                }
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        // Log the exception message for debugging
        error_log('Error in user_actions.php: ' . $e->getMessage());

        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
