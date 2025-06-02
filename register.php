<?php
require_once 'includes/init.php';
require_once 'config/database.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Benutzername ist erforderlich.";
    }

    if (empty($email)) {
        $errors[] = "E-Mail-Adresse ist erforderlich.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Ungültige E-Mail-Adresse.";
    }

    if (empty($password)) {
        $errors[] = "Passwort ist erforderlich.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Passwort muss mindestens 8 Zeichen lang sein.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwörter stimmen nicht überein.";
    }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Benutzername oder E-Mail-Adresse bereits vergeben.";
        }
    }

    // Register user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, registration_date) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$username, $email, $hashed_password])) {
            $_SESSION['success_message'] = "Registrierung erfolgreich. Sie können sich jetzt anmelden.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.";
        }
    }
}

require_once 'navbar.php';
?>


<div class="container mt-5">


    <div class="row justify-content-center">
        <div class="col-md-6">
          <div class="snup">Snup</div>
            <div class="card custom-card">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Registrieren</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="bi bi-person"></i> Benutzername</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><i class="bi bi-envelope"></i> E-Mail-Adresse</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="bi bi-lock"></i> Passwort</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><i class="bi bi-lock-fill"></i> Passwort bestätigen</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-block">Registrieren</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <p>Bereits registriert? <a href="login.php">Hier anmelden</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>