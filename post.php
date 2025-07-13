<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection using absolute path
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/date_function.php';

// Check if user is banned
try {
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && $user['is_banned']) {
        $_SESSION['error'] = "Ihr Account ist derzeit eingeschränkt. Sie haben keinen Zugriff auf diese Seite.";
        header('Location: forum.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error checking user ban status: " . $e->getMessage());
    header('Location: forum.php');
    exit;
}

// Check if post ID is provided in the URL
if (!isset($_GET['id'])) {
    echo "Post ID fehlt.";
    exit;
}

// Success Message or Error after Post edit
if (isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php
        echo $_SESSION['message'];
        unset($_SESSION['message']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php
$post_id = $_GET['id'];

// Fetch the original post along with additional fields and tags
try {
    $stmt = $pdo->prepare("
        SELECT posts.id, posts.title, posts.content, posts.user_id, posts.canton, posts.therapist, posts.designation, 
               posts.created_at,
               users.username, IFNULL(users.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url, 
               categories.name_de AS category,
               posts.tags,
               therapists.form_of_address AS therapist_anrede,
               therapists.last_name AS therapist_nachname,
               therapists.first_name AS therapist_vorname,
               therapists.designation AS therapist_berufsbezeichnung,
               therapists.institution AS therapist_institution,
               therapists.canton AS therapist_canton
        FROM posts 
        JOIN users ON posts.user_id = users.id
        JOIN categories ON posts.category_id = categories.id
        LEFT JOIN therapists ON posts.therapist = therapists.id
        WHERE posts.id = :post_id
        LIMIT 1
    ");
    $stmt->execute(['post_id' => $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo "Post nicht gefunden.";
        exit;
    }

    // Fetch comments for this specific post only
    $stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, comments.created_at, comments.updated_at, comments.is_edited, 
                           users.username, IFNULL(users.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url
                           FROM comments 
                           JOIN users ON comments.user_id = users.id 
                           WHERE comments.post_id = :post_id 
                           ORDER BY comments.created_at ASC");
    $stmt->execute(['post_id' => $post_id]);
    $comments = $stmt->fetchAll();

    if (isset($_GET['id'])) {
        // Mark any notifications for this post as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = ? 
            AND type = 'post_reply' 
            AND reference_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_GET['id']]);
    }
} catch (PDOException $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    
} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
    exit;
}

// Process comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment_content']) && !empty($_POST['comment_content'])) {
        $comment_content = trim($_POST['comment_content']);
        $user_id = $_SESSION['user_id']; // Assuming user is logged in and you have user ID in session

        // Validate content length
        if (strlen($comment_content) > 5000) {
            $_SESSION['error'] = 'Kommentar ist zu lang. Maximum 5000 Zeichen.';
            header("Location: post.php?id=$post_id");
            exit;
        }

        try {
            // Sanitize content with HTML Purifier
            require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li');
            $purifier = new HTMLPurifier($config);
            $clean_content = $purifier->purify($comment_content);

             // Start transaction
             $pdo->beginTransaction();

            // Insert the comment into database with explicit default values for new schema
            $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, updated_at, is_edited) VALUES (?, ?, ?, NULL, 0)");
            $stmt->execute([$post_id, $user_id, $clean_content]);

            // Get post author ID
            $stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post_author_id = $stmt->fetchColumn();

             // Create notification for post author (if commenter is not the post author)
             if ($post_author_id != $user_id) {
                require_once 'includes/notification_helpers.php';
                createNotification($pdo, $post_author_id, 'post_reply', $post_id);
            }

            $pdo->commit();
            
            // Set success message and redirect back to the same post page
            $_SESSION['success'] = 'Kommentar erfolgreich hinzugefügt.';
            header("Location: post.php?id=$post_id");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Fehler beim Speichern des Kommentars: ' . $e->getMessage();
            header("Location: post.php?id=$post_id");
            exit;
        }
    } else {
        $_SESSION['error'] = 'Kommentarinhalt fehlt.';
        header("Location: post.php?id=$post_id");
        exit;
    }
}

// Includes unter AJAX calls
require_once 'navbar.php';
require_once 'includes/summernote.php';
require_once 'includes/comment_display.php';
require_once __DIR__ . '/includes/header.php';
?>

<head>
<!-- CSS für Peek CSS <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/custom.css"> -->
</head>

<main class="container-fluid px-0 my-1">
    <div class="row justify-content-center">
        <div class="col-lg-11 col-md-12 col-sm-12">
            <!-- <article class="post-post-container card shadow-sm mb-4"> -->
                <div class="post-element">
                    <!-- Post Meta Top -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="post-meta">
                            <span class="badge bg-erfahrung me-2"><?= htmlspecialchars($post['category']) ?></span>
                            <img src="uploads/kantone/<?= htmlspecialchars($post['canton']) ?>.png" alt="<?= htmlspecialchars($post['canton']) ?> Flagge" style="width: 20px; height: 20px;" class="me-1">
                            <small class="post-post-user"><?= htmlspecialchars($post['canton']) ?></small>
                        </div>
                        <!-- <div class="d-flex align-items-center">
                            <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="avatar rounded-circle me-2" alt="Avatar">
                            <span class="post-post-user"><?= htmlspecialchars($post['username']) ?></span>
                        </div> -->
                    </div>

                    <!-- Post Title -->
                    <div class="col-md-8">
                        <!-- <h2 class="post-title card-title"><?= htmlspecialchars($post['title']) ?></h2> -->
                        
                        <div class="d-flex align-items-center mt-2">
                            <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="avatar rounded-circle me-2" alt="Avatar">
                            <span class="post-post-user"><?= htmlspecialchars($post['username']) ?> • <?= formatCustomDate($post['created_at']) ?></span>
                        </div>
                    </div>

                    <!-- Therapist and Designation -->
                    <!-- <div class="therapist-info">
                        <?php if ($post['category'] === 'Erfahrung' && $post['therapist']): ?>
                            <div class="therapist-info">
                                <span><small>Erfahrung mit</small></span>
                                <a href="therapeut_profil.php?id=<?= htmlspecialchars($post['therapist']) ?>" class="therapist-link">
                                    <?php
                                    $therapistDetails = [];
                                    if (!empty($post['therapist_anrede'])) $therapistDetails[] = htmlspecialchars($post['therapist_anrede']);
                                    if (!empty($post['therapist_vorname'])) $therapistDetails[] = htmlspecialchars($post['therapist_vorname']);
                                    if (!empty($post['therapist_nachname'])) $therapistDetails[] = htmlspecialchars($post['therapist_nachname']);
                                    if (!empty($post['therapist_berufsbezeichnung'])) $therapistDetails[] = htmlspecialchars($post['therapist_berufsbezeichnung']);
                                    if (!empty($post['therapist_institution'])) $therapistDetails[] = htmlspecialchars($post['therapist_institution']);
                                    // if (!empty($post['therapist_canton'])) $therapistDetails[] = htmlspecialchars($post['therapist_canton']);
                                    
                                    echo implode(', ', $therapistDetails);
                                    ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div> -->

                    <!-- Post Content -->
                    <div class="post-content">
                        <div class="card-text.post-post"><?php if ($post['category'] === 'Erfahrung' && $post['therapist']): ?>
                            
                            <div class="therapist-lead">
                                <a href="therapeut_profil.php?id=<?= htmlspecialchars($post['therapist']) ?>" class="therapist-link">  <i class="bi bi-bullseye"></i> Erfahrung mit 
                                    <?php
                                    $therapistDetails = [];
                                    if (!empty($post['therapist_anrede'])) $therapistDetails[] = htmlspecialchars($post['therapist_anrede']);
                                    if (!empty($post['therapist_vorname'])) $therapistDetails[] = htmlspecialchars($post['therapist_vorname']);
                                    if (!empty($post['therapist_nachname'])) $therapistDetails[] = htmlspecialchars($post['therapist_nachname']);
                                    if (!empty($post['therapist_berufsbezeichnung'])) $therapistDetails[] = htmlspecialchars($post['therapist_berufsbezeichnung']);
                                    if (!empty($post['therapist_institution'])) $therapistDetails[] = htmlspecialchars($post['therapist_institution']);
                                    // if (!empty($post['therapist_canton'])) $therapistDetails[] = htmlspecialchars($post['therapist_canton']);
                                    
                                    echo implode(', ', $therapistDetails);
                                    ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?= $post['content'] ?>
                        </div>
                        
                        <!-- Display Tags -->
                        <?php if (!empty($post['tags'])): ?>
                            <div class="post-tags mt-3">
                                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                    <span class="badge bg-tags me-1"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Button to reveal comments section - Antworten -->
                    <a href="#comments" class="btn btn-outline-primary btn-sm" data-username="<?= htmlspecialchars($post['username']) ?>">Antworten</a>                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['user_id']): ?>
                    <a href="edit_post.php?id=<?= $post['id'] ?>" class="btn btn-outline-primary btn-sm">Beitrag bearbeiten</a>
                    <?php endif; ?>
                    <?php
                    // Check if user is logged in and not viewing their own post
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $post['user_id']) {
                        // Check if private messages are enabled for both users and no blocks exist
                        $stmt = $pdo->prepare("
                            SELECT 
                                visitor.messages_active AS visitor_active,
                                author.messages_active AS author_active,
                                (SELECT COUNT(*) FROM user_blocks 
                                WHERE (blocker_id = ? AND blocked_id = ?) 
                                    OR (blocker_id = ? AND blocked_id = ?)
                                ) as is_blocked
                            FROM users visitor, users author 
                            WHERE visitor.id = ? AND author.id = ?
                        ");
                        $stmt->execute([
                            $_SESSION['user_id'], $post['user_id'],  // For first block check
                            $post['user_id'], $_SESSION['user_id'],  // For second block check
                            $_SESSION['user_id'], $post['user_id']   // For users lookup
                        ]);
                        $message_settings = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($message_settings && 
                            $message_settings['visitor_active'] && 
                            $message_settings['author_active'] && 
                            !$message_settings['is_blocked']) {
                            ?>
                            <a href="messages.php?author_id=<?= $post['user_id'] ?>&post_id=<?= $post['id'] ?>" 
                               class="btn btn-outline-primary btn-sm ms-2 mb-5">Private Mitteilung</a>
                            <?php
                        }
                    }
                    ?>


                    <?php displayPostCommentsSection($comments, $_SESSION['user_id'], $post_id); ?>
                </div>

            <!-- </article> -->
        </div>           
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Comment Management -->
<script src="assets/js/comment-manager.js"></script>

<!-- Comment Section (Reply functionality) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const commentSection = document.getElementById('comments');
    const toggleButtons = document.querySelectorAll('.toggle-comment-btn');
    const cancelButton = document.getElementById('cancel-comment');
    const commentTextarea = document.querySelector('#comment_content');

    // Handle all toggle buttons (for replies)
    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent default anchor behavior
            
        // Show comment section if hidden
        if (commentSection && commentSection.style.display === 'none') {
            commentSection.style.display = 'block';
        }

        // Add @username to textarea if it's a reply to a comment
        const username = this.dataset.username;
        if (username && commentTextarea) {
            commentTextarea.value = `@${username} `;
        }

        // Scroll to comment form smoothly
        if (commentSection) {
            commentSection.scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Hide the clicked button
        this.style.display = 'none';

        // Focus on the comment textarea and place cursor at the end
        if (commentTextarea) {
            commentTextarea.focus();
            commentTextarea.setSelectionRange(commentTextarea.value.length, commentTextarea.value.length);
        }
    });
});

    // Handle cancel button click
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            // Clear the textarea
            if (commentTextarea) {
                commentTextarea.value = '';
            }
            
            // Hide the comment section
            if (commentSection) {
                commentSection.style.display = 'none';
            }
            
            // Show all answer buttons again
            toggleButtons.forEach(button => {
                button.style.display = 'inline-block';
            });
        });
    }
});
</script>

</body>
<?php require_once 'includes/footer.php'; ?>
</html>