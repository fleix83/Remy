<?php
require_once 'config/database.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'];

    // Delete the post
    try {
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);

        // Redirect back to the user profile after deletion
        header('Location: user.php?status=deleted');
        exit;
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
}
?>
