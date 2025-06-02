<?php
ob_start();
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/summernote.php';
require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$post_id = $_GET['id'] ?? null;
$error = null;
$message = null;

if (!$post_id) {
    $error = "Post ID fehlt.";
} else {
    // Fetch post data
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :post_id LIMIT 1");
        $stmt->execute(['post_id' => $post_id]);
        $post = $stmt->fetch();

        if (!$post) {
            $error = "Post nicht gefunden.";
        } elseif ($post['user_id'] != $_SESSION['user_id']) {
            $error = "Sie sind nicht berechtigt, diesen Beitrag zu bearbeiten.";
        }
    } catch (PDOException $e) {
        $error = "Fehler: " . $e->getMessage();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $category = $_POST['category'];
        $canton = $_POST['canton'];
        $therapist = $_POST['therapist'] ?? null;
        $tags = $_POST['tags'] ?? '';

        // Initialize HTML Purifier
        $config = HTMLPurifier_Config::createDefault();
        $purifier = new HTMLPurifier($config);

        // Purify the content
        $clean_content = $purifier->purify($content);

        try {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, tags = ?, canton = ?, therapist = ? WHERE id = ?");
            $result = $stmt->execute([$title, $clean_content, $category, $canton, $therapist, $post_id, $tags]);

            if ($result) {
                $_SESSION['message'] = 'Beitrag erfolgreich aktualisiert';
                header('Location: post.php?id=' . $post_id);
                exit;
            } else {
                $error = 'Fehler beim Aktualisieren des Beitrags';
            }
        } catch (PDOException $e) {
            $error = 'Fehler beim Aktualisieren des Beitrags: ' . $e->getMessage();
        }
    }
}

// Fetch categories from the database
$stmt = $pdo->query("SELECT id, name_de FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch cantons
$cantons = [
    'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
    'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt', 'FR' => 'Freiburg',
    'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden', 'JU' => 'Jura', 'LU' => 'Luzern',
    'NE' => 'Neuenburg', 'NW' => 'Nidwalden', 'OW' => 'Obwalden', 'SG' => 'St. Gallen',
    'SH' => 'Schaffhausen', 'SO' => 'Solothurn', 'SZ' => 'Schwyz', 'TG' => 'Thurgau',
    'TI' => 'Tessin', 'UR' => 'Uri', 'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
];

// Fetch therapists from the database
$stmt = $pdo->query("SELECT id, canton, form_of_address, first_name, last_name, designation, institution FROM therapists");
$therapists = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the navbar and other HTML/PHP output
require_once 'navbar.php';
require_once 'includes/summernote.php';
?>
    <style>
        .note-editor.note-airframe .note-editing-area .note-editable, .note-editor.note-frame .note-editing-area .note-editable {
            overflow: auto;
            padding: 50px 20px 200px 50px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (!$error && isset($post)): ?>
                    <div class="edit-post-box card custom-card">
                        <div class="card-body">
                            <h2 class="card-title text-center mb-4">Beitrag bearbeiten</h2>
                            <form id="editPostForm" action="edit_post.php?id=<?= $post_id ?>" method="post">
                                <div class="mb-3">
                                    <label for="category" class="form-label"><i class="bi bi-folder"></i> Kategorie</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Wählen Sie eine Kategorie</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ($post['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo $category['name_de']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="canton" class="form-label"><i class="bi bi-geo-alt"></i> Kanton</label>
                                    <select class="form-select" id="canton" name="canton" required>
                                        <option value="">Wählen Sie einen Kanton</option>
                                        <?php foreach ($cantons as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" <?php echo ($post['canton'] == $code) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="therapistSection" class="mb-3" style="display: <?php echo ($post['category_id'] == 1) ? 'block' : 'none'; ?>">
                                    <label for="therapist" class="form-label"><i class="bi bi-person"></i> Therapeut/in</label>                           
                                    <select class="form-select" id="therapist" name="therapist">
                                        <option value="">Wählen Sie einen Therapeuten</option>
                                        <?php foreach ($therapists as $therapist): ?>
                                            <option value="<?php echo $therapist['id']; ?>" <?php echo ($post['therapist'] == $therapist['id']) ? 'selected' : ''; ?>>
                                                <?php
                                                echo $therapist['canton'] . ' ' .
                                                     $therapist['form_of_address'] . ' ' .
                                                     $therapist['first_name'] . ' ' .
                                                     $therapist['last_name'] . ' ' .
                                                     $therapist['designation'] . ' ' .
                                                     $therapist['institution'];
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label"><i class="bi bi-type"></i> Titel</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="content" class="form-label"><i class="bi bi-pencil"></i> Inhalt</label>
                                    <textarea id="summernote" name="content" class="form-control" required><?= htmlspecialchars($post['content']) ?></textarea>
                                    <div class="mb-3">
                                    <label for="tags" class="form-label"><i class="bi bi-tags"></i> Tags</label>
                                    <input type="text" class="form-control" id="tags" name="tags" data-role="tagsinput" value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" placeholder="Fügen Sie relevante Tags hinzu">
                                </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary btn-custom">Änderungen speichern</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
    $(document).ready(function() {
        function toggleTherapistSection() {
            var categoryValue = $('#category').val();
            console.log("Category value:", categoryValue);
            if (categoryValue === '1') {
                $('#therapistSection').show();
            } else {
                $('#therapistSection').hide();
            }
        }

        // Set the initial category value
        var initialCategory = '<?php echo addslashes($post['category_id']); ?>';
        console.log("Initial category:", initialCategory);
        $('#category').val(initialCategory);

        // Call the function on page load
        toggleTherapistSection();

        // Bind the function to category change event
        $('#category').change(toggleTherapistSection);

        // Initialize Summernote
        $('#summernote').summernote({
            placeholder: 'Verfassen Sie Ihren Post...',
            tabsize: 2,
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
    </script>
    <script>
    $(document).ready(function() {
        // Initialize Bootstrap Tags Input
        $('#tags').tagsinput({
            trimValue: true,
            confirmKeys: [13, 44, 32], // Enter, comma, space
            tagClass: 'badge bg-primary'
        });

        // Prevent form submission on enter key in tag input
        $('#tags').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>