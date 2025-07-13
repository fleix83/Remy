<?php
session_start();
require_once 'config/database.php';

$response = array('success' => false, 'message' => '', 'data' => null);

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sie sind nicht eingeloggt.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Ungültige Anfrage-Methode.');
    }

    if (!isset($_GET['comment_id'])) {
        throw new Exception('Kommentar-ID fehlt.');
    }

    $comment_id = (int)$_GET['comment_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch comment data
    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.user_id, c.created_at, c.updated_at, c.is_edited,
               u.username, IFNULL(u.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch();

    if (!$comment) {
        throw new Exception('Kommentar nicht gefunden.');
    }

    // Check if user owns the comment or is admin/moderator
    if ($comment['user_id'] != $user_id) {
        // Check if user has admin/moderator privileges
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_role = $stmt->fetchColumn();
        
        if (!in_array($user_role, ['admin', 'moderator'])) {
            throw new Exception('Sie sind nicht berechtigt, diesen Kommentar anzuzeigen.');
        }
    }

    $response['success'] = true;
    $response['data'] = array(
        'id' => $comment['id'],
        'content' => $comment['content'],
        'user_id' => $comment['user_id'],
        'username' => $comment['username'],
        'avatar_url' => $comment['avatar_url'],
        'created_at' => $comment['created_at'],
        'updated_at' => $comment['updated_at'],
        'is_edited' => $comment['is_edited'],
        'can_edit' => ($comment['user_id'] == $user_id || in_array($user_role ?? '', ['admin', 'moderator']))
    );

} catch (PDOException $e) {
    $response['message'] = 'Datenbankfehler: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Always return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>