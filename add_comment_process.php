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

    if (!isset($_POST['comment_content']) || !isset($_POST['post_id'])) {
        throw new Exception('Fehlende Daten für den Kommentar.');
    }

    $post_id = (int)$_POST['post_id'];
    $comment_content = trim($_POST['comment_content']);
    $user_id = $_SESSION['user_id'];

    // Validate content
    if (empty($comment_content)) {
        throw new Exception('Kommentarinhalt darf nicht leer sein.');
    }

    if (strlen($comment_content) > 5000) {
        throw new Exception('Kommentar ist zu lang. Maximum 5000 Zeichen.');
    }

    // Verify the post exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Post nicht gefunden.');
    }

    // Process citations first (convert > text to blockquotes)
    $processed_content = processCitations($comment_content);
    
    // Sanitize content with HTML Purifier
    $config = HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li,blockquote');
    $config->set('HTML.AllowedAttributes', 'blockquote.class');
    $purifier = new HTMLPurifier($config);
    $clean_content = $purifier->purify($processed_content);

    // Start transaction
    $pdo->beginTransaction();

    // Insert the comment into database
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, updated_at, is_edited) VALUES (?, ?, ?, NULL, 0)");
    $stmt->execute([$post_id, $user_id, $clean_content]);
    $comment_id = $pdo->lastInsertId();

    // Get post author ID for notification
    $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post_author_id = $stmt->fetchColumn();

    // Create notification for post author (if commenter is not the post author)
    if ($post_author_id != $user_id) {
        require_once 'includes/notification_helpers.php';
        createNotification($pdo, $post_author_id, 'post_reply', $post_id);
    }

    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Kommentar erfolgreich hinzugefügt.';
    $response['data'] = array('comment_id' => $comment_id);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Datenbankfehler: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

function processCitations($content) {
    // Split content into lines
    $lines = explode("\n", $content);
    $processed_lines = [];
    $in_citation = false;
    $citation_content = '';
    
    foreach ($lines as $line) {
        $trimmed_line = trim($line);
        
        // Check if line starts with citation marker
        if (strpos($trimmed_line, '>') === 0) {
            if (!$in_citation) {
                $in_citation = true;
                $citation_content = '';
            }
            // Remove > and trim
            $citation_text = trim(substr($trimmed_line, 1));
            $citation_content .= $citation_text . ' ';
        } else {
            // End of citation block
            if ($in_citation) {
                $processed_lines[] = '<blockquote class="citation-block">' . trim($citation_content) . '</blockquote>';
                $in_citation = false;
                $citation_content = '';
            }
            
            // Add regular line
            if (!empty($trimmed_line) || !$in_citation) {
                $processed_lines[] = $line;
            }
        }
    }
    
    // Handle citation at end of content
    if ($in_citation && !empty($citation_content)) {
        $processed_lines[] = '<blockquote class="citation-block">' . trim($citation_content) . '</blockquote>';
    }
    
    return implode("\n", $processed_lines);
}
?>