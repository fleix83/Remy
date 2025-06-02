<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_of_address = $_POST['formOfAddress'] ?? '';
    $first_name = $_POST['firstName'] ?? '';
    $last_name = $_POST['lastName'] ?? '';
    $designation = $_POST['therapistDesignation'] ?? '';
    $canton = $_POST['therapistCanton'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($designation) || empty($canton)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO therapists (form_of_address, first_name, last_name, designation, canton) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$form_of_address, $first_name, $last_name, $designation, $canton]);
        
        $therapist_id = $pdo->lastInsertId();
        
        echo json_encode(['success' => true, 'therapist_id' => $therapist_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}