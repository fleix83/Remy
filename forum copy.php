<?php
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/date_function.php';
require_once 'includes/language_utils.php'; // Include language utilities

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header('Location: login.php');
    exit();
}

// Add the banned user check
try {
    $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && $user['is_banned']) {
        $_SESSION['error'] = "Ihr Account ist derzeit eingeschränkt. Sie können keine neuen Beiträge erstellen oder kommentieren.";
    }
} catch (PDOException $e) {
    error_log("Error checking user ban status: " . $e->getMessage());
}

require_once 'navbar.php';

// Fetch the user's default canton
$userDefaultCanton = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT default_canton FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userInfo) {
        $userDefaultCanton = $userInfo['default_canton'];
    }
}

// Get the user's language preference
$userDefaultLanguage = getCurrentLanguage(); // Use the function to get the language

// Error for banned users
if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?= $_SESSION['error'] ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif;

// Initialize filter variables
$filterCategory = $_GET['category'] ?? '';
$filterTherapist = $_GET['therapist'] ?? '';
$filterDesignation = $_GET['designation'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$filterCanton = isset($_GET['canton']) ? $_GET['canton'] : ($userDefaultCanton ?? '');

// Fetch categories from the 'categories' table based on user's default language
try {
    $categoryField = 'name_' . $userDefaultLanguage; // e.g., 'name_de'
    $stmt = $pdo->prepare("SELECT id, $categoryField AS category_name FROM categories ORDER BY $categoryField");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Fehler beim Abrufen der Kategorien: " . $e->getMessage();
    exit;
}

// Prepare categories array for easy access
$categoryOptions = [];
foreach ($categories as $category) {
    $categoryOptions[$category['id']] = $category['category_name'];
}

// Fetch cantons for filter dropdowns
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

// Fetch therapists from the 'therapists' table with required fields
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.id, 
            t.first_name, 
            t.last_name, 
            t.institution, 
            t.canton
        FROM therapists t
        ORDER BY t.last_name, t.first_name
    ");
    $stmt->execute();
    $therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Fehler beim Abrufen der Therapeuten: " . $e->getMessage();
    exit;
}

// Fetch designations from the 'designations' table based on user's default language
try {
    $designationField = 'name_' . $userDefaultLanguage; // e.g., 'name_de'
    $stmt = $pdo->prepare("SELECT id, $designationField AS designation_name FROM designations ORDER BY $designationField");
    $stmt->execute();
    $designations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Fehler beim Abrufen der Bezeichnungen: " . $e->getMessage();
    exit;
}

// Fetch parent posts from the database along with their authors' usernames, categories, comment counts, and latest comment date
try {
    $conditions = [];
    $params = [];

    if (!empty($filterCategory)) {
        $conditions[] = "posts.category_id = ?";
        $params[] = $filterCategory;
    }

    if (!empty($filterCanton)) {
        $conditions[] = "posts.canton = ?";
        $params[] = $filterCanton;
    }

    if (!empty($filterTherapist)) {
        $conditions[] = "posts.therapist = ?";
        $params[] = $filterTherapist;
    }

    if (!empty($filterDesignation)) {
        $conditions[] = "therapists.designation = ?";
        $params[] = $filterDesignation;
    }

    if (!empty($filterDateFrom)) {
        $conditions[] = "posts.created_at >= ?";
        $params[] = $filterDateFrom;
    }

    if (!empty($filterDateTo)) {
        $conditions[] = "posts.created_at <= ?";
        $params[] = $filterDateTo;
    }

    $categoryField = 'name_' . $userDefaultLanguage; // For selecting category name

    $sql = "SELECT posts.*, posts.created_at AS post_created_at, 
    users.username, IFNULL(users.avatar_url, 'path/to/default-avatar.png') AS avatar_url, 
    categories.$categoryField AS category,
    (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) AS comment_count,
    (SELECT MAX(created_at) FROM comments WHERE comments.post_id = posts.id) AS latest_comment_date,
    therapists.form_of_address AS therapist_anrede,
    therapists.first_name AS therapist_vorname,
    therapists.last_name AS therapist_nachname,
    therapists.designation AS therapist_designation,
    therapists.institution AS therapist_institution,
    therapists.canton AS therapist_canton,
    posts.tags, 
    posts.sticky
    FROM posts 
    JOIN users ON posts.user_id = users.id
    JOIN categories ON posts.category_id = categories.id
    LEFT JOIN therapists ON posts.therapist = therapists.id
    WHERE posts.is_published = 1 AND posts.is_active = 1 AND posts.is_banned = 0
    AND posts.parent_id IS NULL";

    if (count($conditions) > 0) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }

    // Modify the ORDER BY clause to prioritize sticky posts
    $sql .= " ORDER BY posts.sticky DESC, posts.created_at DESC";

    $stmt = $pdo->prepare($sql);

    if (count($params) > 0) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Fehler beim Abrufen der Beiträge: " . $e->getMessage();
    exit;
}
require_once __DIR__ . '/includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forum</title>
    <!-- Include your CSS and JS files here -->
</head>
<body>
    <main class="container">
        <div class="row">
            <!-- Forum Container -->
            <div class="col-md-10 m-auto"> 
                <section id="forum-container">
                    <!-- Forum Topbar -->
                    <div class="forum-topbar">
                        <div class="topbar-wrapper">
                            <div class="forum-topbar-buttons">
                            <?php if (isset($user) && !$user['is_banned']): ?>
                                <!-- New Post Button -->
                                <a href="create_post.php" class="new-post btn btn-primary">Neuer Beitrag</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled title="Nicht verfügbar für eingeschränkte Benutzer">Neuer Beitrag</button>
                                    <?php endif; ?>
                                <!-- Toggle Filter Button -->
                                <button id="toggle-filter" class="btn btn-outline-dark">Filter</button>
                            </div>
                            <!-- Search Field -->
                            <div class="search-container">
                                <div class="input-group">
                                    <input type="text" id="search-input" class="form-control" placeholder="Suche...">
                                    <button id="search-button" class="btn btn-secondary">Suchen</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Menu -->
                    <div id="filter-container" class="filter-menu-container" style="display: none;">
                        <?php if ($userDefaultCanton): ?>
                            <div class="alert alert-info">
                                Beiträge werden nach Ihrem Standardkanton (<?= htmlspecialchars($cantons[$userDefaultCanton]) ?>) gefiltert. 
                                <a href="?canton=">Alle Kantone anzeigen</a>
                            </div>
                        <?php endif; ?>
                        <form id="filter-form" class="form-inline" method="get" action="">
                            <div class="row px-5">
                                <div class="d-flex flex-wrap w-100 justify-content-between">
                                    <!-- Category Filter -->
                                    <div class="form-group flex-grow-1 mr-2">
                                        <select id="filter-category" name="category" class="form-control filter">
                                            <option value="">
                                                <i class="fas fa-chevron-down"></i> Alle Kategorien
                                            </option>
                                            <?php foreach ($categories as $category) : ?>
                                                <option value="<?= htmlspecialchars($category['id']) ?>" <?= $filterCategory == $category['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category['category_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Canton Filter -->
                                    <div class="form-group flex-grow-1 mr-2">
                                        <select id="filter-canton" name="canton" class="form-control filter">
                                            <option value="">
                                                <i class="fas fa-chevron-down"></i> Alle Kantone
                                            </option>
                                            <?php foreach ($cantons as $code => $name) : ?>
                                                <option value="<?= htmlspecialchars($code) ?>" <?= $filterCanton === $code ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Therapist Filter -->
                                    <div class="form-group flex-grow-1 mr-2">
                                        <select id="filter-therapist" name="therapist" class="form-control filter">
                                            <option value="">
                                                <i class="fas fa-chevron-down"></i> Alle Therapeuten 
                                            </option>
                                            <?php foreach ($therapists as $therapist) : ?>
                                                <?php
                                                    $therapistDisplayParts = [];
                                                    if (!empty($therapist['first_name'])) {
                                                        $therapistDisplayParts[] = $therapist['first_name'];
                                                    }
                                                    if (!empty($therapist['last_name'])) {
                                                        $therapistDisplayParts[] = $therapist['last_name'];
                                                    }
                                                    if (!empty($therapist['institution'])) {
                                                        $therapistDisplayParts[] = $therapist['institution'];
                                                    }
                                                    if (!empty($therapist['canton'])) {
                                                        $therapistDisplayParts[] = $therapist['canton'];
                                                    }
                                                    $therapistDisplay = htmlspecialchars(implode(' ', $therapistDisplayParts));
                                                    $therapistId = htmlspecialchars($therapist['id']);
                                                    $selected = $filterTherapist == $therapistId ? 'selected' : '';
                                                ?>
                                                <option value="<?= $therapistId ?>" <?= $selected ?>><?= $therapistDisplay ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Designation Filter -->
                                    <div class="form-group flex-grow-1 mr-2">
                                        <select id="filter-designation" name="designation" class="form-control filter">
                                            <option value="">
                                                <i class="fas fa-chevron-down"></i> Alle Bezeichnungen
                                            </option>
                                            <?php foreach ($designations as $designation) : ?>
                                                <?php
                                                    $designationName = htmlspecialchars($designation['designation_name']);
                                                    $designationId = htmlspecialchars($designation['id']);
                                                    $selected = $filterDesignation == $designationId ? 'selected' : '';
                                                ?>
                                                <option <?= $selected ?>><?= $designationName ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <!-- Date From Filter -->
                                    <div class="form-group flex-grow-1 mr-2">
                                        <input type="date" id="filter-date-from" name="date_from" class="form-control filter" value="<?= htmlspecialchars($filterDateFrom) ?>">
                                    </div>
                                    <!-- Date To Filter -->
                                    <div class="form-group d-flex align-items-center">
                                        <input type="date" id="filter-date-to" name="date_to" class="form-control filter mr-2" value="<?= htmlspecialchars($filterDateTo) ?>">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary filter">Filtern</button>
                            <button id="reset-button" class="btn btn-secondary">Zurücksetzen</button>
                        </form>
                    </div>

                    <!-- Search Results -->
                    <div id="search-results" class="search-results" style="display: none;">
                        <h3>Suchergebnisse</h3>
                        <div id="search-results-content"></div>
                    </div>

                    <!-- Post Loop -->
                    <?php foreach ($posts as $post): ?>

                    <!-- Assign a CSS class if the post is sticky -->
                    <?php $postClass = $post['sticky'] == 1 ? 'post sticky-post' : 'post'; ?>

                        <!-- Post Element -->
                        <article class="post">
                            <div class="post-wrapper <?= $postClass ?>">
                                <!-- Post User and Stats -->
                                <div class="post-user-stats col-md-3 col-sm-3 col-xs-3">  
                                    <div class="post-user">
                                        <div>
                                            <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="post-avatar" alt="Avatar">
                                        </div>  
                                    </div>
                                </div>

                                <div class="post-content col-md-10 col-sm-8 col-xs-8">

                                    <!-- Post Category -->
                                    <div class="post-category-canton">
                                        <p class="category badge bg-erfahrung"><?= htmlspecialchars($post['category']) ?></p>
                                        <div class="post-canton">
                                            <img class="post-canton" src="uploads/kantone/<?= htmlspecialchars($post['canton']) ?>.png" alt="<?= htmlspecialchars($post['canton']) ?> Flagge" >
                                            <?= htmlspecialchars($post['canton']) ?>
                                        </div>
                                    </div>

                                    <div class="col-md-10 col-xs-12">
                                        <!-- Username & Date -->
                                        <p class="user-date"><?= htmlspecialchars($post['username']) ?> • <span class="post-date"><?= formatCustomDate($post['post_created_at']) ?></span></p>
                                        <h2 class="forum-post-titel">
                                        <?php if (isset($user) && !$user['is_banned']): ?>
                                            <a href="post.php?id=<?= htmlspecialchars($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                                        <?php else: ?>
                                            <span style="color: #6c757d;" title="Nicht verfügbar für eingeschränkte Benutzer"><?= htmlspecialchars($post['title']) ?></span>
                                        <?php endif; ?>
                                        </h2>
                                        
                                        <!-- Therapist and Designation -->
                                        <?php if ($post['category_id'] == 1 && $post['therapist']): ?>
                                            <div class="therapist-info">
                                                <span><small>Erfahrung mit</small></span>
                                                <a href="therapeut_profil.php?id=<?= htmlspecialchars($post['therapist']) ?>" class="therapist-link">
                                                    <?php
                                                    $therapistDetails = [];
                                                    if (!empty($post['therapist_anrede'])) $therapistDetails[] = htmlspecialchars($post['therapist_anrede']);
                                                    if (!empty($post['therapist_vorname'])) $therapistDetails[] = htmlspecialchars($post['therapist_vorname']);
                                                    if (!empty($post['therapist_nachname'])) $therapistDetails[] = htmlspecialchars($post['therapist_nachname']);
                                                    if (!empty($post['therapist_designation'])) $therapistDetails[] = htmlspecialchars($post['therapist_designation']);
                                                    if (!empty($post['therapist_institution'])) $therapistDetails[] = htmlspecialchars($post['therapist_institution']);
                                                    if (!empty($post['therapist_canton'])) $therapistDetails[] = htmlspecialchars($post['therapist_canton']);
                                                    
                                                    echo implode(', ', array_filter($therapistDetails));
                                                    ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <!-- Post Tags -->
                                        <?php if (!empty($post['tags'])): ?>
                                            <div class="post-tags">
                                                <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                                    <span class="badge bg-secondary forum-post-tags me-1"><?= htmlspecialchars(trim($tag)) ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="stat-item col-md-10">
                                            <!-- Comment Icon -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                                                <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                                            </svg>
                                            <!-- Comment Count -->
                                            <h5 class="count"><?= htmlspecialchars($post['comment_count']) ?></h5>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Tags for displaying Search results
        function generateTagsHtml(tags) {
                if (!tags) return '';
                const tagsArray = tags.split(',').map(tag => tag.trim());
                return tagsArray
                    .map(tag => `<span class="badge bg-secondary me-1">${tag}</span>`)
                    .join('');
            }
            
         // Search Function
         document.getElementById('search-button').addEventListener('click', function() {
            const query = document.getElementById('search-input').value;
            console.log("Search query:", query);
            if (query.length > 2) {
                fetch(`search_posts.php?query=${encodeURIComponent(query)}`)
                    .then(response => {
                        console.log("Response status:", response.status);
                        return response.text();
                    })
                    .then(text => {
                        console.log("Raw response:", text);
                        if (!text) {
                            throw new Error("Empty response received");
                        }
                        return JSON.parse(text);
                    })
                    .then(data => {
                        console.log("Parsed data:", data);
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        const resultsContainer = document.getElementById('search-results-content');
                        resultsContainer.innerHTML = '';
                        if (Array.isArray(data) && data.length > 0) {
                            data.forEach(post => {
                                let therapistInfo = '';
                                if (post.category === 'Erfahrung' && (post.therapist_nachname || post.therapist_vorname || post.therapist_berufsbezeichnung)) {
                                    const therapistDetails = [
                                        post.therapist_anrede,
                                        post.therapist_nachname,
                                        post.therapist_vorname,
                                        post.therapist_berufsbezeichnung,
                                        post.therapist_institution,
                                        post.therapist_canton
                                    ].filter(Boolean).join(', ');
                                    therapistInfo = `
                                        <div class="therapist-info">
                                            <span><small>Erfahrung mit</small></span>
                                            <a href="#" class="therapist-link">${therapistDetails}</a>
                                        </div>
                                    `;
                                }
                                
                                const avatarUrl = `uploads/avatars/${post.avatar_filename}`;
                                const tagsHtml = generateTagsHtml(post.tags);
                                resultsContainer.innerHTML += `
                                <article class="post">
                                    <div class="post-wrapper">
                                        <div class="post-user-stats col-md-3 col-sm-3 col-xs-3">
                                            <div class="post-user">
                                                <div>
                                                    <img src="${post.avatar_url}" alt="${post.username}'s avatar" class="avatar-search">
                                                </div>  
                                            </div>
                                        </div>
                                        <div class="post-content col-md-10 col-sm-8 col-xs-8">
                                            <div class="post-category-canton">
                                                <p class="category badge bg-erfahrung">${post.category}</p>
                                                <div class="post-canton">
                                                    <img class="post-canton" src="uploads/kantone/${post.canton}.png" alt="${post.canton} Flagge">
                                                    ${post.canton}
                                                </div>
                                            </div>
                                            <div class="col-md-8 col-xs-12">
                                                <p class="user-date">${post.username} • ${formatCustomDate(post.post_created_at)}</p>
                                                <h2 class="forum-post-titel"><a href="post.php?id=${post.id}">${post.title}</a></h2>
                                                ${post.tags ? `<div class="post-tags">${tagsHtml}</div>` : ''}
                                                ${therapistInfo}
                                                <div class="stat-item">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                                                        <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                                                    </svg>
                                                    <h5 class="count">${post.comment_count}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                                `;
                            });
                            document.getElementById('search-results').style.display = 'block';
                        } else {
                            resultsContainer.innerHTML = '<p>Keine Ergebnisse gefunden.</p>';
                            document.getElementById('search-results').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const resultsContainer = document.getElementById('search-results-content');
                        resultsContainer.innerHTML = `<p>Ein Fehler ist aufgetreten: ${error.message}</p>`;
                        document.getElementById('search-results').style.display = 'block';
                    });
            }
        });

        // You might need to implement the formatCustomDate function in JavaScript
        // if it's not already available on the client side
        function formatCustomDate(dateString) {
            const date = new Date(dateString);
            const monthNames = ["Januar", "Februar", "März", "April", "Mai", "Juni",
                "Juli", "August", "September", "Oktober", "November", "Dezember"
            ];
            return `${date.getDate()}.${monthNames[date.getMonth()]}.${date.getFullYear()} ${date.getHours()}:${date.getMinutes()} h`;
        }

    </script>

    <script>
        // JavaScript to toggle filter visibility
        document.getElementById('toggle-filter').addEventListener('click', function() {
            const filterContainer = document.getElementById('filter-container');
            filterContainer.style.display = filterContainer.style.display === 'block' ? 'none' : 'block';
        });

        document.getElementById('reset-button').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default form reset

            // Reset all the form fields
            document.getElementById('filter-category').value = '';
            document.getElementById('filter-canton').value = '';
            document.getElementById('filter-therapist').value = '';
            document.getElementById('filter-designation').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';

            // Optionally, you can also submit the form to apply the reset
            document.getElementById('filter-form').submit();
        });
    </script>

    <!-- If you have any JavaScript that handles the search functionality, ensure it works with the updated data structures -->
</body>
</html>
