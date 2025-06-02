<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';

// HTML Purifier
require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

// // Log the received POST data
// file_put_contents('debug.log', "Received POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// // Log the session data
// file_put_contents('debug.log', "Session data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

$response = array('success' => false, 'message' => '', 'data' => null);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {

            // Fetch the current user's role
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Sie sind nicht eingeloggt. Bitte melden Sie sich an und versuchen Sie es erneut.');
            }
            $user_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_role = $user['role'];

            switch ($_POST['action']) {
                case 'draft':
                case 'publish':
                    // Validate required fields
                    $requiredFields = ['category', 'canton', 'title', 'content'];
                    foreach ($requiredFields as $field) {
                        if (empty($_POST[$field])) {
                            throw new Exception("Bitte füllen Sie alle erforderlichen Felder aus. Das Feld '$field' fehlt.");
                        }
                    }

                    $table = ($_POST['action'] === 'draft') ? 'post_saved' : 'posts';

                    $config = HTMLPurifier_Config::createDefault();
                    $purifier = new HTMLPurifier($config);

                    // Purify the content
                    $clean_content = $purifier->purify($_POST['content']);

                    // Initialize $sticky
                    $sticky = 0;

                    // Check if the user has permission to set the sticky flag
                    if (($user_role === 'admin' || $user_role === 'moderator') && isset($_POST['sticky'])) {
                        $sticky = $_POST['sticky'] == 1 ? 1 : 0;
                    }

                    // Prepare the data array
                    $data = [
                        $_SESSION['user_id'],
                        $_POST['category'],
                        $_POST['canton'],
                        $_POST['therapist'] ?? null,
                        $_POST['designation'] ?? '',
                        $_POST['title'],
                        $clean_content,
                        $_POST['tags'] ?? '',
                        $sticky
                    ];

                    if ($_POST['action'] === 'publish') {
                        $sql = "INSERT INTO posts (user_id, category_id, canton, therapist, designation, title, content, tags, sticky, is_published, is_active, is_banned, is_deactivated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 0)";
                    } else {
                        $sql = "INSERT INTO $table (user_id, category_id, canton, therapist, designation, title, content, tags, sticky) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    }

                    $stmt = $pdo->prepare($sql);
                    $result = $stmt->execute($data);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = ($_POST['action'] === 'draft') ? 'Entwurf erfolgreich gespeichert.' : 'Beitrag zur Moderation eingereicht.';

                        // Handle tags
                        $post_id = $pdo->lastInsertId();
                        $tags = explode(',', $_POST['tags']);
                        foreach ($tags as $tag_name) {
                            $tag_name = trim($tag_name);
                            if (!empty($tag_name)) {
                                // Check if tag exists, if not create it
                                $stmt = $pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
                                $stmt->execute([$tag_name]);

                                $tag_id = $pdo->lastInsertId();
                                if (!$tag_id) {
                                    // If tag already existed, get its ID
                                    $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                                    $stmt->execute([$tag_name]);
                                    $tag_id = $stmt->fetchColumn();
                                }

                                // Associate tag with post
                                $stmt = $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                                $stmt->execute([$post_id, $tag_id]);
                            }
                        }
                    } else {
                        throw new Exception('Fehler beim Speichern des Beitrags. Bitte versuchen Sie es erneut.');
                    }
                    break;

                case 'new_therapist':
                    // Handle new therapist creation
                    $stmt = $pdo->prepare("INSERT INTO therapists (form_of_address, first_name, last_name, designation, institution, canton) VALUES (?, ?, ?, ?, ?, ?)");
                    $result = $stmt->execute([
                        $_POST['form_of_address'],
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['designation'],
                        $_POST['institution'],
                        $_POST['therapist_canton']
                    ]);

                    if ($result) {
                        $newTherapistId = $pdo->lastInsertId();
                        $stmt = $pdo->prepare("SELECT * FROM therapists WHERE id = ?");
                        $stmt->execute([$newTherapistId]);
                        $newTherapist = $stmt->fetch(PDO::FETCH_ASSOC);

                        $response['success'] = true;
                        $response['message'] = 'Neuer Therapeut erfolgreich hinzugefügt.';
                        $response['data'] = $newTherapist;
                    } else {
                        throw new Exception('Fehler beim Hinzufügen des neuen Therapeuten. Bitte versuchen Sie es erneut.');
                    }
                    break;

                default:
                    throw new Exception('Ungültige Aktion. Bitte versuchen Sie es erneut.');
            }
        } else {
            throw new Exception('Keine Aktion angegeben. Bitte versuchen Sie es erneut.');
        }
    } else {
        throw new Exception('Ungültige Anfragemethode. Bitte versuchen Sie es erneut.');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    file_put_contents('debug.log', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Log the response
// file_put_contents('debug.log', "Response: " . print_r($response, true) . "\n", FILE_APPEND);

// error_log("Final response before sending: " . print_r($response, true));

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);