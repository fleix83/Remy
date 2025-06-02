<?php
require_once 'includes/date_function.php';

function createNotification($pdo, $user_id, $type, $reference_id) {
    // Check user preferences first
    $stmt = $pdo->prepare("SELECT 
        CASE 
            WHEN ? = 'post_reply' THEN post_replies
            WHEN ? = 'private_message' THEN private_messages
        END as preference
        FROM notification_preferences 
        WHERE user_id = ?");
    $stmt->execute([$type, $type, $user_id]);
    $preference = $stmt->fetchColumn();

    // Only create notification if user has enabled this type
    if ($preference) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id) 
                              VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $type, $reference_id]);
    }
    return false;
}