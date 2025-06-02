<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$draft_id = $_POST['draft_id'];

try {
    $stmt = $pdo->prepare("DELETE FROM post_saved WHERE id = ? AND user_id = ?");
    $stmt->execute([$draft_id, $_SESSION['user_id']]);

    $_SESSION['success_message'] = "Entwurf erfolgreich gelöscht.";
    header('Location: user.php');
} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
}
?>
