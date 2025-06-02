<?php 
require_once 'includes/init.php';
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Define $user_id from session
$user_id = $_SESSION['user_id'];

// Determine which user profile to show
$profile_user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];
$is_own_profile = ($profile_user_id == $_SESSION['user_id']);

$is_admin_or_moderator = ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator');

$languages = [
    'de' => ['name' => 'Deutsch', 'flag' => 'de'],
    'fr' => ['name' => 'Français', 'flag' => 'fr'],
    'it' => ['name' => 'Italiano', 'flag' => 'it']
];

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$profile_user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        die("User not found");
    }
} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
    exit;
}

// Fetch user posts
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name_de AS category_name 
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.user_id = :user_id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['user_id' => $profile_user_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
    exit;
}

// Fetch saved drafts (only for own profile)
$drafts = [];
if ($is_own_profile) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM post_saved WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$profile_user_id]);
        $drafts = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
        exit;
    }
}

// Fetch user comments and check if the parent post exists (only for own profile)
$comments = [];
if ($is_own_profile) {
    try {
        $stmt = $pdo->prepare("SELECT c.*, p.title AS post_title, p.id AS post_exists FROM comments c 
                               LEFT JOIN posts p ON c.post_id = p.id 
                               WHERE c.user_id = ? 
                               ORDER BY c.created_at DESC");
        $stmt->execute([$profile_user_id]);
        $comments = $stmt->fetchAll();
    } catch (PDOException $e) {
        echo "Fehler: " . $e->getMessage();
        exit;
    }
}

// Fetch direct messages (only for own profile)
$messages = [];
if ($is_own_profile) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE receiver_id = ? OR sender_id = ?");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt->execute([$profile_user_id, $profile_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Fehler beim Laden der Nachrichten: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        echo "SQL State: " . $e->getSQLState() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

    // Handle Unblocking a User
    if (isset($_POST['unblock_user_id'])) {
        $unblock_user_id = $_POST['unblock_user_id'];
        $stmt = $pdo->prepare("DELETE FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
        $stmt->execute([$user_id, $unblock_user_id]);
        // Redirect or provide feedback
        header('Location: user.php');
        exit;
    }

    // Handle Blocking a User
    if (isset($_POST['block_username'])) {
        $block_username = $_POST['block_username'];

        // Get the user ID of the username entered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$block_username]);
        $blockedUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($blockedUser) {
            $blocked_id = $blockedUser['id'];

            // Check if already blocked
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
            $stmt->execute([$user_id, $blocked_id]);
            $alreadyBlocked = $stmt->fetchColumn();

            if (!$alreadyBlocked) {
                // Insert into user_blocks
                $stmt = $pdo->prepare("INSERT INTO user_blocks (blocker_id, blocked_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $blocked_id]);
                // Provide feedback
                $message = "User '$block_username' has been blocked.";
            } else {
                $message = "User '$block_username' is already blocked.";
            }
        } else {
            $message = "User '$block_username' not found.";
        }
        // Redirect or display message
        header('Location: user.php');
        exit;
    }

// Navbar nach AJAX requests
require_once 'navbar.php';
?>

<style>
     .canton-display {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }
    
    .canton-label {
        font-weight: 500;
        color: #666;
    }
    
    .canton-flag-small {
        width: 20px;
        height: 20px;
        object-fit: cover;
        border-radius: 3px;
    }
    
    #defaultCanton {
        max-width: 300px;
    }
    </style>


<?php
$cantons = [
    "AG" => "Aargau",
    "AI" => "Appenzell Innerrhoden",
    "AR" => "Appenzell Ausserrhoden",
    "BE" => "Bern",
    "BL" => "Basel-Landschaft",
    "BS" => "Basel-Stadt",
    "FR" => "Freiburg",
    "GE" => "Genf",
    "GL" => "Glarus",
    "GR" => "Graubünden",
    "JU" => "Jura",
    "LU" => "Luzern",
    "NE" => "Neuenburg",
    "NW" => "Nidwalden",
    "OW" => "Obwalden",
    "SG" => "St. Gallen",
    "SH" => "Schaffhausen",
    "SO" => "Solothurn",
    "SZ" => "Schwyz",
    "TG" => "Thurgau",
    "TI" => "Tessin",
    "UR" => "Uri",
    "VD" => "Waadt",
    "VS" => "Wallis",
    "ZG" => "Zug",
    "ZH" => "Zürich"
];
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<body>
    <div class="container col-md-12">
    <div class="profile-box col-md-8">
    <?php if ($is_own_profile): ?>
        <form id="profileForm" action="user_update_process.php" method="post" enctype="multipart/form-data">
        <div class="avatar-container">
            <img id="profileImage" src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? 'default.png', ENT_QUOTES, 'UTF-8') ?>" alt="Profilbild">
            <div class="avatar-overlay">
                <i class="fas fa-camera"></i>
                <span>Change Avatar</span>
            </div>
            <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;">
        </div>

    <div id="profileInfo">
        <h2 id="usernameDisplay"><?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        <p id="bioDisplay"><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?: 'Kein Profiltext hinterlegt.' ?></p>
    
        <div class="language-display">
            <span class="language-label">Bevorzugte Sprache:</span>
            <?php if (!empty($user['language_preference'])): ?>
                <!-- <img src="uploads/flags/<?= htmlspecialchars($languages[$user['language_preference']]['flag']) ?>.png" 
                    alt="<?= htmlspecialchars($languages[$user['language_preference']]['name'] ?? '') ?>" 
                    class="language-flag-small"> -->
                    <span id="languageDisplay"><?= htmlspecialchars($languages[$user['language_preference']]['name'] ?? 'Nicht festgelegt') ?></span>
            <?php else: ?>
                <span id="languageDisplay">Nicht festgelegt</span>
            <?php endif; ?>
        </div>

        <div class="canton-display">
                <span class="canton-label">Standard Kanton:</span>
                <?php if (!empty($user['default_canton'])): ?>
                    <img src="uploads/kantone/<?= htmlspecialchars($user['default_canton']) ?>.png" 
                        alt="<?= htmlspecialchars($cantons[$user['default_canton']] ?? '') ?>" 
                        class="canton-flag-small">
                <span id="cantonDisplay"><?= htmlspecialchars($cantons[$user['default_canton']] ?? 'Nicht festgelegt') ?></span>
                <?php else: ?>
                    <span id="cantonDisplay">Alle Kantone</span>
                <?php endif; ?>
        </div>

        <div class="private-messages-status mt-3">
            <span class="messages-label">Private Nachrichten: &nbsp;</span>
            <span class="messages-value"><?= $user['messages_active'] ? 'An' : 'Aus' ?></span>
        </div>
    </div>

    <div id="profileEdit" style="display: none;">
    <input type="text" id="usernameInput" name="username" class="form-control mb-2" 
           value="<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <textarea id="bioInput" name="bio" class="form-control mb-2" rows="4" 
              placeholder="Dein Profiltext..."><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
              
              <!-- Default Language selection -->
              <div class="default-language mb-3">
                <label for="language_preference" class="form-label">Sprache / Langue / Lingua</label>
                <select class="form-control" id="language_preference" name="language_preference">
                    <option value="de" <?= ($user['language_preference'] == 'de') ? 'selected' : '' ?>>Deutsch</option>
                    <option value="fr" <?= ($user['language_preference'] == 'fr') ? 'selected' : '' ?>>Français</option>
                    <option value="it" <?= ($user['language_preference'] == 'it') ? 'selected' : '' ?>>Italiano</option>
                </select>
            </div>
            
            <!-- Default Canton selection -->
            <div class="form-group default-canton">
                    <label for="defaultCanton">Standard Kanton:</label>
                    <select id="defaultCanton" name="default_canton" class="form-control mb-2">
                        <option value="">Alle Kantone</option>
                        <?php foreach ($cantons as $code => $name): ?>
                            <option value="<?= htmlspecialchars($code) ?>" 
                                    <?= ($user['default_canton'] == $code) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                    <!-- Private Messages on/off -->
                    <div class="form-group private-messages-toggle">
                            <label class="d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                        class="form-check-input" 
                                        id="messages_active" 
                                        name="messages_active" 
                                        value="1" 
                                        <?= $user['messages_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="messages_active">Private Nachrichten erlauben</label>
                                </div>
                            </label>
                        </div>
            </div>
            <button type="button" id="editBtn" class="btn btn-outline-secondary mt-3">Ändern</button>
            <button type="submit" id="saveBtn" class="btn btn-primary mt-3" style="display: none;">Speichern</button>
        </form>
        
            <!-- Blocked Users Section -->
            <div class="user-block">
            <h3>Blockierte User</h3>
            <ul id="blocked-users-list">
                <?php
                // Fetch blocked users
                $stmt = $pdo->prepare("
                    SELECT u.id, u.username, IFNULL(u.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url
                    FROM user_blocks ub
                    JOIN users u ON ub.blocked_id = u.id
                    WHERE ub.blocker_id = ?
                ");
                $stmt->execute([$user_id]);
                $blockedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($blockedUsers as $blockedUser):
                ?>
                    <li class="blocked-user-item d-flex align-items-center" data-user-id="<?= $blockedUser['id'] ?>">
                        <img src="<?= htmlspecialchars($blockedUser['avatar_url']) ?>" alt="Avatar" class="avatar-img me-2">
                        <span><?= htmlspecialchars($blockedUser['username']) ?></span>
                        <form action="user.php" method="post" class="ms-auto">
                            <input type="hidden" name="unblock_user_id" value="<?= $blockedUser['id'] ?>">
                            <button type="submit" class="action-btn unblock-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Unblock">
                                <i class="bi bi-shield-check"></i>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Form to Block a New User -->
            <form action="user.php" method="post" class="block-user-form">
                <div class="form-group">
                    <label for="block_username">User blockieren</label>
                    <div class="input-group">
                        <input type="text" 
                               id="block_username"
                               name="block_username" 
                               class="form-control" 
                               placeholder="Username eingeben" 
                               required>
                        <button type="submit" class="action-btn">
                            <i class="bi bi-shield-exclamation"></i>
                        </button>
                    </div>
                </div>
            </form>
               
            <?php else: ?>
                    <!-- Display-only profile information for other users -->
                    <img src="uploads/avatars/<?= htmlspecialchars($user['avatar'] ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="Profilbild">
                    <h2><?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES, 'UTF-8') ?: 'Kein Profiltext hinterlegt.' ?></p>
                <?php endif; ?>
            </div>

        <!-- Tab Nav (only for own profile) -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <?php if ($is_own_profile): ?>
                <li class="nav-item">
                    <a class="nav-link active" id="drafts-tab" data-toggle="tab" href="#drafts" role="tab" aria-controls="drafts" aria-selected="false">Entwürfe</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link <?= $is_own_profile ? '' : 'active' ?>" id="posts-tab" data-toggle="tab" href="#posts" role="tab" aria-controls="posts" aria-selected="true">Posts</a>
            </li>
            <?php if ($is_own_profile): ?>
                <li class="nav-item">
                    <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab" aria-controls="comments" aria-selected="false">Kommentare</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" id="messages-tab" data-toggle="tab" href="#messages" role="tab" aria-controls="messages" aria-selected="false">Direktnachrichten</a>
                </li> -->
            <?php endif; ?>
        </ul>
      
        <?php if ($is_own_profile): ?>
        <div class="tab-content" id="myTabContent">
                <!-- Drafts tab (only for own profile) -->
                <div class="tab-pane fade show active" id="drafts" role="tabpanel" aria-labelledby="drafts-tab">
                    <?php if ($drafts): ?>
                        <?php foreach ($drafts as $draft): ?>
                            <div class="user-draft-post d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h3><?= htmlspecialchars($draft['title']) ?></h3>
                                    <p><?= ($draft['content']) ?></p>
                                    <small>Entwurf gespeichert am: <?= htmlspecialchars($draft['created_at']) ?></small>
                                </div>
                                <div>
                                    <form action="delete_draft_process.php" method="post" class="d-inline-block">
                                        <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draft['id']) ?>">
                                        <button type="submit" class="btn btn-danger">Löschen</button>
                                    </form>
                                    <form action="publish_draft_process.php" method="post" class="d-inline-block">
                                        <input type="hidden" name="draft_id" value="<?= htmlspecialchars($draft['id']) ?>">
                                        <button type="submit" class="btn btn-primary">Publizieren</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Keine Entwürfe gefunden.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Published posts -->
            <div class="tab-pane fade <?= $is_own_profile ? '' : 'show active' ?>" id="posts" role="tabpanel" aria-labelledby="posts-tab">
                <?php if ($posts && ($is_own_profile || $is_admin_or_moderator)): ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="user-post-post d-flex flex-column mb-3">
                            <?php
                            // Determine post status
                            if ($post['is_banned']) {
                                $status = 'Geblockt';
                                $statusClass = 'text-danger border border-danger';
                            } elseif ($post['is_deactivated']) {
                                $status = 'Pause';
                                $statusClass = 'text-secondary border border-secondary';
                            } elseif ($post['is_published'] && $post['is_active']) {
                                $status = 'Publiziert';
                                $statusClass = 'text-success border border-success';
                            } else {
                                $status = 'Moderation';
                                $statusClass = 'text-secondary border border-secondary';
                            }
                            ?>
                            <div class="align-self-start mb-2">
                                <span class="badge <?= $statusClass ?> rounded-pill"><?= $status ?></span>
                            </div>
                            <h3><a href="post.php?id=<?= htmlspecialchars($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                            <div class="post-meta mb-2">
                                <span class="badge bg-primary me-2"><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></span>
                            </div>
                            <p><?= ($post['content']) ?>...</p>
                            <small>Erstellt am: <?= htmlspecialchars($post['created_at']) ?></small>
                            <?php if ($is_own_profile): ?>
                                <div class="mt-2">
                                    <form action="pause_post_process.php" method="post" class="d-inline-block">
                                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                        <button type="submit" class="btn btn-warning btn-sm">Pausieren</button>
                                    </form>
                                    <form action="delete_post_process.php" method="post" class="d-inline-block">
                                        <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!$is_own_profile && !$is_admin_or_moderator): ?>
                    <p>Keine Berechtigung zum Anzeigen der Posts.</p>
                <?php else: ?>
                    <p>Keine Posts gefunden<?= (!$is_own_profile && !$is_admin_or_moderator) ? ' oder keine Berechtigung zum Anzeigen' : '' ?>.</p>
                <?php endif; ?>
            </div>

            <?php if ($is_own_profile): ?>
                <!-- Comments tab (only for own profile) -->
                <div class="tab-pane fade" id="comments" role="tabpanel" aria-labelledby="comments-tab">
                    <?php if ($comments): ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="user-comment-post mb-3 pb-3 border-bottom">
                                <div>
                                    <?php if ($comment['post_exists']): ?>
                                        <strong>Kommentar zu:</strong> 
                                        <a href="post.php?id=<?= htmlspecialchars($comment['post_id']) ?>">
                                            <?= htmlspecialchars($comment['post_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <strong>Der Post wurde gelöscht.</strong>
                                    <?php endif; ?>
                                    <p><?= ($comment['content']) ?></p>
                                    <small>Erstellt am: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($comment['created_at']))) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Keine Kommentare gefunden.</p>
                    <?php endif; ?>
                </div>

                <!-- Messages Tab (only for own profile) 
                <div class="tab-pane fade" id="messages" role="tabpanel" aria-labelledby="messages-tab">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message">
                                <p><?= htmlspecialchars($message['content']) ?></p>
                                <small>Gesendet von User <?= htmlspecialchars($message['sender_id']) ?> an User <?= htmlspecialchars($message['receiver_id']) ?></small>
                                <small>Erstellt am: <?= htmlspecialchars($message['created_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Keine Direktnachrichten gefunden.</p>
                    <?php endif; ?>
                </div> -->
            <?php endif; ?>
        </div> 
    </div>

     <!-- Condition visible only own profile -->
    <?php if ($is_own_profile): ?>

        <!-- JavaScript to handle bio edit/save (only for own profile) -->
        <script>
document.addEventListener('DOMContentLoaded', function () {
    const profileImage = document.getElementById('profileImage');
    const avatarInput = document.getElementById('avatarInput');
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const profileForm = document.getElementById('profileForm');
    const profileInfo = document.getElementById('profileInfo');
    const profileEdit = document.getElementById('profileEdit');
    const avatarOverlay = document.querySelector('.avatar-overlay');

    if (avatarOverlay && avatarInput && profileImage) {
        avatarOverlay.addEventListener('click', function() {
            avatarInput.click();
        });

        avatarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (editBtn && saveBtn && profileInfo && profileEdit) {
        editBtn.addEventListener('click', function() {
            profileInfo.style.display = 'none';
            profileEdit.style.display = 'block';
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
        });
    }

    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(profileForm);

            fetch('user_update_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profil erfolgreich aktualisiert!');
                    location.reload();
                } else {
                    alert('Fehler beim Aktualisieren des Profils: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
            });
        });
    }
});
</script>

        <?php endif; ?>


        <!-- Initialsing Bootstrap  -->
        <script>
        document.addEventListener('DOMContentLoaded', function () {
        var triggerTabList = [].slice.call(document.querySelectorAll('#myTab a'))
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl)

            triggerEl.addEventListener('click', function (event) {
                event.preventDefault()
                tabTrigger.show()
                    })
                })
            })
        </script>

        <script>
        // Fix active Tab not loading on load
        var tabElements = [].slice.call(document.querySelectorAll('#myTab a'));
            tabElements.forEach(function (tabElement) {
                var tabTrigger = new bootstrap.Tab(tabElement);

                tabElement.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });

            // Activate the initial tab
            var initialTab = document.querySelector('#myTab a.active');
            if (initialTab) {
                var tab = new bootstrap.Tab(initialTab);
                tab.show();
            }

            // Show the content of the active tab
            var activeTabContent = document.querySelector('.tab-pane.active');
            if (activeTabContent) {
                activeTabContent.classList.add('show');
            }
        </script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM fully loaded');
    const profileImage = document.getElementById('profileImage');
    const avatarInput = document.getElementById('avatarInput');

    console.log('profileImage:', profileImage);
    console.log('avatarInput:', avatarInput);

    if (profileImage && avatarInput) {
        profileImage.addEventListener('click', function() {
            console.log('Profile image clicked');
            avatarInput.click();
        });

        avatarInput.addEventListener('change', function(event) {
            console.log('Avatar input changed');
            const file = event.target.files[0];
            if (file) {
                console.log('File selected:', file.name);
                const reader = new FileReader();
                reader.onload = function(e) {
                    console.log('File read successfully');
                    profileImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    } else {
        console.log('profileImage or avatarInput not found');
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const profileImage = document.getElementById('profileImage');
    const avatarInput = document.getElementById('avatarInput');

    profileImage.addEventListener('click', function() {
        avatarInput.click();
    });

    avatarInput.addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profileImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>

<style>
.private-messages-toggle {
    margin: 20px 0;
}

.form-check.form-switch {
    padding-left: 2.5em;
}

.form-check-input {
    cursor: pointer;
    width: 3em !important;
    height: 1.5em !important;
    margin-left: -2.5em;
}

.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.form-check-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-check-label {
    cursor: pointer;
    user-select: none;
    padding-left: 0.5em;
}
</style>

<!-- Allow messages toggle state change -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesToggle = document.getElementById('messages_active');
    if (messagesToggle) {
        messagesToggle.addEventListener('change', function() {
            // You could add immediate visual feedback here if desired
            console.log('Private messages setting changed:', this.checked);
        });
    }
});
</script>


</body>
</html>



     