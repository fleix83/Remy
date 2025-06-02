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

    // Fetch the post details to move it to drafts
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $user_id]);
        $post = $stmt->fetch();

        if ($post) {
            // Insert the post into the post_saved table
            $stmt = $pdo->prepare("INSERT INTO post_saved (user_id, category_id, canton, therapist, designation, title, content, tags)
                                   VALUES (:user_id, :category, :canton, :therapist, :designation, :title, :content, :tags)");
            $stmt->execute([
                'user_id' => $user_id,
                'category' => $post['category_id'],
                'canton' => $post['canton'],
                'therapist' => $post['therapist'],
                'designation' => $post['designation'],
                'title' => $post['title'],
                'content' => $post['content'],
                'tags' => $post['tags']
            ]);

            // After inserting, delete the post from the posts table
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);

            // Redirect back to the user profile
            header('Location: user.php?status=paused');
            exit;
        } else {
            echo "Post not found or permission denied.";
        }
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
}
?>
