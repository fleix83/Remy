<?php
require_once 'config/database.php';

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent PHP from displaying errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$bio = $_POST['bio'] ?? null;
$username = $_POST['username'] ?? null;
$default_canton = $_POST['default_canton'] ?? null;
$language_preference = $_POST['language_preference'] ?? 'de'; // Default to German if not set

// Handle messages_active
$messages_active = isset($_POST['messages_active']) ? 1 : 0;

$response = ['success' => false, 'message' => ''];

try {
    error_log("Starting user update process for user ID: " . $user_id);
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    $pdo->beginTransaction();

    // Check if this is just a messages_active update
    if (isset($_POST['update_messages_only'])) {
        $messages_active = isset($_POST['messages_active']) ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET messages_active = :messages_active WHERE id = :user_id");
        $stmt->execute([
            ':messages_active' => $messages_active,
            ':user_id' => $user_id
        ]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Einstellung gespeichert']);
        exit;
    }

    // Fetch the current user data
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE users SET bio = :bio, username = :username, default_canton = :default_canton, language_preference = :language_preference, messages_active = :messages_active";
    $params = [
        ':bio' => $bio,
        ':username' => $username,
        ':default_canton' => $default_canton,
        ':language_preference' => $language_preference,
        ':messages_active' => $messages_active,
        ':user_id' => $user_id
    ];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
            // Use the existing avatar filename or create a new one if it doesn't exist
            $avatar_filename = $user['avatar'] ?? (uniqid() . '.jpg');
            $upload_path = __DIR__ . '/uploads/avatars/' . $avatar_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $sql .= ", avatar = :avatar";
                $params[':avatar'] = $avatar_filename;

                // Update the session variable
                $_SESSION['avatar'] = $avatar_filename;
            } else {
                throw new Exception("Fehler beim Hochladen des Avatars. Upload path: " . $upload_path);
            }
        } else {
            throw new Exception("Ungültiger Dateityp oder zu große Datei für Avatar.");
        }
    }

    $sql .= " WHERE id = :user_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $pdo->commit();

    // Update session variables
    $_SESSION['language_preference'] = $language_preference;
    $_SESSION['default_canton'] = $default_canton;

    $response['success'] = true;
    $response['message'] = 'Profil wurde erfolgreich aktualisiert!';
    error_log("Profile updated successfully for user ID: " . $user_id);
} catch (Exception $e) {
    $pdo->rollBack();
    $response['success'] = false;
    $response['message'] = 'Fehler beim Aktualisieren des Profils: ' . $e->getMessage();
    error_log("Error updating profile: " . $e->getMessage());
}

echo json_encode($response);

