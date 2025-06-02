<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, form_of_address, first_name, last_name, designation FROM therapists ORDER BY last_name, first_name");
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($therapists);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}