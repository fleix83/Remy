<?php
require_once __DIR__ . '/date_function.php';

/**
 * Display a single comment with edit/delete functionality
 * 
 * @param array $comment Comment data with all fields
 * @param int $current_user_id Currently logged in user ID
 * @param string $context Context: 'post' or 'profile'
 * @param int $index Comment index (for first comment styling)
 */
function displayComment($comment, $current_user_id, $context = 'post', $index = 0) {
    $is_owner = ($current_user_id == $comment['user_id']);
    $first_comment_class = ($index === 0 && $context === 'post') ? ' first-comment' : '';
    ?>
    
    <div class="comment mb-3 pb-3 border-bottom<?= $first_comment_class ?>" data-comment-id="<?= $comment['id'] ?>">
        <div class="comment-header d-flex align-items-center mb-2">
            <img src="<?= htmlspecialchars($comment['avatar_url']) ?>" 
                 class="avatar rounded-circle me-2" 
                 alt="Avatar" 
                 style="width: 30px; height: 30px;">
            <div class="comment-meta">
                <strong class="comment-username"><?= htmlspecialchars($comment['username']) ?> • <?= formatCustomDate($comment['created_at']) ?></strong>
                <?php if ($comment['is_edited']): ?>
                    <small class="edited-indicator text-muted ms-2">bearbeitet</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="comment-content">
            <div class="comment-text-display">
                <p class="comment-text mb-0"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
            </div>
            <div class="comment-edit-form" style="display: none;">
                <textarea class="form-control comment-edit-textarea" rows="3"><?= htmlspecialchars($comment['content']) ?></textarea>
                <div class="edit-form-actions mt-2">
                    <button type="button" class="btn btn-sm save-comment-btn">Speichern</button>
                    <button type="button" class="btn btn-sm cancel-edit-btn">Abbrechen</button>
                    <button type="button" class="btn btn-sm delete-comment-btn">Löschen</button>
                </div>
            </div>
        </div>
        
        <?php if ($context === 'post'): ?>
            <div class="comment-actions mt-2">
                <a href="#comments" class="toggle-comment-btn btn btn-sm" data-username="<?= htmlspecialchars($comment['username']) ?>">Antworten</a>
                <?php if ($is_owner): ?>
                    <button type="button" class="btn btn-sm edit-comment-btn">Bearbeiten</button>
                <?php endif; ?>
            </div>
        <?php elseif ($context === 'profile' && $is_owner): ?>
            <div class="comment-actions mt-2">
                <?php if ($comment['post_exists']): ?>
                    <a href="post.php?id=<?= htmlspecialchars($comment['post_id']) ?>" class="btn btn-sm">Zum Post</a>
                <?php endif; ?>
                <button type="button" class="btn btn-sm edit-comment-btn">Bearbeiten</button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
}

/**
 * Display a list of comments
 * 
 * @param array $comments Array of comment data
 * @param int $current_user_id Currently logged in user ID
 * @param string $context Context: 'post' or 'profile'
 * @param string $empty_message Message to show when no comments
 */
function displayCommentsList($comments, $current_user_id, $context = 'post', $empty_message = 'Noch keine Kommentare. Sei der Erste!') {
    if (empty($comments)): ?>
        <p class="text-muted"><?= htmlspecialchars($empty_message) ?></p>
    <?php else: ?>
        <?php foreach ($comments as $index => $comment): ?>
            <?php displayComment($comment, $current_user_id, $context, $index); ?>
        <?php endforeach; ?>
    <?php endif;
}

/**
 * Display comments section for post page
 * 
 * @param array $comments Array of comment data
 * @param int $current_user_id Currently logged in user ID
 * @param int $post_id Post ID for the comment form
 */
function displayPostCommentsSection($comments, $current_user_id, $post_id) {
    ?>
    <!-- <h3 class="card-title mb-4">Antworten</h3> -->
    <?php displayCommentsList($comments, $current_user_id, 'post'); ?>

    <!-- Comments Section -->
    <section id="comments" class="card" style="display: none;">
        <div class="card-body">
            <!-- Add Comment Form -->
            <h4 class="mt-4 mb-3">Neue Antwort</h4>
            <form id="comment-form" action="post.php?id=<?= $post_id ?>" method="post">
                <div class="mb-3">
                    <textarea id="comment_content" name="comment_content" class="form-control" rows="3" placeholder="Deine Antwort..." required></textarea>
                </div>
                <button type="button" class="btn btn-secondary" id="cancel-comment">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Antwort speichern</button>
            </form>
        </div>
    </section>
    <?php
}

/**
 * Display comments section for user profile page
 * 
 * @param array $comments Array of comment data
 * @param int $current_user_id Currently logged in user ID
 */
function displayProfileCommentsSection($comments, $current_user_id) {
    ?>
    <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
        <?php if ($comments): ?>
            <?php foreach ($comments as $comment): ?>
                <div class="user-comment-post mb-3 pb-3 border-bottom" data-comment-id="<?= $comment['id'] ?>">
                    <div class="comment-header">
                        <?php if ($comment['post_exists']): ?>
                            <strong>Kommentar zu:</strong> 
                            <a href="post.php?id=<?= htmlspecialchars($comment['post_id']) ?>">
                                <?= htmlspecialchars($comment['post_title']) ?>
                            </a>
                        <?php else: ?>
                            <strong>Der Post wurde gelöscht.</strong>
                        <?php endif; ?>
                        <?php if ($comment['is_edited']): ?>
                            <small class="edited-indicator text-muted ms-2">bearbeitet</small>
                        <?php endif; ?>
                    </div>
                    <div class="comment-content mt-2">
                        <div class="comment-text-display">
                            <p class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
                        </div>
                        <div class="comment-edit-form" style="display: none;">
                            <textarea class="form-control comment-edit-textarea" rows="3"><?= htmlspecialchars($comment['content']) ?></textarea>
                            <div class="edit-form-actions mt-2">
                                <button type="button" class="btn btn-sm save-comment-btn">Speichern</button>
                                <button type="button" class="btn btn-sm cancel-edit-btn">Abbrechen</button>
                                <button type="button" class="btn btn-sm delete-comment-btn">Löschen</button>
                            </div>
                        </div>
                    </div>
                    <div class="comment-actions mt-2">
                        <?php if ($comment['post_exists']): ?>
                            <a href="post.php?id=<?= htmlspecialchars($comment['post_id']) ?>" class="btn btn-sm">Zum Post</a>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm edit-comment-btn">Bearbeiten</button>
                    </div>
                    <small class="comment-date text-muted">Erstellt am: <?= formatCustomDate($comment['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Keine Kommentare gefunden.</p>
        <?php endif; ?>
    </div>
    <?php
}
?>