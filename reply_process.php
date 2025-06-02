<?php
require_once 'config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$parent_id = $_POST['parent_id'];
$content = trim($_POST['content']);

if (empty($content)) {
    echo "Bitte geben Sie einen Inhalt ein.";
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, parent_id, content) VALUES (:user_id, :parent_id, :content)");
    $stmt->execute(['user_id' => $user_id, 'parent_id' => $parent_id, 'content' => $content]);
    $post_id = $pdo->lastInsertId();
    header('Location: post.php?id=' . $parent_id);
    exit;
} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
    exit;
}
?>
