<?php require_once 'includes/header.php'; ?>
<?php
require_once 'config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posteingang</title>
    <link rel="stylesheet" href="path/to/bootstrap.min.css">
</head>
<body>
<header>
    <h1>Posteingang</h1>
</header>
<main>
    <div class="container">
        <?php
        $stmt = $pdo->prepare("SELECT messages.id, messages.content, messages.created_at, users.username AS sender FROM messages JOIN users ON messages.sender_id = users.id WHERE receiver_id = ? ORDER BY messages.created_at DESC");
        $stmt->execute([$user_id]);
        $messages = $stmt->fetchAll();

        foreach ($messages as $message) {
            echo "<div class=\"card mb-3\">";
            echo "<div class=\"card-body\">";
            echo "<h5 class=\"card-title\">Von: " . htmlspecialchars($message['sender']) . "</h5>";
            echo "<p class=\"card-text\">" . nl2br(htmlspecialchars($message['content'])) . "</p>";
            echo "<p class=\"card-text\"><small class=\"text-muted\">" . htmlspecialchars($message['created_at']) . "</small></p>";
            echo "</div>";
            echo "</div>";
        }
        ?>
    </div>
</main>
<footer>
    <p>&copy; 2023 Ihr Forum</p>
</footer>

<!-- Optional JavaScript -->
<script src="path/to/bootstrap.bundle.min.js"></script>
</body>
</html>
