<?php
require_once '../includes/init.php';
require_once '../includes/header.php';
require_once '../config/database.php';

// Check if user is logged in and has moderator privileges
if (!isset($_SESSION['user_id']) /* || !is_moderator($_SESSION['user_id']) */) {
    header('Location: ../login.php');
    exit;
}

$post_id = $_GET['id'] ?? null;

if (!$post_id) {
    die("No post ID provided");
}

try {
    $stmt = $pdo->prepare("SELECT posts.*, users.username, users.id AS user_id, users.avatar_url, categories.name_de AS category_name 
                           FROM posts 
                           JOIN users ON posts.user_id = users.id
                           JOIN categories ON posts.category_id = categories.id
                           WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        die("Post not found");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Moderation Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        /* Post Moderation Specific Styling */
        .moderation-container {
            background: var(--background-element);
            border-radius: 20px;
            padding: 20px 30px;
            margin: 18px 0;
            min-height: 1rem;
        }
        
        .post-title {
            color: var(--primary);
            font-size: 1.0em;
            font-weight: 700;
            line-height: 1.2rem;
            margin: 0 0 8px 0;
        }
        
        .post-meta {
            color: var(--type-meta);
            font-size: 0.775rem;
            font-weight: 600;
            line-height: 0.8rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .post-meta-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .post-meta-user {
            display: flex;
            align-items: center;
        }
        
        .post-avatar {
            width: 17px;
            height: 17px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .action-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--background-element);
            padding: 15px;
            border-radius: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        
        .action-btn {
            background: transparent;
            border: 1px solid var(--primary);
            border-radius: 5px;
            color: var(--primary);
            padding: 0.375rem 0.75rem;
            margin-right: 0.5rem;
            margin-bottom: 10px;
            font-size: 1rem;
            transition: all 0.2s ease-in-out;
        }

        .action-btn:hover {
            background: var(--primary);
            color: var(--background-element);
        }

        .action-btn i {
            margin-right: 0.25rem;
        }

        .back-btn {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            margin-bottom: 1rem;
            transition: all 0.2s ease-in-out;
        }
        
        .back-btn:hover {
            background: var(--primary);
            color: var(--background-element);
        }

        .message-btn {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
            margin: 1rem 0;
            transition: all 0.2s ease-in-out;
        }
        
        .message-btn:hover {
            background: var(--primary);
            color: var(--background-element);
        }
        
        .message-btn i {
            margin-right: 3px;
        }
        
        .category-badge {
            background: var(--primary);
            color: var(--background-element);
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.3em 0.6em;
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Back button -->
        <a href="moderation.php" class="btn back-btn">
            <i class="bi bi-arrow-left"></i> Zurück zum Moderations-Panel
        </a>
        
        <div class="moderation-container mt-5">
            <!-- Category Badge Top Left -->
            <div class="post-meta-top">
                <span class="category-badge"><?php echo htmlspecialchars($post['category_name']); ?></span>
            </div>
            
            <!-- Post Title -->
            <h2 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h2>
            
            <!-- User Info Above Content -->
            <div class="post-meta-user">
                <?php 
                $avatar_url = !empty($post['avatar_url']) ? '../' . $post['avatar_url'] : '../uploads/avatars/default-avatar.png';
                ?>
                <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="post-avatar">
                <div class="post-meta">
                    Von <?php echo htmlspecialchars($post['username']); ?> • 
                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                </div>
            </div>
            
            <div class="post-content"><?= ($post['content']) ?></div>
            
            <!-- Message Button -->
            <button class="btn message-btn" onclick="messageUser(<?php echo $post['user_id']; ?>, <?php echo $post['id']; ?>)">
                <i class="bi bi-envelope"></i> Nachricht an Benutzer
            </button>
        </div>

        <div class="action-buttons">
            <button class="btn action-btn" onclick="deletePost(<?php echo $post['id']; ?>)">
                <i class="bi bi-trash"></i> Löschen
            </button>
            <button class="btn action-btn" onclick="deactivatePost(<?php echo $post['id']; ?>)">
                <i class="bi bi-shield-exclamation"></i> Deaktivieren
            </button>
            <button class="btn action-btn" onclick="blockPost(<?php echo $post['id']; ?>)">
                <i class="bi bi-shield-x"></i> Blockieren
            </button>
            <button class="btn action-btn" onclick="publishPost(<?php echo $post['id']; ?>)">
                <i class="bi bi-check-circle"></i> Veröffentlichen
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function performAction(action, postId) {
        $.ajax({
            url: 'post_actions.php',
            type: 'POST',
            data: {
                action: action,
                post_id: postId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.href = 'moderation.php';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                alert('An error occurred while processing your request. Check the console for more details.');
            }
        });
    }

    function deletePost(postId) {
        if (confirm('Sind Sie sicher, dass Sie diesen Beitrag löschen möchten?')) {
            performAction('delete', postId);
        }
    }

    function deactivatePost(postId) {
        if (confirm('Sind Sie sicher, dass Sie diesen Beitrag deaktivieren möchten?')) {
            performAction('deactivate', postId);
        }
    }

    function blockPost(postId) {
        if (confirm('Sind Sie sicher, dass Sie diesen Beitrag blockieren möchten?')) {
            performAction('block', postId);
        }
    }

    function publishPost(postId) {
        if (confirm('Sind Sie sicher, dass Sie diesen Beitrag veröffentlichen möchten?')) {
            performAction('publish', postId);
        }
    }

    function messageUser(userId, postId) {
        window.location.href = 'send_message.php?user_id=' + userId + '&post_id=' + postId;
    }
    </script>
</body>
</html>