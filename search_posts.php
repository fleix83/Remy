<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once 'config/database.php';
require_once 'includes/date_function.php';

function searchPosts($query) {
    global $pdo;

    try {
        // Sanitize the query string to prevent SQL injection
        $sanitizedQuery = $pdo->quote($query);

        $sql = "SELECT DISTINCT posts.*, posts.created_at AS post_created_at, 
                users.username, IFNULL(users.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url, 
                categories.name_de AS category,
                (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
                therapists.form_of_address AS therapist_anrede,
                therapists.last_name AS therapist_nachname,
                therapists.first_name AS therapist_vorname,
                therapists.designation AS therapist_berufsbezeichnung,
                therapists.institution AS therapist_institution,
                therapists.canton AS therapist_canton,
                posts.tags
                FROM posts 
                JOIN users ON posts.user_id = users.id
                JOIN categories ON posts.category_id = categories.id
                LEFT JOIN therapists ON posts.therapist = therapists.id
                WHERE (MATCH(posts.title, posts.content) AGAINST($sanitizedQuery IN BOOLEAN MODE)
                    OR therapists.last_name LIKE :like_query1
                    OR therapists.first_name LIKE :like_query2
                    OR therapists.designation LIKE :like_query3
                    OR therapists.institution LIKE :like_query4
                    OR FIND_IN_SET(:tag_query, posts.tags) > 0)
                AND posts.is_published = 1
                AND posts.is_active = 1
                AND posts.is_banned = 0
                ORDER BY posts.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $likeQuery = "%$query%";
        $stmt->execute([
            'like_query1' => $likeQuery,
            'like_query2' => $likeQuery,
            'like_query3' => $likeQuery,
            'like_query4' => $likeQuery,
            'tag_query' => $query
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in searchPosts: " . $e->getMessage() . "\nSQL: " . $sql . "\nParameters: " . json_encode([
            'like_query1' => $likeQuery,
            'like_query2' => $likeQuery,
            'like_query3' => $likeQuery,
            'like_query4' => $likeQuery,
            'tag_query' => $query
        ]));
        throw new Exception("Database error: " . $e->getMessage());
    }
}

try {
    if (isset($_GET['query'])) {
        $query = $_GET['query'];
        error_log("Received search query: " . $query);
        $results = searchPosts($query);
        error_log("Search results: " . json_encode($results));
        echo json_encode($results);
    } else {
        echo json_encode(['error' => 'No query provided']);
    }
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}