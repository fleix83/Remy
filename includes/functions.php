<?php
require_once 'config/database.php';

// Function to create a new post (new)
function create_post($user_id, $category_id, $canton, $therapist, $designation, $title, $content, $tags) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, category_id, canton, therapist, designation, title, content, tags, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $category_id, $canton, $therapist, $designation, $title, $content, $tags]);
        
        // Return the ID of the newly created post
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        echo "Fehler beim Erstellen des Posts: " . $e->getMessage();
        return false;
    }
}



// Function to update user profile
function update_user($user_id, $username, $biography) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, biography = ? WHERE id = ?");
        return $stmt->execute([$username, $biography, $user_id]);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}

// Function to update user avatar
function update_avatar($user_id, $avatar_path) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        return $stmt->execute([$avatar_path, $user_id]);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error updating avatar: " . $e->getMessage());
        return false;
    }
}

// Function to get all posts
function get_posts($limit = 10, $offset = 0) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.username, c.name as category_name 
                               FROM posts p 
                               JOIN users u ON p.user_id = u.id 
                               JOIN categories c ON p.category_id = c.id 
                               ORDER BY p.created_at DESC 
                               LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error getting posts: " . $e->getMessage());
        return false;
    }
}

// Function to get a single post
function get_post($post_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT p.*, u.username, c.name as category_name 
                               FROM posts p 
                               JOIN users u ON p.user_id = u.id 
                               JOIN categories c ON p.category_id = c.id 
                               WHERE p.id = ?");
        $stmt->execute([$post_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error getting post: " . $e->getMessage());
        return false;
    }
}

// Function to add a comment
function add_comment($post_id, $user_id, $content) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        return $stmt->execute([$post_id, $user_id, $content]);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error adding comment: " . $e->getMessage());
        return false;
    }
}

// Function to get comments for a post
function get_comments($post_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT c.*, u.username 
                               FROM comments c 
                               JOIN users u ON c.user_id = u.id 
                               WHERE c.post_id = ? 
                               ORDER BY c.created_at ASC");
        $stmt->execute([$post_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error getting comments: " . $e->getMessage());
        return false;
    }
}

// Function to anonymize a user
function anonymize_user($user_id) {
    global $pdo;
    try {
        $random_username = 'user_' . bin2hex(random_bytes(5));
        $random_avatar = 'default_avatar.png'; // You should have a default avatar image
        $stmt = $pdo->prepare("UPDATE users SET username = ?, avatar = ?, biography = '' WHERE id = ?");
        return $stmt->execute([$random_username, $random_avatar, $user_id]);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error anonymizing user: " . $e->getMessage());
        return false;
    }
}

// Function to get categories
function get_categories() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error
        error_log("Error getting categories: " . $e->getMessage());
        return false;
    }
}

// Add more functions as needed...

?>