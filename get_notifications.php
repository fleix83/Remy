<?php
require_once 'config/database.php';
require_once 'includes/date_function.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = [];

try {
    // Get unread count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $response['unread_count'] = $stmt->fetchColumn();

    // Get latest 5 notifications
    if (isset($_GET['latest']) && $_GET['latest'] === 'true') {
        $stmt = $pdo->prepare("
            SELECT 
                n.id,
                n.type,
                n.reference_id,
                n.created_at,
                n.is_read,
                CASE 
                    WHEN n.type = 'post_reply' THEN (
                        SELECT JSON_OBJECT(
                            'username', u.username,
                            'post_title', p.title,
                            'comment_content', LEFT(c.content, 100)
                        )
                        FROM comments c 
                        JOIN posts p ON c.post_id = p.id
                        JOIN users u ON c.user_id = u.id
                        WHERE p.id = n.reference_id
                        ORDER BY c.created_at DESC 
                        LIMIT 1
                    )
                    WHEN n.type = 'private_message' THEN (
                        SELECT JSON_OBJECT(
                            'username', u.username,
                            'message_content', LEFT(m.content, 100)
                        )
                        FROM messages m
                        JOIN users u ON m.sender_id = u.id
                        WHERE m.id = n.reference_id
                    )
                END as details,
                CASE 
                    WHEN n.type = 'post_reply' THEN CONCAT('post.php?id=', n.reference_id)
                    WHEN n.type = 'private_message' THEN CONCAT('messages.php?conversation_user=', 
                        (SELECT sender_id FROM messages WHERE id = n.reference_id)
                    )
                END as link
            FROM notifications n
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $response['notifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}
?>