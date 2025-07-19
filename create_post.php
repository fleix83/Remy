<?php
require_once 'includes/init.php';
require_once 'config/database.php';
require_once 'includes/header.php'; 
require_once 'navbar.php';
require_once 'includes/language_utils.php'; 
require_once 'includes/summernote.php';

// Get the user's current language
$currentLang = getCurrentLanguage();

// Fetch user role from the database
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'];

$stmt = $pdo->prepare("SELECT role, IFNULL(avatar_url, 'path/to/default-avatar.png') as avatar_url FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'];
$user_avatar = $user['avatar_url'];


// Fetch categories from the database
$stmt = $pdo->query("SELECT id, name_de FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch designations from the database
$stmt = $pdo->query("SELECT name_de, name_fr, name_it FROM designations WHERE is_active = 1 ORDER BY name_$currentLang ASC");
$designations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Fetch latest tags
$stmt = $pdo->prepare("
    SELECT t.name, COUNT(*) as count
    FROM tags t
    JOIN post_tags pt ON t.id = pt.tag_id
    JOIN posts p ON pt.post_id = p.id
    WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY t.id
    ORDER BY count DESC, t.name
    LIMIT 10
");
$stmt->execute();
$latest_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <style>
        
    

    </style>
</head>
<body>
    <div class="container mt-2">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="edit-post-box card custom-card">
                    <div class="post-element">
                        <h2 class="create-post card-title text-left mb-5">Neuen Beitrag erstellen</h2>
                        <form id="createPostForm">
                            <div class="mb-4">
                                <label for="category" class="form-label"><i class="bi bi-folder"></i> Kategorie</label>
                                <select class="form-select edit-post" id="category" name="category" required>
                                    <option value="">Wähle eine Kategorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name_de']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="canton" class="form-label"><i class="bi bi-geo-alt"></i> Kanton</label>
                                <select class="form-select edit-post" id="canton" name="canton" required>
                                    <option value="">Wähle einen Kanton</option>
                                    <?php foreach ($cantons as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="therapistSection" class="mb-4" style="display: none;">
                                <label for="therapistSearch" class="form-label"><i class="bi bi-person"></i> Therapeut/in</label>
                                <div class="input-group">
                                    <input type="text" 
                                        class="form-control edit-post" 
                                        id="therapistSearch" 
                                        placeholder="Therapeuten suchen"
                                        autocomplete="off">
                                    <input type="hidden" id="therapist" name="therapist" value="">
                                    <button class="btn new-therapist" type="button" id="newTherapistBtn">Neu</button>
                                </div>

                                <!-- Dropdown container for search results -->
                                <div id="therapistResults" class="therapist-results" style="display: none;"></div>
                            </div>

                            <!-- <div class="mb-3">
                                <label for="title" class="form-label"><i class="bi bi-type"></i> Titel</label>
                                <input type="text" class="form-control edit-post" id="title" name="title" placeholder="Gebe einen Titel ein" required>
                            </div> -->

                            <div class="mb-5">
                                <label for="content" class="form-label"><i class="bi bi-pencil"></i> Inhalt</label>
                                <!-- <textarea class="form-control" id="trumbowyg-demo" name="content" rows="6" placeholder="Verfassen Sie Ihren Post..." required></textarea> -->
                                <textarea id="summernote" name="content" class="form-control edit-post" placeholder="Dein Beitrag..." required></textarea>
                            </div>

                            <!-- <div class="mb-3">
                                <label>Kürzlich verwendete Tags:</label>
                                <?php if (!empty($latest_tags)): ?>
                                    <?php foreach ($latest_tags as $tag): ?>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag['name']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>Keine kürzlich verwendeten Tags gefunden.</p>
                                <?php endif; ?>
                            </div> -->

                            <div class="mb-5">
                                <label for="tags" class="form-label"><i class="bi bi-tags"></i> Tags </label>
                                <input type="text" class="form-control edit-post" id="tags" name="tags" data-role="tagsinput" placeholder="Tags hinzufügen">
                                <?php if (!empty($latest_tags)): ?>
                                    <?php foreach ($latest_tags as $tag): ?>
                                        <span class="badge bg-tags me-1 mt-3"><?= htmlspecialchars($tag['name']) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- <p>Keine kürzlich verwendeten Tags gefunden.</p> -->
                                <?php endif; ?>
                            </div>

                            <!-- Sticky Post -->
                            <?php if ($user_role === 'admin' || $user_role === 'moderator'): ?>
                                <div class="mb-3 form-switch">
                                    <input type="checkbox" class="form-check-input mb-5" id="sticky" name="sticky" value="1">
                                    <label class="form-check-label mx-3" for="sticky">Post anpinnen</label>
                                </div>
                            <?php endif; ?>

                            

                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary btn-custom me-2" id="previewBtn">Vorschau</button>
                                <button type="button" class="btn btn-outline-primary btn-custom me-2" id="saveBtn">Speichern</button>
                                <button type="button" class="btn btn-primary btn-custom" id="publishBtn">Veröffentlichen</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Beitragsvorschau</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <main class="container-fluid px-0">
                        <div class="row justify-content-center">
                            <div class="col-lg-11 col-md-12 col-sm-12">
                                <div class="post card-body">
                                    <!-- Post Meta Top -->
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <span id="preview-category" class="badge bg-primary me-2"></span>
                                            <img id="preview-canton-flag" src="" alt="" style="width: 20px; height: 20px;" class="me-1">
                                            <small id="preview-canton" class="post-post-user text-muted"></small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <img id="preview-avatar" src="" class="avatar rounded-circle me-2" alt="Avatar">
                                            <span id="preview-username" class="post-post-user text-muted"></span>
                                        </div>
                                    </div>

                                    <!-- Post Title -->
                                    <div class="col-md-8">
                                        <h2 id="preview-title" class="post-post-title card-title"></h2>
                                    </div>

                                    <!-- Therapist Info (only shown for category "Erfahrung") -->
                                    <div id="preview-therapist-section" class="therapist-info mb-3" style="display: none;">
                                        <div class="therapist-info">
                                            <span><small>Erfahrung mit</small></span>
                                            <span id="preview-therapist" class="therapist-link"></span>
                                        </div>
                                    </div>

                                    <!-- Post Content -->
                                    <div class="post-post-content">
                                        <div id="preview-content" class="card-text"></div>
                                        <!-- Tags -->
                                        <div id="preview-tags" class="post-tags mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </main>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" id="previewPublishBtn">Veröffentlichen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Therapist Modal -->
    <div class="modal fade" id="newTherapistModal" tabindex="-1" aria-labelledby="newTherapistModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newTherapistModalLabel">Neuen Therapeuten hinzufügen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="newTherapistForm">
                    <div class="mb-3">
                            <label for="therapist_canton" class="form-label">Kanton</label>
                            <select class="form-select edit-post" id="therapist_canton" name="therapist_canton" required>
                                <option value="">Kanton in dem Fachperson praktiziert</option>
                                <?php foreach ($cantons as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">
                            Bitte wählen Sie einen Kanton aus.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="form_of_address" class="form-label">Anrede</label>
                            <input type="text" class="form-control edit-post" id="form_of_address" name="form_of_address" placeholder="Anrede" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Vorname</label>
                            <input type="text" class="form-control edit-post" id="first_name" name="first_name" placeholder="Vorname" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nachname</label>
                            <input type="text" class="form-control edit-post" id="last_name" name="last_name" placeholder="Nachname" required>
                        </div>
                        <div class="mb-3">
                            <label for="designation" class="form-label">Berufsbezeichnung</label>
                            <select class="form-select edit-post" id="designation" name="designation" required>
                                <option value="">Berufsbezeichnung der Fachperson</option>
                                <?php foreach ($designations as $designation): ?>
                                    <option value="<?= htmlspecialchars($designation["name_$currentLang"]) ?>">
                                        <?= htmlspecialchars($designation["name_$currentLang"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="institution" class="form-label">Institution (wenn vorhanden)</label>
                            <input type="text" class="form-control edit-post" id="institution" name="institution" placeholder="(zB. Klinik, Tagesstruktur, Programm)">
                        </div>
                       
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn action-btn" id="saveNewTherapistBtn">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.8.0/bootstrap-tagsinput.min.js"></script>

<script>
    var userRole = '<?php echo $user_role; ?>';
</script>

<script>
$(document).ready(function() {
    // Show/hide therapist section based on category selection
    $('#category').change(function() {
        if ($(this).val() === '1') { // Assuming '1' is the id for "Erfahrung" category
            $('#therapistSection').show();
        } else {
            $('#therapistSection').hide();
        }
    });

   // Preview button click handler
$('#previewBtn').click(function() {
    var category = $('#category option:selected').text();
    var canton = $('#canton option:selected').text();
    var cantonCode = $('#canton').val();
    var therapist = $('#therapistSearch').val();
    var title = $('#title').val();
    var content = $('#summernote').summernote('code');
    var tags = $('#tags').val();

    // Set category
    $('#preview-category').text(category);

    // Set canton and flag
    $('#preview-canton').text(cantonCode);
    $('#preview-canton-flag').attr('src', `uploads/kantone/${cantonCode}.png`);
    $('#preview-canton-flag').attr('alt', `${canton} Flagge`);

    // Set user info (you might want to get these from PHP)
    $('#preview-avatar').attr('src', '<?php echo htmlspecialchars($user_avatar); ?>');
    $('#preview-username').text('<?php echo htmlspecialchars($_SESSION["username"]); ?>');

    // Set title
    $('#preview-title').text(title);

    // Handle therapist section
    if ($('#category').val() === '1' && therapist) { // Check if category is "Erfahrung"
        $('#preview-therapist-section').show();
        $('#preview-therapist').text(therapist);
    } else {
        $('#preview-therapist-section').hide();
    }

    // Set content
    $('#preview-content').html(content);

    // Set tags
    if (tags) {
        const tagsHtml = tags.split(',')
            .map(tag => `<span class="badge bg-secondary me-1">${tag.trim()}</span>`)
            .join('');
        $('#preview-tags').html(tagsHtml);
    } else {
        $('#preview-tags').empty();
    }

    // Show the modal
    $('#previewModal').modal('show');
});

// Add handler for the preview publish button
$('#previewPublishBtn').click(function() {
    $('#previewModal').modal('hide');
    savePost('publish');
});

// New Therapis Button
$('#newTherapistBtn').click(function() {
    $('#newTherapistModal').modal('show');
});


// Save New Therapist button click handler
let therapists = <?php echo json_encode($therapists); ?>;
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

                // Optional: Reload the page to ensure all data is fresh
                // If you want a smoother experience without reload, remove this line
                // location.reload();
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



function savePost(action) {
    // Client-side validation
    var category = $('#category').val();
    var canton = $('#canton').val();
    var title = $('#title').val();
    var content = $('#summernote').summernote('code');

    if (!category) {
        alert("Bitte wählen Sie eine Kategorie aus.");
        return;
    }

    if (!canton) {
        alert("Bitte wählen Sie einen Kanton aus.");
        return;
    }

    if (!title.trim()) {
        alert("Bitte geben Sie einen Titel ein.");
        return;
    }

    if (!content.trim()) {
        alert("Bitte geben Sie einen Inhalt ein.");
        return;
    }

    var formData = new FormData($('#createPostForm')[0]);
    formData.append('action', action);
    formData.append('tags', $('#tags').val());
    formData.append('content', content);

    if (!formData.has('designation')) {
        formData.append('designation', '');
    }
   
    if (userRole === 'admin' || userRole === 'moderator') {
        var isSticky = $('#sticky').is(':checked') ? 1 : 0;
        formData.append('sticky', isSticky);
    }

    $.ajax({
        url: 'create_post_process.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                var message = action === 'publish' 
                    ? 'Dein Beitrag wurde an die Moderation gesendet' 
                    : 'Dein Beitrag wurde als Entwurf gespeichert';
                    
                alert(message);
                window.location.href = 'forum.php';
            } else {
                alert(response.message || 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            // Try to extract JSON from error response that might contain HTML
            let errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
            try {
                // Try to find JSON in the response
                const jsonMatch = xhr.responseText.match(/\{.*\}/);
                if (jsonMatch) {
                    const jsonResponse = JSON.parse(jsonMatch[0]);
                    if (jsonResponse.message) {
                        errorMessage = jsonResponse.message;
                    }
                }
            } catch (e) {
                console.error('Error parsing error response:', e);
            }
            
            alert(errorMessage);
        }
    });
}

    // Attach save and publish handlers
    $('#saveBtn, #savePreviewBtn').click(function() {
        savePost('draft');
    });

    $('#publishBtn, #publishPreviewBtn').click(function() {
        savePost('publish');
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
<!-- Summernote JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

<script>
// Summernote Editor
    $(document).ready(function() {
        $('#summernote').summernote({
            placeholder: 'Dein Beitrag ...',
            tabsize: 2,
            height: 300,
            toolbar: [
                // ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                // ['fontname', ['fontname']],
                // ['fontsize', ['fontsize']],
                // ['color', ['color']],
                // ['para', ['ul', 'ol']],
                // ['table', ['table']],
                ['insert', ['link', 'picture', //'video',
                'fullscreen']],
                // ['view', ['fullscreen', 'codeview', 'help']]
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

    // Adding Tags to input when clicking on recent tags
    $('.badge.bg-secondary').click(function() {
        var tagText = $(this).text().trim();
        $('#tags').tagsinput('add', tagText);
        $(this).fadeOut(300).fadeIn(300);
    });
});
</script>

<script>
// Therapist search select
document.addEventListener('DOMContentLoaded', function() {
    const therapistSearchInput = document.getElementById('therapistSearch');
    const therapistHiddenInput = document.getElementById('therapist');
    const therapistResults = document.getElementById('therapistResults');
    const therapists = <?php echo json_encode($therapists); ?>; // PHP will inject the therapists array

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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
<?php require_once 'includes/footer.php'; ?>
</html>