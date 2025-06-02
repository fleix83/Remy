<?php
require_once 'includes/init.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect to login page or show an error message
    header('Location: login.php');
    exit();
}

$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            // Success message
            $success_message = "User <strong>" . htmlspecialchars($username) . "</strong> created successfully.";
        } catch (PDOException $e) {
            $errors[] = "Error creating user: " . $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
require_once 'navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
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
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
