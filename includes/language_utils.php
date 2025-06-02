<?php
function getCurrentLanguage() {
    if (isset($_SESSION['language_preference'])) {
        return $_SESSION['language_preference'];
    } elseif (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT language_preference FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $lang = $stmt->fetchColumn();
        $_SESSION['language_preference'] = $lang;
        return $lang;
    }
    return 'de'; // Default to German if no preference is set
}