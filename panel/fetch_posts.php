<?php
// header('Content-Type: application/json');
require_once '../config/database.php';

function searchPosts($status) {
    global $pdo;
    $query = "SELECT posts.*, posts.created_at AS post_created_at, 
              users.username, IFNULL(users.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url,
              categories.name_de AS category
              FROM posts 
              JOIN users ON posts.user_id = users.id
              JOIN categories ON posts.category_id = categories.id
              WHERE 1=1 ";

    switch ($status) {
        case 'unpublished':
            $query .= "AND posts.is_active = 0 AND posts.is_published = 0 AND posts.is_deactivated = 0 AND posts.is_banned = 0";
            break;
        case 'deactivated':
            $query .= "AND posts.is_deactivated = 1 AND posts.is_banned = 0";
            break;
        case 'banned':
            $query .= "AND posts.is_banned = 1";
            break;
        case 'published':
            $query .= "AND posts.is_published = 1 AND posts.is_active = 1";
            break;
    }

    $query .= " ORDER BY posts.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET['status'])) {
    $posts = searchPosts($_GET['status']);
    
    echo '<table class="table table-hover">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Created</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
    
            foreach ($posts as $post) {
                $avatarUrl = '../' . $post['avatar_url'];
                echo '<tr id="post-' . $post['id'] . '">
                        <td><a href="post_moderation.php?id=' . $post['id'] . '">' . htmlspecialchars($post['title']) . '</a></td>
                        <td>' . date('d/m/Y', strtotime($post['created_at'])) . '</td>
                        <td><img src="' . htmlspecialchars($avatarUrl) . '" 
                                 class="user-avatar" 
                                 data-bs-toggle="tooltip" 
                                 data-bs-placement="top" 
                                 title="' . htmlspecialchars($post['username']) . '"></td>
                        <td>' . htmlspecialchars($post['category']) . '</td>
                        <td class="action-buttons">
                            <button class="btn btn-link p-0 me-2" onclick="deletePost(' . $post['id'] . ', \'' . $_GET['status'] . '\')" 
                                    data-bs-toggle="tooltip" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                            <button class="btn btn-link p-0 me-2" onclick="blockPost(' . $post['id'] . ', \'' . $_GET['status'] . '\')"
                                    data-bs-toggle="tooltip" title="Block">
                                <i class="bi bi-shield-exclamation text-warning"></i>
                            </button>
                            <button class="btn btn-link p-0" onclick="publishPost(' . $post['id'] . ', \'' . $_GET['status'] . '\')"
                                    data-bs-toggle="tooltip" title="Publish">
                                <i class="bi bi-check-circle text-success"></i>
                            </button>
                        </td>
                    </tr>';
            }
    
    echo '</tbody></table>';
}
?>