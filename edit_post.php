<?php
ob_start();
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/summernote.php';
include 'includes/new_therapist_form.php'; 
require_once 'vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user'; // Default to 'user' if not set

$post_id = $_GET['id'] ?? null;
$error = null;

if (!$post_id) {
    $error = "Post ID fehlt.";
} else {
    // Fetch post data
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :post_id LIMIT 1");
        $stmt->execute(['post_id' => $post_id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            $error = "Post nicht gefunden.";
        } elseif ($post['user_id'] != $_SESSION['user_id'] && !in_array($user_role, ['admin', 'moderator'])) {
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

        // Initialize $sticky
        $sticky = $post['sticky']; // Default to current value

        // Handle sticky status based on user role
        if (in_array($user_role, ['admin', 'moderator'])) {
            $sticky = isset($_POST['sticky']) ? 1 : 0;
        }

        try {
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ?, canton = ?, therapist = ?, tags = ?, sticky = ? WHERE id = ?");
            $result = $stmt->execute([$title, $clean_content, $category, $canton, $therapist, $tags, $sticky, $post_id]);

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

</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php elseif (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <?php if (!$error && isset($post)): ?>
                    <div class="edit-post-box card custom-card">
                        <div class="post-element">
                            <h2 class="card-title text-center mb-4">Beitrag bearbeiten</h2>
                            <form id="editPostForm" action="edit_post.php?id=<?= $post_id ?>" method="post">
                                <div class="mb-3">
                                    <label for="category" class="form-label"><i class="bi bi-folder"></i> Kategorie</label>
                                    <select class="form-select edit-post" id="category" name="category" required>
                                        <option value="">Wählen Sie eine Kategorie</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo ($post['category_id'] == $category['id']) ? 'selected' : ''; ?>><?php echo $category['name_de']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="canton" class="form-label"><i class="bi bi-geo-alt"></i> Kanton</label>
                                    <select class="form-select edit-post" id="canton" name="canton" required>
                                        <option value="">Wählen Sie einen Kanton</option>
                                        <?php foreach ($cantons as $code => $name): ?>
                                            <option value="<?php echo $code; ?>" <?php echo ($post['canton'] == $code) ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="therapistSection" class="mb-3" style="display: <?php echo ($post['category_id'] == 1) ? 'block' : 'none'; ?>">
                                <label for="therapistSearch" class="form-label"><i class="bi bi-person"></i> Therapeut/in</label>
                                <div class="input-group">
                                    <?php
                                    // Prepare the current therapist display text if one is selected
                                    $currentTherapistDisplay = '';
                                    if (!empty($post['therapist'])) {
                                        $therapistDetails = array_filter([
                                            $post['therapist_anrede'] ?? '',
                                            $post['therapist_vorname'] ?? '',
                                            $post['therapist_nachname'] ?? '',
                                            $post['therapist_berufsbezeichnung'] ?? '',
                                            $post['therapist_institution'] ?? '',
                                            $post['therapist_canton'] ?? ''
                                        ]);
                                        $currentTherapistDisplay = implode(' ', $therapistDetails);
                                    }
                                    ?>
                                    <input type="text" 
                                        class="form-control edit-post" 
                                        id="therapistSearch" 
                                        placeholder="Therapeut* suchen oder neu erstellen..."
                                        value="<?php echo htmlspecialchars($currentTherapistDisplay); ?>"
                                        autocomplete="off">
                                    <input type="hidden" 
                                        id="therapist" 
                                        name="therapist" 
                                        value="<?php echo htmlspecialchars($post['therapist'] ?? ''); ?>">
                                        <button class="btn new-therapist" type="button" id="newTherapistBtn">Neu</button>
                                </div>
                                <div id="therapistResults" class="therapist-results" style="display: none;"></div>
                            </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label"><i class="bi bi-type"></i> Titel</label>
                                    <input type="text" class="form-control edit-post" id="title" name="title" value="<?= htmlspecialchars($post['title']) ?>" required>
                                </div>

                                <div class="mb-5">
                                    <label for="content" class="form-label"><i class="bi bi-pencil"></i> Inhalt</label>
                                    <textarea id="summernote" name="content" class="form-control edit-post" required><?= htmlspecialchars($post['content']) ?></textarea>
                                </div>
                                <div>
                                    <div class="mb-5">
                                    <label for="tags" class="form-label"><i class="bi bi-tags"></i> Tags</label>
                                    <input type="text" class="form-control" id="tags" name="tags" data-role="tagsinput" value="<?php echo htmlspecialchars($post['tags'] ?? ''); ?>" placeholder="Fügen Sie relevante Tags hinzu">
                                </div>
                                

                               <!-- Sticky Post -->
                                <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                                    <div class="mb-3 form-switch">
                                        <input type="checkbox" class="form-check-input" id="sticky" name="sticky" value="1" <?php echo ($post['sticky'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="sticky">Als wichtig markieren (Sticky)</label>
                                    </div>
                                <?php endif; ?>


                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn action-butto-primary mt-3 mb-3">Änderungen speichern</button>
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
                // ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                // ['fontname', ['fontname']],
                // ['fontsize', ['fontsize']],
                // ['color', ['color']],
                //['para', ['ul', 'ol', 'paragraph']],
                //['table', ['table']],
                ['insert', ['link', 'picture', 'fullscreen' ]],
                //['view', ['fullscreen','video', 'codeview', 'help']]
            ]
        });
    });

    // Therapist search field
let therapists = <?php echo json_encode($therapists); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const therapistSearchInput = document.getElementById('therapistSearch');
    const therapistHiddenInput = document.getElementById('therapist');
    const therapistResults = document.getElementById('therapistResults');
    const categorySelect = document.getElementById('category');

    // Show/hide therapist section based on category
    function toggleTherapistSection() {
        const therapistSection = document.getElementById('therapistSection');
        therapistSection.style.display = categorySelect.value === '1' ? 'block' : 'none';
    }

    categorySelect.addEventListener('change', toggleTherapistSection);

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Filter and display therapists
    function filterTherapists(searchValue) {
        if (!searchValue.trim()) {
            therapistResults.style.display = 'none';
            return;
        }

        const searchTerm = searchValue.toLowerCase();
        const filteredTherapists = therapists.filter(therapist => {
            const searchString = `${therapist.canton} ${therapist.form_of_address} ${therapist.first_name} ${therapist.last_name} ${therapist.designation} ${therapist.institution}`.toLowerCase();
            return searchString.includes(searchTerm);
        });

        displayResults(filteredTherapists);
    }

    // Display results in dropdown
    function displayResults(results) {
        if (results.length === 0) {
            therapistResults.style.display = 'none';
            return;
        }

        therapistResults.innerHTML = results.map(therapist => {
            const displayText = [
                therapist.canton,
                therapist.form_of_address,
                therapist.first_name,
                therapist.last_name,
                therapist.designation,
                therapist.institution
            ].filter(Boolean).join(' ');

            return `
                <div class="therapist-result-item" data-id="${therapist.id}">
                    ${displayText}
                </div>
            `;
        }).join('');

        therapistResults.style.display = 'block';
    }

    // Handle input changes
    therapistSearchInput.addEventListener('input', debounce(function(e) {
        filterTherapists(e.target.value);
    }, 300));

    // Handle selection
    therapistResults.addEventListener('click', function(e) {
        const resultItem = e.target.closest('.therapist-result-item');
        if (resultItem) {
            const therapistId = resultItem.dataset.id;
            const therapistText = resultItem.textContent.trim();
            
            therapistSearchInput.value = therapistText;
            therapistHiddenInput.value = therapistId;
            therapistResults.style.display = 'none';
            
            // Add selected class
            document.querySelectorAll('.therapist-result-item').forEach(item => {
                item.classList.remove('therapist-selected');
            });
            resultItem.classList.add('therapist-selected');
        }
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!therapistSearchInput.contains(e.target) && !therapistResults.contains(e.target)) {
            therapistResults.style.display = 'none';
        }
    });

    // Handle keyboard navigation
    therapistSearchInput.addEventListener('keydown', function(e) {
        const items = therapistResults.querySelectorAll('.therapist-result-item');
        const currentSelected = therapistResults.querySelector('.therapist-selected');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (!currentSelected && items.length > 0) {
                    items[0].classList.add('therapist-selected');
                    items[0].scrollIntoView({ block: 'nearest' });
                } else if (currentSelected && currentSelected.nextElementSibling) {
                    currentSelected.classList.remove('therapist-selected');
                    currentSelected.nextElementSibling.classList.add('therapist-selected');
                    currentSelected.nextElementSibling.scrollIntoView({ block: 'nearest' });
                }
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                if (currentSelected && currentSelected.previousElementSibling) {
                    currentSelected.classList.remove('therapist-selected');
                    currentSelected.previousElementSibling.classList.add('therapist-selected');
                    currentSelected.previousElementSibling.scrollIntoView({ block: 'nearest' });
                }
                break;
                
            case 'Enter':
                e.preventDefault();
                if (currentSelected) {
                    currentSelected.click();
                }
                break;
                
            case 'Escape':
                therapistResults.style.display = 'none';
                break;
        }
    });
});

// Update the Save New Therapist button click handler
$('#saveNewTherapistBtn').click(function() {
    var formData = new FormData($('#newTherapistForm')[0]);
    formData.append('action', 'new_therapist');

    $.ajax({
        url: 'create_post_process.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log('New Therapist Response:', response);
            if (response.success) {
                // Create display text for the new therapist
                const displayText = [
                    response.data.canton,
                    response.data.form_of_address,
                    response.data.first_name,
                    response.data.last_name,
                    response.data.designation,
                    response.data.institution
                ].filter(Boolean).join(' ');

                // Update the search input and hidden input with new therapist
                $('#therapistSearch').val(displayText);
                $('#therapist').val(response.data.id);

                // Add the new therapist to the therapists array for future searches
                therapists.push(response.data);

                // Close the modal
                $('#newTherapistModal').modal('hide');
                
                // Reset the form
                $('#newTherapistForm')[0].reset();

                // Show success message
                alert(response.message);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error:', textStatus, errorThrown);
            console.error('Response Text:', jqXHR.responseText);
            alert('An error occurred while saving the new therapist.');
        }
    });
});
</script>

    <script>
    // Connect the "New" button to the modal
    document.getElementById('newTherapistBtn').addEventListener('click', function() {
        // Get the currently selected canton from the post form
        const selectedCanton = document.getElementById('canton').value;
        
        // Pre-fill the canton in the new therapist form if one is selected
        if (selectedCanton) {
            document.getElementById('therapist_canton').value = selectedCanton;
        }
        
        // Show the modal
        var newTherapistModal = new bootstrap.Modal(document.getElementById('newTherapistModal'));
        newTherapistModal.show();
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