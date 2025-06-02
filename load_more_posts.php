<?php
// load_more_posts.php

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Ensure the user is logged in (for testing, you might comment this out)
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'config/database.php';
require_once 'includes/date_function.php';
require_once 'includes/language_utils.php';

$userDefaultLanguage = getCurrentLanguage();

// Retrieve filters if any (optional)
$filterCategory    = $_GET['category']    ?? '';
$filterTherapist   = $_GET['therapist']   ?? '';
$filterDesignation = $_GET['designation'] ?? '';
$filterDateFrom    = $_GET['date_from']   ?? '';
$filterDateTo      = $_GET['date_to']     ?? '';
$filterCanton      = $_GET['canton']      ?? '';

// Build query conditions
$conditions = [];
$params = [];
if (!empty($filterCategory)) {
    $conditions[] = "posts.category_id = ?";
    $params[] = $filterCategory;
}
if (!empty($filterCanton)) {
    $conditions[] = "posts.canton = ?";
    $params[] = $filterCanton;
}
if (!empty($filterTherapist)) {
    $conditions[] = "posts.therapist = ?";
    $params[] = $filterTherapist;
}
if (!empty($filterDesignation)) {
    $conditions[] = "therapists.designation = ?";
    $params[] = $filterDesignation;
}
if (!empty($filterDateFrom)) {
    $conditions[] = "posts.created_at >= ?";
    $params[] = $filterDateFrom;
}
if (!empty($filterDateTo)) {
    $conditions[] = "posts.created_at <= ?";
    $params[] = $filterDateTo;
}

// Build base query
$categoryField = 'name_' . $userDefaultLanguage;
$sql = "SELECT posts.*, posts.created_at AS post_created_at, 
    users.username, IFNULL(users.avatar_url, 'path/to/default-avatar.png') AS avatar_url, 
    categories.$categoryField AS category,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
    (SELECT MAX(created_at) FROM comments WHERE comments.post_id = posts.id) AS latest_comment_date,
    therapists.form_of_address AS therapist_anrede,
    therapists.first_name AS therapist_vorname,
    therapists.last_name AS therapist_nachname,
    therapists.designation AS therapist_designation,
    therapists.institution AS therapist_institution,
    therapists.canton AS therapist_canton,
    posts.tags, 
    posts.sticky
FROM posts 
JOIN users ON posts.user_id = users.id
JOIN categories ON posts.category_id = categories.id
LEFT JOIN therapists ON posts.therapist = therapists.id
WHERE posts.is_published = 1 AND posts.is_active = 1 AND posts.is_banned = 0
  AND posts.parent_id IS NULL";

if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY posts.sticky DESC, posts.created_at DESC";

// Define limit and offset
$limit = 10;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$sql .= " LIMIT $limit OFFSET $offset";

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON
header('Content-Type: application/json');
echo json_encode($posts);
exit;
