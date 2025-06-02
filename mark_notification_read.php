<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    error_log('Invalid request: user_id=' . isset($_SESSION['user_id']) . ', notification_id=' . isset($_POST['notification_id']));
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

try {
    error_log('Attempting to mark notification ' . $_POST['notification_id'] . ' as read for user ' . $_SESSION['user_id']);
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                          WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$_POST['notification_id'], $_SESSION['user_id']]);
    
    if ($result) {
        // Get number of rows affected
        $rowCount = $stmt->rowCount();
        error_log('Update affected ' . $rowCount . ' rows');

        // Get updated unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadCount = $stmt->fetchColumn();
        error_log('New unread count: ' . $unreadCount);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'unread_count' => $unreadCount,
            'rows_affected' => $rowCount
        ]);
    } else {
        error_log('Update failed');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to update notification']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>