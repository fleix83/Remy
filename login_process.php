<?php
require_once 'config/database.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and validate input data
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($email) || empty($password)) {
        echo "Bitte füllen Sie alle Felder aus.";
        exit;
    }

    // Check if the user exists and verify the password
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, avatar, role FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            session_regenerate_id(true); // Prevent session fixation attacks
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar'] = $user['avatar'] ?? 'default-avatar.png';
            $_SESSION['role'] = $user['role'];
            header('Location: forum.php'); // Redirect to the forum or homepage
            exit;
        } else {
            echo "Ungültige E-Mail oder Passwort.";
        }

    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
} else {
    echo "Ungültige Anforderung.";
}

// After successfully updating the user's avatar in the database
if (isset($avatar_filename)) {
    $_SESSION['avatar'] = $avatar_filename;
}

?>


