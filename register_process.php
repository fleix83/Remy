<?php
require_once 'config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and validate input data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo "Bitte füllen Sie alle Felder aus.";
        exit;
    }

    if ($password !== $confirm_password) {
        echo "Die Passwörter stimmen nicht überein.";
        exit;
    }

    // Check if the username or email is already taken
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            echo "Der Benutzername oder die E-Mail-Adresse ist bereits vergeben.";
            exit;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $result = $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        if ($result) {
            // Registration successful, redirect to login page or homepage
            header('Location: login.php');
            exit;
        } else {
            echo "Fehler bei der Registrierung. Bitte versuchen Sie es erneut.";
        }

    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
    }
} else {
    echo "Ungültige Anforderung.";
}
?>
