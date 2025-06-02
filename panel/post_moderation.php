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
    $stmt = $pdo->prepare("SELECT posts.*, users.username, users.id AS user_id, categories.name_de AS category_name 
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .action-buttons {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        /* Project Specific Card Styles */
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: none;
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        
        .card-title {
            font-family: inherit;
            color: #333;
            font-weight: 600;
        }
        
        .action-btn {
            padding: 0.375rem 0.75rem;
            margin-right: 0.5rem;
            border-radius: 4px;
            font-size: 1rem;
        }

        .action-btn i {
            margin-right: 0.25rem;
        }

        .back-btn {
            margin-bottom: 1rem;
        }

        .message-btn {
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <!-- Back button -->
        <a href="moderation.php" class="btn btn-secondary back-btn">
            <i class="bi bi-arrow-left"></i> Zurück zum Moderations-Panel
        </a>
        
        <div class="card mt-5">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h4 mb-0"><?php echo htmlspecialchars($post['title']); ?></h2>
                </div>
                <div class="text-muted small">
                    By <?php echo htmlspecialchars($post['username']); ?> • 
                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?> <br>
                    <span class="badge bg-primary"><?php echo htmlspecialchars($post['category_name']); ?></span>
                </div>
                <p class="card-text"><?= ($post['content']) ?></p>
                
                <!-- Message Button -->
                <button class="btn btn-outline-primary message-btn" onclick="messageUser(<?php echo $post['user_id']; ?>, <?php echo $post['id']; ?>)">
                    <i class="bi bi-envelope"></i> Nachricht an Benutzer
                </button>
            </div>
        </div>

        <div class="action-buttons">
            <button class="btn btn-danger action-btn" onclick="deletePost(<?php echo $post['id']; ?>)">
                <i class="bi bi-trash"></i> Löschen
            </button>
            <button class="btn btn-warning action-btn" onclick="deactivatePost(<?php echo $post['id']; ?>)">
                <i class="bi bi-shield-exclamation"></i> Deaktivieren
            </button>
            <button class="btn btn-dark action-btn" onclick="blockPost(<?php echo $post['id']; ?>)">
                <i class="bi bi-shield-x"></i> Blockieren
            </button>
            <button class="btn btn-success action-btn" onclick="publishPost(<?php echo $post['id']; ?>)">
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