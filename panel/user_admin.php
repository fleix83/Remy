<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once '../includes/init.php';

// Check if the user is an admin
if (!is_admin()) {
    // Redirect to login page or show an error message
    header('Location: ../login.php');
    exit();
}

// Debug: Log the session data
error_log("Session data in user_admin.php: " . print_r($_SESSION, true));

// Variables for messages
$errors = [];
$success_message = '';

// Retrieve success message from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    // Capture and sanitize input data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($role)) {
        $errors[] = "Role is required.";
    } elseif (!in_array($role, ['user', 'moderator', 'admin'])) {
        $errors[] = "Invalid role selected.";
    }

    // Check if the username already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $usernameExists = $stmt->fetchColumn();

        if ($usernameExists) {
            $errors[] = "Username already exists.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error checking username: " . $e->getMessage();
    }

    // Check if the email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $emailExists = $stmt->fetchColumn();

        if ($emailExists) {
            $errors[] = "Email already exists.";
        }
    } catch (PDOException $e) {
        $errors[] = "Error checking email: " . $e->getMessage();
    }

    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Set default values for avatar and avatar_url
        $defaultAvatar = 'default-avatar.png';
        $defaultAvatarUrl = 'uploads/avatars/default-avatar.png';

        // Insert the new user into the database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role, avatar, avatar_url, registration_date)
                VALUES (:username, :email, :password, :role, :avatar, :avatar_url, NOW())
            ");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'role' => $role,
                'avatar' => $defaultAvatar,
                'avatar_url' => $defaultAvatarUrl
            ]);

            // Set success message in session
            $_SESSION['success_message'] = "User <strong>" . htmlspecialchars($username) . "</strong> created successfully.";

            // Redirect to avoid form resubmission
            header('Location: user_admin.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error creating user: " . $e->getMessage();
        }
    }
}

// Fetch all users
function fetch_users() {
    global $pdo;
    try {
        $query = "SELECT users.id, users.username, users.email, users.registration_date, users.role, users.is_banned,
                         COUNT(posts.id) as post_count
                  FROM users
                  LEFT JOIN posts ON users.id = posts.user_id
                  GROUP BY users.id
                  ORDER BY FIELD(users.role, 'admin', 'moderator', 'user'), users.registration_date DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
        return false;
    }
}

$users = fetch_users();
if ($users === false) {
    $error_message = "An error occurred while fetching users. Please check the error log for more details.";
}

// HTML Header must be after AJAX Call
require_once __DIR__ . '/../includes/header.php';
require_once '../navbar.php';
?>

<!-- Tab User und Create User  -->
<body>
    <div class="container my-5">
        <h1 class="panel text-center mb-5">User Administration</h1>

        <!-- Display success message -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <ul class="nav nav-pills mb-3 mx-5" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-users-tab" data-bs-toggle="pill" data-bs-target="#pills-users" type="button" role="tab" aria-controls="pills-users" aria-selected="true">Users</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-create-tab" data-bs-toggle="pill" data-bs-target="#pills-create" type="button" role="tab" aria-controls="pills-create" aria-selected="false">Neuen User erstellen</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-users" role="tabpanel" aria-labelledby="pills-users-tab">
            <div class="card">
                <div class="card-body user-admin">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php elseif (empty($users)): ?>
                        <div class="alert alert-info">No users found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="hide-on-small">User ID</th>
                                        <th>Username</th>
                                        <th class="hide-on-small">Registration Date</th>
                                        <th class="hide-on-small">Posts</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="hide-on-small"><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td class="hide-on-small"><?= date('d/m/Y', strtotime($user['registration_date'])) ?></td>
                                        <td class="hide-on-small"><?= $user['post_count'] ?></td>
                                        <td>
                                            <select name="user_role_<?= $user['id'] ?>" id="user_role_<?= $user['id'] ?>" class="form-select form-select-sm" onchange="updateUserRole(<?= $user['id'] ?>, this.value)">
                                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                                <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="status-<?= $user['id'] ?>" name="status_<?= $user['id'] ?>" <?= $user['is_banned'] ? '' : 'checked' ?> onchange="updateUserStatus(<?= $user['id'] ?>, this.checked)">
                                                <label class="form-check-label" for="status-<?= $user['id'] ?>">
                                                    <?= $user['is_banned'] ? 'Banned' : 'Active' ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                        <button class="btn btn-icon btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)" title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <button class="btn btn-icon btn-outline-primary" onclick="messageUser(<?= $user['id'] ?>)" title="Message User">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            <!-- Create New User -->
            <div class="tab-pane fade" id="pills-create" role="tabpanel" aria-labelledby="pills-create-tab">
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body new-user">
                                <h2 class="card-title text-center mb-4">Create New User</h2>
                                
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo htmlspecialchars($error); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success_message): ?>
                                    <div class="alert alert-success">
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>

                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control text-start" id="username" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email address</label>
                                        <input type="email" class="form-control text-start" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control text-start" id="password" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Role</label>
                                        <select class="form-select text-start" id="role" name="role" required>
                                            <option value="">Select role</option>
                                            <option value="user">User</option>
                                            <option value="moderator">Moderator</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function updateUserRole(userId, newRole) {
            $.ajax({
                url: 'user_actions.php',
                type: 'POST',
                data: {
                    action: 'update_role',
                    user_id: userId,
                    role: newRole
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                    } else {
                        showNotification('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showNotification('An error occurred while updating the role.', 'danger');
                }
            });
        }

        function updateUserStatus(userId, isActive) {
            $.ajax({
                url: 'user_actions.php',
                type: 'POST',
                data: {
                    action: 'update_status',
                    user_id: userId,
                    is_active: isActive
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showNotification(response.message, 'success');
                        $(`#status-${userId}`).next('label').text(isActive ? 'Active' : 'Banned');
                    } else {
                        showNotification('Error: ' + response.message, 'danger');
                        $(`#status-${userId}`).prop('checked', !isActive);
                    }
                },
                error: function() {
                    showNotification('An error occurred while updating the status.', 'danger');
                    $(`#status-${userId}`).prop('checked', !isActive);
                }
            });
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                $.ajax({
                    url: 'user_actions.php',
                    type: 'POST',
                    data: {
                        action: 'delete_user',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete user response:', response);
                        if (response.success) {
                            showNotification(response.message, 'success');
                            $(`tr:has(td:contains(${userId}))`).remove();
                        } else {
                            showNotification('Error: ' + response.message, 'danger');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error deleting user:', textStatus, errorThrown);
                        console.log('Response Text:', jqXHR.responseText);
                        try {
                            // Try to extract JSON from the response if it contains HTML
                            var jsonStart = jqXHR.responseText.indexOf('{');
                            var jsonEnd = jqXHR.responseText.lastIndexOf('}');
                            if (jsonStart !== -1 && jsonEnd !== -1) {
                                var jsonString = jqXHR.responseText.substring(jsonStart, jsonEnd + 1);
                                var response = JSON.parse(jsonString);
                                showNotification('Error: ' + response.message, 'danger');
                            } else {
                                throw new Error('No valid JSON found in response');
                            }
                        } catch (e) {
                            showNotification('An unexpected error occurred. Check console for details.', 'danger');
                        }
                    }
                });
            }
        }

        function messageUser(userId) {
            // Implement message functionality or redirect to messaging page
            window.location.href = `send_message.php?user_id=${userId}`;
        }

        function showNotification(message, type) {
            const notificationEl = $('<div>')
                .addClass(`alert alert-${type} alert-dismissible fade show`)
                .attr('role', 'alert');

            const messageEl = $('<span>').html(message);

            const closeButton = $('<button>')
                .addClass('btn-close')
                .attr({
                    'type': 'button',
                    'data-bs-dismiss': 'alert',
                    'aria-label': 'Close'
                });

            notificationEl.append(messageEl, closeButton);
            $('body').append(notificationEl);

            setTimeout(function() {
                notificationEl.alert('close');
            }, 5000);
        }
        </script>
    </div>


    </script>
</body>
</html>




