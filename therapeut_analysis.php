<?php
header('Content-Type: application/json');
require_once 'config/database.php';

// Error reporting for debugging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Check if ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No therapist ID provided']);
    exit;
}

$therapist_id = $_GET['id'];

try {
    // Get therapist details first
    $stmt = $pdo->prepare("
        SELECT id, form_of_address, first_name, last_name, designation, institution, canton
        FROM therapists 
        WHERE id = ?
    ");
    $stmt->execute([$therapist_id]);
    $therapist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$therapist) {
        echo json_encode(['error' => 'Therapist not found']);
        exit;
    }

    // Get all related posts
    $stmt = $pdo->prepare("
        SELECT p.*, u.username 
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.therapist = ? 
            AND p.is_published = 1 
            AND p.is_banned = 0
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$therapist_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data
    $response = [
        'therapist' => $therapist,
        'posts' => array_map(function($post) {
            return [
                'id' => $post['id'],
                'title' => $post['title'],
                'content' => strip_tags($post['content']),
                'date' => $post['created_at'],
                'author' => $post['username']
            ];
        }, $posts),
        'summary' => [
            'total_posts' => count($posts),
            'latest_post' => !empty($posts) ? $posts[0]['created_at'] : null,
        ]
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in therapeut_analysis.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}