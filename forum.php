<?php
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/date_function.php';
require_once 'includes/language_utils.php'; 


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

    // Define how many posts to load at once (e.g., 10 posts)
    $limit = 10;
    // Use GET parameter "offset" to know where to start, defaulting to 0 on the first load.
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // Append the LIMIT and OFFSET clause to your SQL after the ORDER BY clause
    $sql .= " LIMIT $limit OFFSET $offset";




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
    
</head>

<style>
    /* Add this to your existing CSS */

.post-wrapper {
    display: flex;
    align-items: flex-start;  /* This ensures top alignment */
}

.post-user-stats {
    margin-top: 57px;  /* Adjust this value to match the top padding/margin of user-date */
}

.post-user {
    position: relative;
    top: 0;
}

.post-content {
    display: flex;
    flex-direction: column;
}

.user-info-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.user-date-wrapper {
    display: flex;
    flex-direction: column;
    /* gap: 0.25rem; */
}

.username {
    margin: 0;
    font-size: 0.775rem;
    font-weight: 500;
    color: #4b5563;
    line-height: 0.8rem;
}

.post-date {
    margin: 0;
    color: #6b7280;
    font-size: 0.675rem;
    line-height: 0.8rem;
}

.post-avatar {
    width: 2rem;
    height: 2rem;
    border-radius: 9999px;
    object-fit: cover;
}

</style>

<head>
<!-- CSS für Peek CSS <link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/custom.css"> -->
</head>

<body>
<main class="container-fluid px-3">
    <div class="row justify-content-center">
            <!-- Forum Container -->
            <div class="col-12 col-lg-10">
                <!-- <div class="forum-top-edge"></div> -->
                <!-- <section id="forum-container"> -->
                    <!-- Forum Topbar -->
                    <div class="forum-topbar">
                        <div class="topbar-wrapper">
                            <div class="forum-topbar-buttons">
                            <?php if (isset($user) && !$user['is_banned']): ?>
                                <!-- New Post Button -->
                                <a href="create_post.php" class=" new-post btn btn-secondary">Neu</a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled title="Nicht verfügbar für eingeschränkte Benutzer">Neuer Beitrag</button>
                                    <?php endif; ?>
                                <!-- Toggle Filter Button -->
                                <button id="toggle-filter" class="btn btn-outline-dark">Filter</button>
                            </div>
                            <!-- Search Field -->
                            <div class="search-container">
                            <div class="search-input-wrapper">
                                    <input type="text" 
                                        id="search-input" 
                                        class="form-control" 
                                        placeholder="Suche..."
                                        minlength="3"
                                        autocomplete="off">
                                    <div class="search-icon-wrapper">
                                        <i class="bi bi-search search-icon"></i>
                                        <div class="spinner-border text-primary search-spinner" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
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
                                <div class="d-flex flex-wrap w-100 justify-content-between filter-items-wrapper">
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
                                    <div class="form-group flex-grow-1 mr-2">
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
                        <div id="post-load-more">
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
                                                <!-- <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="post-avatar" alt="Avatar"> -->
                                            </div>  
                                        </div>
                                    </div>

                                    <div class="post-content col-md-10 col-sm-8 col-xs-8">

                                    

                                        

                                        <!-- Post Category -->
                                        <div class="post-category-canton mb-3">
                                            <p class="badge bg-erfahrung"><?= htmlspecialchars($post['category']) ?></p>
                                            <div class="post-canton">
                                                <img class="post-canton" src="uploads/kantone/<?= htmlspecialchars($post['canton']) ?>.png" alt="<?= htmlspecialchars($post['canton']) ?> Flagge" >
                                                <?= htmlspecialchars($post['canton']) ?>
                                            </div>
                                            <!-- Answers -->
                                            <div class="stat-item col-md-2">
                                                <!-- Comment Icon -->
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                                                    <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                                                </svg>
                                                 <!-- Comment Count -->
                                                <h5 class="count"><?= htmlspecialchars($post['comment_count']) ?></h5>
                                            </div>
                                        </div>

                                        <div class="col-md-10 col-xs-12">
                                            
                                        <!-- Username & Date -->
                                        <div class="user-info-container">
                                            <img src="<?= htmlspecialchars($post['avatar_url']) ?>" class="post-avatar" alt="Avatar">
                                            <div class="user-date-wrapper">
                                                <p class="username"><?= htmlspecialchars($post['username']) ?></p>
                                                <p class="post-date"><?= formatCustomDate($post['post_created_at']) ?></p>
                                            </div>
                                        </div>

                                            <span><h2 class="forum-post-titel"></span>
                                            <?php if (isset($user) && !$user['is_banned']): ?>
                                                <a href="post.php?id=<?= htmlspecialchars($post['id']) ?>"><?= htmlspecialchars($post['title']) ?></a>
                                            <?php else: ?>
                                                <span style="color: #6c757d;" title="Nicht verfügbar für eingeschränkte Benutzer"><?= htmlspecialchars($post['title']) ?></span>
                                            <?php endif; ?>
                                            </h2>
                                            
                                            <!-- Therapist and Designation -->
                                            <div class="col-md-4">
                                            <div class="therapist-info">
                                            <?php if ($post['category_id'] == 1 && $post['therapist']): ?>
                                                
                                                <span>
                                                    <a href="therapeut_profil.php?id=<?= htmlspecialchars($post['therapist']) ?>" class="therapist-link"><i class="bi bi-bullseye"></i> 
                                                    
                                                        <?php
                                                        $therapistDetails = [];
                                                        if (!empty($post['therapist_anrede'])) $therapistDetails[] = htmlspecialchars($post['therapist_anrede']);
                                                        if (!empty($post['therapist_vorname'])) $therapistDetails[] = htmlspecialchars($post['therapist_vorname']);
                                                        if (!empty($post['therapist_nachname'])) $therapistDetails[] = htmlspecialchars($post['therapist_nachname']);
                                                        if (!empty($post['therapist_designation'])) $therapistDetails[] = htmlspecialchars($post['therapist_designation']);
                                                        if (!empty($post['therapist_institution'])) $therapistDetails[] = htmlspecialchars($post['therapist_institution']);
                                                        // if (!empty($post['therapist_canton'])) $therapistDetails[] = htmlspecialchars($post['therapist_canton']);
                                                        
                                                        echo implode(', ', array_filter($therapistDetails));
                                                        ?>
                                                    </a></span>
                                            <?php endif; ?>
                                            </div>
                                            </div>
                                            <!-- Post Tags -->
                                            <?php if (!empty($post['tags'])): ?>
                                                <div class="post-tags">
                                                    <?php foreach (explode(',', $post['tags']) as $tag): ?>
                                                        <span class="badge bg-tags me-1"><?= htmlspecialchars(trim($tag)) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                     </div> <!-- End post-load-more -->

                    <div id="load-more-container" class="text-center my-4">
                        <button id="load-more" class="btn btn-primary">Mehr laden</button>
                    </div>

                <!-- </section> -->
            </div>
        </div>
    </main>
    <?php require_once 'includes/footer.php'; ?>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Custom Date Format in JS
        function formatCustomDate(dateString) {
        const date = new Date(dateString);
        const monthNames = [
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        ];
        
        const day = date.getDate();
        const month = monthNames[date.getMonth()];
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

        return `${day}. ${month} ${year} ${hours}:${minutes} h`;
    }
        // Tags for displaying Search results
        function generateTagsHtml(tags) {
                if (!tags) return '';
                const tagsArray = tags.split(',').map(tag => tag.trim());
                return tagsArray
                    .map(tag => `<span class="badge bg-secondary me-1">${tag}</span>`)
                    .join('');
            }
            
         // Search Function
         document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const searchIconWrapper = document.querySelector('.search-icon-wrapper');
        const searchContainer = document.querySelector('.search-container');
        const resultsContainer = document.getElementById('search-results');
        const resultsContent = document.getElementById('search-results-content');
        
        let searchTimeout;
        const DEBOUNCE_DELAY = 300;
        const MIN_CHARS = 3;

        // Debounce function
        function debounce(func, wait) {
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(searchTimeout);
                    func(...args);
                };
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(later, wait);
            };
        }

    // Search function
    async function performSearch(query) {
        if (query.length < MIN_CHARS) {
            searchContainer.classList.add('show-min-chars');
            resultsContainer.style.display = 'none';
            return;
        }

        searchContainer.classList.remove('show-min-chars');
        searchContainer.classList.add('is-searching');

        try {
            const response = await fetch(`search_posts.php?query=${encodeURIComponent(query)}`);
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            displayResults(data);
        } catch (error) {
            console.error('Search error:', error);
            resultsContent.innerHTML = `<p class="text-danger">Ein Fehler ist aufgetreten: ${error.message}</p>`;
        } finally {
            searchContainer.classList.remove('is-searching');
        }
    }

    // Debounced search function
    const debouncedSearch = debounce(performSearch, DEBOUNCE_DELAY);

    // Event listeners
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        debouncedSearch(query);
    });

    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const query = searchInput.value.trim();
            performSearch(query);
        }
    });

    searchIconWrapper.addEventListener('click', () => {
        const query = searchInput.value.trim();
        performSearch(query);
    });

    // Display results function (your existing code with modifications)
    function displayResults(data) {
    resultsContainer.style.display = 'block';
    resultsContent.innerHTML = ''; // Clear previous results
    
    if (Array.isArray(data) && data.length > 0) {
        data.forEach(post => {
            // Prepare therapist info if available
            let therapistInfo = '';
            if (post.category === 'Erfahrung' && post.therapist) {
                const therapistDetails = [
                    post.therapist_anrede,
                    post.therapist_vorname,
                    post.therapist_nachname,
                    post.therapist_berufsbezeichnung,  // Changed from therapist_designation
                    post.therapist_institution,
                    post.therapist_canton
                ].filter(Boolean).join(', ');
                
                therapistInfo = therapistDetails ? `
                    <div class="therapist-info">
                        <span><small>Erfahrung mit</small></span>
                        <a href="therapeut_profil.php?id=${post.therapist}" class="therapist-link">
                            ${therapistDetails}
                        </a>
                    </div>
                ` : '';
            }

            // Generate tags HTML
            const tagsHtml = post.tags ? generateTagsHtml(post.tags) : '';

            // Create post HTML
            const postHtml = `
                <article class="post">
                    <div class="post-wrapper">
                        <div class="post-user-stats col-md-3 col-sm-3 col-xs-3">
                            <div class="post-user">
                                <div>
                                    <img src="${post.avatar_url}" class="post-avatar" alt="Avatar">
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
                            <div class="col-md-10 col-xs-12">
                                <p class="user-date">${post.username} • <span class="post-date">${formatCustomDate(post.post_created_at)}</span></p>
                                <h2 class="forum-post-titel">
                                    <a href="post.php?id=${post.id}">${post.title}</a>
                                </h2>
                                ${therapistInfo}
                                ${tagsHtml ? `<div class="post-tags">${tagsHtml}</div>` : ''}
                                <div class="stat-item col-md-10">
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
            resultsContent.insertAdjacentHTML('beforeend', postHtml);
        });
    } else {
        resultsContent.innerHTML = '<p>Keine Ergebnisse gefunden.</p>';
    }
}
});

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

<!-- Load-More -->
<script>
document.getElementById('load-more').addEventListener('click', function() {
    // Determine the current offset based on the number of posts already loaded.
    const postContainer = document.getElementById('post-load-more');
    let currentOffset = postContainer.childElementCount;
    
    // Construct a URL for load_more_posts.php including any filters if needed.
    // If you have filters, you can add them as query parameters.
    const params = new URLSearchParams({
        offset: currentOffset,
        // Uncomment and add these if you want to pass through filters:
        // category: document.getElementById('filter-category').value,
        // canton: document.getElementById('filter-canton').value,
        // therapist: document.getElementById('filter-therapist').value,
        // designation: document.getElementById('filter-designation').value,
        // date_from: document.getElementById('filter-date-from').value,
        // date_to: document.getElementById('filter-date-to').value
    });
    
    fetch('load_more_posts.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            // If fewer posts than our limit are returned, hide the "Load More" button.
            if (data.length < 10) {
                document.getElementById('load-more-container').style.display = 'none';
            }
            
            // Append each new post to the post container.
            data.forEach(post => {
                const article = document.createElement('article');
                article.className = 'post';
                
                // Construct the inner HTML similarly to your PHP loop.
                // (You can adjust this markup to match your existing structure.)
                article.innerHTML = `
                    <div class="post-wrapper ${post.sticky == 1 ? 'sticky-post' : ''}">
                        <div class="post-user-stats col-md-3 col-sm-3 col-xs-3">
                            <div class="post-user">
                                
                            </div>
                        </div>
                        <div class="post-content col-md-10 col-sm-8 col-xs-8">
                            <div class="post-category-canton mb-3">
                                <p class="badge bg-erfahrung">${post.category}</p>
                                <div class="post-canton">
                                    <img class="post-canton" src="uploads/kantone/${post.canton}.png" alt="${post.canton} Flagge">
                                    ${post.canton}
                                </div>
                                <div class="stat-item col-md-2">
                                    <!-- Comment icon SVG can be inserted here -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat" viewBox="0 0 16 16">
                                        <path d="M2.678 11.894a1 1 0 0 1 .287.801 11 11 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8 8 0 0 0 8 14c3.996 0 7-2.807 7-6s-3.004-6-7-6-7 2.808-7 6c0 1.468.617 2.83 1.678 3.894m-.493 3.905a22 22 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a10 10 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9 9 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105"/>
                                    </svg>
                                    <h5 class="count">${post.comment_count}</h5>
                                </div>
                            </div>
                            <div class="col-md-10 col-xs-12">
                                <div class="user-info-container">
                                    <img src="${post.avatar_url}" class="post-avatar" alt="Avatar">
                                    <div class="user-date-wrapper">
                                        <p class="username">${post.username}</p>
                                        <p class="post-date">${new Date(post.post_created_at).toLocaleString()}</p>
                                    </div>
                                </div>
                                <h2 class="forum-post-titel">
                                    <a href="post.php?id=${post.id}">${post.title}</a>
                                </h2>
                                <!-- Additional elements like therapist info or tags can be added here -->
                            </div>
                        </div>
                    </div>
                `;
                postContainer.appendChild(article);
            });
        })
        .catch(error => console.error('Error loading more posts:', error));
});
</script>


    <!-- If you have any JavaScript that handles the search functionality, ensure it works with the updated data structures -->
</body>
</html>
