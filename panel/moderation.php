<?php
require_once '../includes/init.php';
require_once '../includes/header.php';
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
<div class="moderation-container">
    <h1 class="panel mt-3 mb-5">Moderation Panel</h1>

    <div class="tabs-wrapper">
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
    </div>

    <div class="tab-content" id="moderationTabContent">
        <div class="tab-pane fade show active" id="unpublished" role="tabpanel">
            <div id="unpublished-posts-container"></div>
        </div>
        <div class="tab-pane fade" id="deactivated" role="tabpanel">
            <div id="deactivated-posts-container"></div>
        </div>
        <div class="tab-pane fade" id="banned" role="tabpanel">
            <div id="banned-posts-container"></div>
        </div>
        <div class="tab-pane fade" id="published" role="tabpanel">
            <div id="published-posts-container"></div>
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

<script id="postTableTemplate" type="text/template">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Title</th>
                <th>Created</th>
                <th>User</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {{#each posts}}
            <tr id="post-{{id}}">
                <td><a href="post_moderation.php?id={{id}}">{{title}}</a></td>
                <td>{{formatDate created_at}}</td>
                <td>
                    <img src="{{avatar_url}}" class="user-avatar" data-bs-toggle="tooltip" data-bs-placement="top" title="{{username}}">
                </td>
                <td>{{category_name}}</td>
                <td>
                    <button class="action-btn delete" onclick="deletePost({{id}}, '{{../status}}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button class="action-btn ban" onclick="blockPost({{id}}, '{{../status}}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Block">
                        <i class="bi bi-shield-exclamation"></i>
                    </button>
                    <button class="action-btn publish" onclick="publishPost({{id}}, '{{../status}}')" data-bs-toggle="tooltip" data-bs-placement="top" title="Publish">
                        <i class="bi bi-check-circle"></i>
                    </button>
                </td>
            </tr>
            {{/each}}
        </tbody>
    </table>
</script>

<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Load initial tab content
    loadPosts('unpublished');

    // Tab change handler
    $('.nav-link').on('shown.bs.tab', function(event) {
        var status = $(event.target).attr('id').replace('-tab', '');
        loadPosts(status);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Prevent body scrolling when scrolling tabs on mobile
    const navTabs = document.querySelector('.nav-tabs');
    if (navTabs) {
        navTabs.addEventListener('touchmove', function(e) {
            e.stopPropagation();
        }, { passive: true });
    }

    // Scroll active tab into view when switching tabs
    const tabButtons = document.querySelectorAll('.nav-link');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(e) {
            e.target.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        });
    });
});

function loadPosts(status) {
    $.ajax({
        url: 'fetch_posts.php',
        type: 'GET',
        data: { status: status },
        success: function(response) {
            $(`#${status}-posts-container`).html(response);
            
            // Reinitialize tooltips for new content
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        },
        error: function(xhr, status, error) {
            console.error("Error loading posts:", error);
        }
    });
}
</body>
</html>