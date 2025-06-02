<?php
require_once '../includes/init.php';
require_once __DIR__ . '/../navbar.php';

// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Cache-Control: post-check=0, pre-check=0", false);
// header("Pragma: no-cache");




// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

function fetch_posts($status) {
    global $pdo;
    $query = "SELECT posts.*, users.username, users.id as user_id, categories.name_de AS category_name 
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
            $query .= "AND posts.is_published = 1 AND posts.is_active = 1 AND posts.is_deactivated = 0 AND posts.is_banned = 0";
            break;
    }

    $query .= " ORDER BY posts.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$unpublishedPosts = fetch_posts('unpublished');
$deactivatedPosts = fetch_posts('deactivated');
$bannedPosts = fetch_posts('banned');
$publishedPosts = fetch_posts('published');

?>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Moderation Panel</h1>

        <!-- Tabs navigation -->
        <ul class="nav nav-tabs" id="moderationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="unpublished-tab" data-bs-toggle="tab" data-bs-target="#unpublished" type="button" role="tab">Unpublished</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="deactivated-tab" data-bs-toggle="tab" data-bs-target="#deactivated" type="button" role="tab">Deactivated</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="banned-tab" data-bs-toggle="tab" data-bs-target="#banned" type="button" role="tab">Banned</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="published-tab" data-bs-toggle="tab" data-bs-target="#published" type="button" role="tab">Published</button>
            </li>
        </ul>

        <!-- Tab content -->
        <div class="tab-content" id="moderationTabContent">
            <!-- Unpublished tab -->
            <div class="tab-pane fade show active" id="unpublished" role="tabpanel" aria-labelledby="unpublished-tab">
                <h3>Unpublished Posts</h3>
                <div id="unpublished-posts-container"></div>
            </div>

            <!-- Deactivated tab -->
            <div class="tab-pane fade" id="deactivated" role="tabpanel" aria-labelledby="deactivated-tab">
                <h3>Deactivated Posts</h3>
                <div id="deactivated-posts-container"></div>
            </div>

            <!-- Banned tab -->
            <div class="tab-pane fade" id="banned" role="tabpanel" aria-labelledby="banned-tab">
                <h3>Banned Posts</h3>
                <div id="banned-posts-container"></div>
            </div>

            <!-- Published tab -->
            <div class="tab-pane fade" id="published" role="tabpanel" aria-labelledby="published-tab">
                <h3>Published Posts</h3>
                <div id="published-posts-container"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        loadPosts('unpublished');
        loadPosts('deactivated');
        loadPosts('banned');
        loadPosts('published');

        $('.nav-link').on('shown.bs.tab', function(event) {
            var tabId = $(event.target).attr('id');
            var status = tabId.replace('-tab', '');
            loadPosts(status);
        });
    });

    function loadPosts(status) {
        $.ajax({
            url: 'fetch_posts.php',
            type: 'GET',
            data: { status: status },
            success: function(response) {
                $('#' + status + '-posts-container').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Error loading posts:", error);
            }
        });
    }

    function performAction(action, postId, currentStatus) {
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
                    // Reload all tabs
                    loadPosts('unpublished');
                    loadPosts('deactivated');
                    loadPosts('banned');
                    loadPosts('published');
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

    function deletePost(postId, currentStatus) {
        if (confirm('Are you sure you want to delete this post?')) {
            performAction('delete', postId, currentStatus);
        }
    }

    function blockPost(postId, currentStatus) {
        if (confirm('Are you sure you want to block this post?')) {
            performAction('block', postId, currentStatus);
        }
    }

    function publishPost(postId, currentStatus) {
        if (confirm('Are you sure you want to publish this post?')) {
            performAction('publish', postId, currentStatus);
        }
    }

    function deactivatePost(postId, currentStatus) {
        if (confirm('Are you sure you want to deactivate this post?')) {
            performAction('deactivate', postId, currentStatus);
        }
    }

    function messageUser(userId, postId) {
        window.location.href = 'send_message.php?user_id=' + userId + '&post_id=' + postId;
        }
    </script>
</body>
</html>