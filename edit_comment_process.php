<?php
session_start();
require_once 'config/database.php';

// HTML Purifier for content sanitization
require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

$response = array('success' => false, 'message' => '', 'data' => null);

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sie sind nicht eingeloggt. Bitte melden Sie sich an und versuchen Sie es erneut.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Ungültige Anfrage-Methode.');
    }

    if (!isset($_POST['comment_id']) || !isset($_POST['content'])) {
        throw new Exception('Fehlende Daten für die Kommentar-Bearbeitung.');
    }

    $comment_id = (int)$_POST['comment_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    // Validate content
    if (empty($content)) {
        throw new Exception('Kommentarinhalt darf nicht leer sein.');
    }

    if (strlen($content) > 5000) {
        throw new Exception('Kommentar ist zu lang. Maximum 5000 Zeichen.');
    }

    // Verify comment exists and user owns it
    $stmt = $pdo->prepare("SELECT user_id, content FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        throw new Exception('Kommentar nicht gefunden.');
    }

    if ($comment['user_id'] != $user_id) {
        throw new Exception('Sie sind nicht berechtigt, diesen Kommentar zu bearbeiten.');
    }

    // Check if content actually changed
    if ($comment['content'] === $content) {
        $response['success'] = true;
        $response['message'] = 'Keine Änderungen vorgenommen.';
        $response['data'] = array(
            'content' => htmlspecialchars($content),
            'is_edited' => false
        );
    } else {
        // Sanitize content with HTML Purifier
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li');
        $purifier = new HTMLPurifier($config);
        $clean_content = $purifier->purify($content);

        // Update comment
        $stmt = $pdo->prepare("UPDATE comments SET content = ?, updated_at = NOW(), is_edited = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$clean_content, $comment_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Kommentar erfolgreich aktualisiert.';
            $response['data'] = array(
                'content' => nl2br(htmlspecialchars($clean_content)),
                'is_edited' => true
            );
        } else {
            throw new Exception('Fehler beim Aktualisieren des Kommentars.');
        }
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
    header("Location: $redirect_url&status=comment_updated");
} else {
    $redirect_url = $_POST['redirect_url'] ?? 'post.php?id=' . ($_POST['post_id'] ?? '');
    header("Location: $redirect_url&error=" . urlencode($response['message']));
}
exit;
?>