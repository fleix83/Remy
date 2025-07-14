<?php
require_once 'includes/init.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    echo "Therapeut ID fehlt.";
    exit;
}

$therapist_id = $_GET['id'];

try {
    // Fetch therapist details
    $stmt = $pdo->prepare("SELECT * FROM therapists WHERE id = ?");
    $stmt->execute([$therapist_id]);
    $therapist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$therapist) {
        echo "Therapeut nicht gefunden.";
        exit;
    }

    // Fetch posts mentioning this therapist
    $stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON p.user_id = u.id WHERE p.therapist = ? ORDER BY p.created_at DESC");
    $stmt->execute([$therapist_id]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
    exit;
}
// Navbar nach AJAX request
require_once 'includes/header.php';
require_once 'navbar.php';
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($therapist['first_name'] . ' ' . $therapist['last_name']); ?> - Therapeutenprofil</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        /* Match forum post styling */
        .post-wrapper {
            display: flex;
            align-items: flex-start;
        }

        .post-user-stats {
            margin-top: 57px;
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

        /* Therapist profile specific styling */
        .therapist-header {
            background: #ffffff;
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 60px;
                }

        .therapist-name {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .therapist-detail {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .therapist-detail i {
            width: 1.2rem;
            opacity: 0.8;
        }

        .canton-flag {
            width: 20px;
            height: 20px;
            margin-right: 0.5rem;
        }

        /* Post cards matching forum style */
        .post-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .post-card-wrapper {
            display: flex;
            align-items: flex-start;
            padding: 1.5rem;
        }

        .post-card-content {
            flex: 1;
        }

        .post-card-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: #6b7280;
            font-size: 0.775rem;
        }

        .post-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #1f2937;
        }

        .post-card-title a {
            color: inherit;
            text-decoration: none;
        }

        .post-card-title a:hover {
            color: #667eea;
        }

        .post-card-excerpt {
            color: #4b5563;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .read-more {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .read-more:hover {
            color: #764ba2;
        }

        /* Analysis section styling */
        .analysis-section {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .analysis-section h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        /* Chat styling */
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }

        .user-message {
            background-color: #e3f2fd;
            margin-left: 20%;
            margin-right: 5px;
        }

        .ai-message {
            background-color: #f5f5f5;
            margin-right: 20%;
            margin-left: 5px;
        }

        .system-message {
            background-color: #fff3e0;
            text-align: center;
            margin: 10px 25%;
            font-style: italic;
        }

        /* Section headers */
        .section-title {
           font-size: 1.15rem;
           font-weight: 400;
           margin-bottom: 1.5rem;
           color: #1f2937;
           padding-bottom: 0.5rem;
        }

        /* Container styling */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Page title styling */
        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
            text-align: center;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Page Title -->
        <h1 class="page-title mb-4">Therapeutenprofil</h1>
        
        <!-- Therapist Header -->
        <div class="therapist-header">
            <h1 class="therapist-name">
                <?php echo htmlspecialchars($therapist['form_of_address'] . ' ' . $therapist['first_name'] . ' ' . $therapist['last_name']); ?>
            </h1>
            <div class="therapist-detail">
                <i class="bi bi-geo-alt-fill"></i>
                <img class="canton-flag" src="uploads/kantone/<?php echo htmlspecialchars($therapist['canton']); ?>.png" alt="<?php echo htmlspecialchars($therapist['canton']); ?> Flagge">
                <?php echo htmlspecialchars($therapist['canton']); ?>
            </div>
            <div class="therapist-detail">
                <i class="bi bi-person-badge"></i>
                <?php echo htmlspecialchars($therapist['designation']); ?>
            </div>
            <?php if (!empty($therapist['description'])): ?>
                <div class="therapist-detail">
                    <i class="bi bi-file-text"></i>
                    <?php echo htmlspecialchars($therapist['description']); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($therapist['institution'])): ?>
                <div class="therapist-detail">
                    <i class="bi bi-building"></i>
                    <?php echo htmlspecialchars($therapist['institution']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Posts Section -->
        <h2 class="section-title">Beiträge zu <?= "{$therapist['first_name']} {$therapist['last_name']}" ?></h2>
        
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                <i class="bi bi-chat-square-text"></i>
                <p>Es wurden noch keine Beiträge über diesen Therapeuten verfasst.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <div class="post-card-wrapper">
                        <div class="post-card-content">
                            <div class="post-card-meta">
                                <i class="bi bi-person"></i>
                                <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                                <span>•</span>
                                <i class="bi bi-calendar"></i>
                                <span class="post-date"><?php echo date('d.m.Y', strtotime($post['created_at'])); ?></span>
                            </div>
                            <h3 class="post-card-title">
                                <a href="post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                            </h3>
                            <p class="post-card-excerpt"><?php echo htmlspecialchars(strip_tags(substr($post['content'], 0, 200))) . '...'; ?></p>
                            <a href="post.php?id=<?php echo $post['id']; ?>" class="read-more">Weiterlesen →</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
           
      

        <!-- Analysis Section -->
        <div class="analysis-section">
            <h3>Analyse der Beiträge</h3>
            <button id="analyzeBtn" class="btn btn-primary mb-3">Beiträge analysieren</button>
            <div id="analysisResults" style="display: none;">
                <div id="analysisStats" class="mb-4">
                    <!-- Stats will be inserted here -->
                </div>
                <div id="chatInterface">
                    <h4 class="mb-3">AI-Analyse Chat</h4>
                    <div id="chatMessages" class="mb-3 p-3 border rounded" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                        <!-- Chat messages will appear here -->
                    </div>
                    <div class="input-group">
                        <input type="text" id="userQuery" class="form-control" 
                            placeholder="Stellen Sie eine Frage über diesen Therapeuten...">
                        <button id="submitQuery" class="btn btn-primary">Fragen</button>
                    </div>
                </div>
            </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const analyzeBtn = document.getElementById('analyzeBtn');
        const analysisResults = document.getElementById('analysisResults');
        const analysisStats = document.getElementById('analysisStats');
        const chatMessages = document.getElementById('chatMessages');
        const userQuery = document.getElementById('userQuery');
        const submitQuery = document.getElementById('submitQuery');

    // Initialize therapistData in global scope
    let therapistData = null;

    analyzeBtn.addEventListener('click', async () => {
        try {
            // Show loading state
            analyzeBtn.disabled = true;
            analyzeBtn.textContent = 'Lädt...';

            // Fetch the analysis data
            const response = await fetch(`${window.location.origin}/remy/therapeut_analysis.php?id=<?= $therapist['id'] ?>`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Store the response data in therapistData
            therapistData = await response.json();
            console.log('Received therapist data:', therapistData);  // Debug log

            if (therapistData.error) {
                throw new Error(therapistData.error);
            }

            // Display basic stats
            analysisStats.innerHTML = `
                <p><strong>Anzahl Beiträge:</strong> ${therapistData.summary.total_posts}</p>
                ${therapistData.summary.latest_post ? 
                    `<p><strong>Letzter Beitrag:</strong> ${new Date(therapistData.summary.latest_post).toLocaleDateString('de-CH')}</p>` 
                    : ''}
            `;

            // Show the results section
            analysisResults.style.display = 'block';
            
            // Reset button state
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Beiträge analysieren';

        } catch (error) {
            console.error('Error:', error);
            alert('Ein Fehler ist aufgetreten: ' + error.message);
            
            // Reset button state
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = 'Beiträge analysieren';
        }
    });

    submitQuery.addEventListener('click', async () => {
        // Check if analysis has been run first
        if (!therapistData) {
            alert('Bitte zuerst die Analyse durchführen.');
            return;
        }

        if (!userQuery.value.trim()) {
            alert('Bitte geben Sie eine Frage ein.');
            return;
        }

        const query = userQuery.value.trim();
        
        // Add user question to chat
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'chat-message user-message';
        userMessageDiv.innerHTML = `<strong>Ihre Frage:</strong> ${query}`;
        chatMessages.appendChild(userMessageDiv);

        // Add loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chat-message system-message';
        loadingDiv.innerHTML = 'Analysiere...';
        chatMessages.appendChild(loadingDiv);

        // Clear input
        userQuery.value = '';
        
        try {
            const response = await fetch('ai_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: query,
                    therapistData: therapistData
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('AI Response:', data);  // Debug log

            // Remove loading indicator
            loadingDiv.remove();

            // Add AI response to chat
            const aiMessageDiv = document.createElement('div');
            aiMessageDiv.className = 'chat-message ai-message';
            
            if (data.error) {
                aiMessageDiv.innerHTML = `<strong>Error:</strong> ${data.error}`;
            } else {
                aiMessageDiv.innerHTML = `<strong>Analyse:</strong> ${data.response}`;
            }
            
            chatMessages.appendChild(aiMessageDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;

        } catch (error) {
            console.error('Error:', error);
            loadingDiv.innerHTML = `<strong>Error:</strong> ${error.message}`;
        }
    });

    // Allow Enter key to submit
    userQuery.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            submitQuery.click();
        }
    });
});
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>