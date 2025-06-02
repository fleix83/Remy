<?php
require_once 'config/database.php';
require_once 'includes/notification_helpers.php';
require_once 'includes/date_function.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver'];
    $content = $_POST['content'];

    try {
        $pdo->beginTransaction();

        // Send message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$sender_id, $receiver_id, $content]);
        
        // Get the message ID for the notification reference
        $message_id = $pdo->lastInsertId();
        
        // Create notification for receiver
        createNotification($pdo, $receiver_id, 'private_message', $message_id);
        
        $pdo->commit();
        header('Location: inbox.php');
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Fehler beim Senden der Nachricht: " . $e->getMessage();
    }
}
?>