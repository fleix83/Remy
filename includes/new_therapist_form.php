<!-- New Therapist Modal -->


<?php
require_once 'includes/language_utils.php'; 

// Get the user's current language
$currentLang = getCurrentLanguage();

// Fetch user role from the database
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$user_role = $user['role'];


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
                        <select class="form-select" id="therapist_canton" name="therapist_canton" required>
                            <option value="">Wählen Sie einen Kanton</option>
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
                        <input type="text" class="form-control" id="form_of_address" name="form_of_address" required>
                    </div>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Vorname</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Nachname</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="designation" class="form-label">Berufsbezeichnung</label>
                        <select class="form-select" id="designation" name="designation" required>
                            <option value="">Wählen Sie eine Berufsbezeichnung</option>
                            <?php foreach ($designations as $designation): ?>
                                <option value="<?= htmlspecialchars($designation["name_$currentLang"]) ?>">
                                    <?= htmlspecialchars($designation["name_$currentLang"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="institution" class="form-label">Institution</label>
                        <input type="text" class="form-control" id="institution" name="institution">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="saveNewTherapistBtn">Speichern</button>
            </div>
        </div>
    </div>
</div>

<!-- Shared Therapist Form JavaScript -->
<script>
// Save New Therapist button click handler
$('#saveNewTherapistBtn').click(function() {
    var formData = new FormData($('#newTherapistForm')[0]);
    formData.append('action', 'new_therapist');

    $.ajax({
        url: '<?php echo BASE_URL; ?>create_post_process.php',
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