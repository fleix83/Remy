<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and has moderator privileges
if (!isset($_SESSION['user_id']) /* || !is_moderator($_SESSION['user_id']) */) {
    header('Location: ../login.php');
    exit;
}

$recipient_id = $_GET['user_id'] ?? null;
$post_id = $_GET['post_id'] ?? null;

if (!$recipient_id || !$post_id) {
    die("Missing user ID or post ID");
}

// Fetch post details
try {
    $stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Post not found");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';

    if (empty($message)) {
        $error = "Message cannot be empty";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES (?, ?, ?, NOW())");
            $result = $stmt->execute([$_SESSION['user_id'], $recipient_id, $message]);

            if ($result) {
                $success = "Message sent successfully";
            } else {
                $error = "Failed to send message";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Message to User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Send Message to User</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="post-title" class="form-label">Regarding Post:</label>
                <input type="text" class="form-control" id="post-title" value="<?php echo htmlspecialchars($post['title']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message:</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
            <a href="moderation.php" class="btn btn-secondary">Back to Moderation Panel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>