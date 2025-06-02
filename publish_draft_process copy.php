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
    $draft_id = $_POST['draft_id'];

    // Fetch the draft post from post_saved table
    try {
        $stmt = $pdo->prepare("SELECT * FROM post_saved WHERE id = ? AND user_id = ?");
        $stmt->execute([$draft_id, $user_id]);
        $draft = $stmt->fetch();

        if ($draft) {
            // Insert the draft into the posts table (publish)
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, canton, therapist, designation, title, content, tags)
                                   VALUES (:user_id, :category, :canton, :therapist, :designation, :title, :content, :tags)");
            $stmt->execute([
                'user_id' => $user_id,
                'category' => $draft['category_id'],
                'canton' => $draft['canton'],
                'therapist' => $draft['therapist'],
                'designation' => $draft['designation'],
                'title' => $draft['title'],
                'content' => $draft['content'],
                'tags' => $draft['tags']
            ]);

            // After publishing, delete the draft from post_saved table
            $stmt = $pdo->prepare("DELETE FROM post_saved WHERE id = ? AND user_id = ?");
            $stmt->execute([$draft_id, $user_id]);

            // Redirect back to the user profile after publishing
            header('Location: user.php?status=published');
            exit;
        } else {
            echo "Draft not found or permission denied.";
        }
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
}
?>
