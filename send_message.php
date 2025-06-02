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
    <title>Nachricht senden</title>
    <link rel="stylesheet" href="path/to/bootstrap.min.css">
</head>
<body>
<header>
    <h1>Nachricht senden</h1>
</header>
<main>
    <div class="container">
        <form action="send_message_process.php" method="post">
            <div class="form-group">
                <label for="receiver">Empfänger:</label>
                <select id="receiver" name="receiver" class="form-control" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, username FROM users");
                    while ($user = $stmt->fetch()) {
                        echo "<option value=\"{$user['id']}\">{$user['username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="content">Nachricht:</label>
                <textarea id="content" name="content" class="form-control" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Senden</button>
        </form>
    </div>
</main>

<!-- Optional JavaScript -->
<script src="path/to/bootstrap.bundle.min.js"></script>
</body>
</html>
