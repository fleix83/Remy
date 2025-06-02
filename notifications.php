<?php
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/date_function.php';
require_once 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            n.id,
            n.type,
            n.reference_id,
            n.created_at,
            n.is_read,
            CASE 
                WHEN n.type = 'post_reply' THEN (
                    SELECT CONCAT(u.username, ' commented on your post: ', p.title)
                    FROM comments c 
                    JOIN posts p ON c.post_id = p.id
                    JOIN users u ON c.user_id = u.id
                    WHERE p.id = n.reference_id
                    ORDER BY c.created_at DESC 
                    LIMIT 1
                )
                WHEN n.type = 'private_message' THEN (
                    SELECT CONCAT(u.username, ' sent you a message')
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    WHERE m.id = n.reference_id
                )
            END as notification_text,
            CASE 
                WHEN n.type = 'post_reply' THEN CONCAT('post.php?id=', n.reference_id)
                WHEN n.type = 'private_message' THEN CONCAT('messages.php?conversation_user=', (
                    SELECT sender_id FROM messages WHERE id = n.reference_id
                ))
            END as link
        FROM notifications n
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
</head>
<body>
    <main class="container p-3 mt-4">
        <h2>Notifications</h2>
        <?php if (empty($notifications)): ?>
            <p class="text-muted">No notifications yet.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <a href="<?= htmlspecialchars($notification['link']) ?>" 
                       class="list-group-item list-group-item-action <?= $notification['is_read'] ? '' : 'active' ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <small><?= formatCustomDate($notification['created_at']) ?></small>
                            <p class="mb-1"><?= htmlspecialchars($notification['notification_text']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>