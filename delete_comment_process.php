<?php
session_start();
require_once 'config/database.php';

$response = array('success' => false, 'message' => '', 'data' => null);

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sie sind nicht eingeloggt. Bitte melden Sie sich an und versuchen Sie es erneut.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfrage-Methode.');
    }

    if (!isset($_POST['comment_id'])) {
        throw new Exception('Kommentar-ID fehlt.');
    }

    $comment_id = (int)$_POST['comment_id'];
    $user_id = $_SESSION['user_id'];

    // Verify comment exists and user owns it
    $stmt = $pdo->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        throw new Exception('Kommentar nicht gefunden.');
    }

    if ($comment['user_id'] != $user_id) {
        throw new Exception('Sie sind nicht berechtigt, diesen Kommentar zu löschen.');
    }

    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
    $stmt->execute([$comment_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Kommentar erfolgreich gelöscht.';
        $response['data'] = array('comment_id' => $comment_id);
    } else {
        throw new Exception('Fehler beim Löschen des Kommentars.');
    }

} catch (PDOException $e) {
    $response['message'] = 'Datenbankfehler: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response for AJAX calls
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// For non-AJAX requests, redirect back with status
if ($response['success']) {
    $redirect_url = $_POST['redirect_url'] ?? 'post.php?id=' . ($_POST['post_id'] ?? '');
    header("Location: $redirect_url&status=comment_deleted");
} else {
    $redirect_url = $_POST['redirect_url'] ?? 'post.php?id=' . ($_POST['post_id'] ?? '');
    header("Location: $redirect_url&error=" . urlencode($response['message']));
}
exit;
?>