<?php
require_once '../config/database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $post_id = $_POST['post_id'] ?? '';

    error_log("Received action: $action for post ID: $post_id");

    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'No post ID provided']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        switch ($action) {
            case 'delete':
                // First, delete related records in post_tags table
                $sql_delete_tags = "DELETE FROM post_tags WHERE post_id = :post_id";
                $stmt = $pdo->prepare($sql_delete_tags);
                $stmt->execute(['post_id' => $post_id]);

                // Then, delete the post
                $sql = "DELETE FROM posts WHERE id = :post_id";
                break;
            case 'block':
                $sql = "UPDATE posts SET is_banned = 1, is_active = 0, is_published = 0, is_deactivated = 0 WHERE id = :post_id";
                break;
            case 'publish':
                $sql = "UPDATE posts SET is_published = 1, is_active = 1, is_deactivated = 0, is_banned = 0 WHERE id = :post_id";
                break;
            case 'deactivate':
                $sql = "UPDATE posts SET is_deactivated = 1, is_active = 0, is_published = 0, is_banned = 0 WHERE id = :post_id";
                break;
            case 'unpublish':
                $sql = "UPDATE posts SET is_published = 0, is_active = 0, is_deactivated = 0, is_banned = 0 WHERE id = :post_id";
                break;
            default:
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute(['post_id' => $post_id]);

        if ($result) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Action completed successfully', 'action' => $action]);
        } else {
            $pdo->rollBack();
            error_log("Database error: " . print_r($stmt->errorInfo(), true));
            echo json_encode(['success' => false, 'message' => 'Database error occurred']);
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("PDO Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}