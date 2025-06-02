<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator')) {
    header('Location: ../login.php');
    exit();
}

$isAdmin = $_SESSION['role'] === 'admin';

// Include the existing navbar
require_once __DIR__ . '/../navbar.php';
?>

    <style>
        body {
        
          background: lightgreen;
        }
        .container {
            max-width: 1200px;
        }
        .dashboard-title {
            text-align: center;
            font-weight: bold;
            color: #343a40;
            margin-bottom: 2rem;
        }
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.15);
        }
        .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card-title {
            font-size: 1.5rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        .card-text {
            flex-grow: 1;
            color: #6c757d;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .icon-container {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="dashboard-title">Admin Panel Dashboard</h1>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php if ($isAdmin || $_SESSION['role'] === 'moderator'): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-container">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="card-title">Moderation</h5>
                        <p class="card-text">Moderate forum posts and comments to maintain community standards.</p>
                        <a href="moderation.php" class="btn btn-primary">Go to Moderation</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-container">
                            <i class="fas fa-users"></i>
                        </div>
                        <h5 class="card-title">User Administration</h5>
                        <p class="card-text">Manage user accounts, roles, and permissions.</p>
                        <a href="user_admin.php" class="btn btn-primary">Go to User Admin</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-container">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h5 class="card-title">Category Administration</h5>
                        <p class="card-text">Create, edit, and manage forum categories.</p>
                        <a href="category_panel.php" class="btn btn-primary">Go to Categories</a>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="icon-container">
                            <i class="fas fa-book"></i>
                        </div>
                        <h5 class="card-title">Community Guidelines</h5>
                        <p class="card-text">Edit and update the community guidelines for the forum.</p>
                        <a href="#" class="btn btn-primary disabled">Coming Soon</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>