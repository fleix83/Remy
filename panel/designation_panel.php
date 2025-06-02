<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/language_utils.php'; 

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$currentLang = getCurrentLanguage();

// Initialize message variable
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                addDesignation($pdo);
                $message = 'Designation added successfully.';
                break;
            case 'edit':
                editDesignation($pdo);
                $message = 'Designation updated successfully.';
                break;
            case 'delete':
                deleteDesignation($pdo);
                $message = 'Designation deleted successfully.';
                break;
        }
    }
       // Redirect after POST to prevent form resubmission
       header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
       exit();
   }

   // Display message if set
    if (isset($_GET['message'])) {
        $message = $_GET['message'];
    }

// Fetch designations
$designations = fetchDesignations($pdo);

// Functions
function fetchDesignations($pdo) {
    $stmt = $pdo->query("SELECT * FROM designations ORDER BY name_de ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addDesignation($pdo) {
    $name_de = $_POST['name_de'];
    $name_fr = $_POST['name_fr'];
    $name_it = $_POST['name_it'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO designations (name_de, name_fr, name_it, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name_de, $name_fr, $name_it, $is_active]);
}

function editDesignation($pdo) {
    $id = $_POST['id'];
    $name_de = $_POST['name_de'];
    $name_fr = $_POST['name_fr'];
    $name_it = $_POST['name_it'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE designations SET name_de = ?, name_fr = ?, name_it = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$name_de, $name_fr, $name_it, $is_active, $id]);
}

function deleteDesignation($pdo) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM designations WHERE id = ?");
    $stmt->execute([$id]);
}

// Header, Navbar after AJAX calls
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../navbar.php';
?>


<body>
    <div class="container designation col-md-8 mt-5">
        <h1 class="panel mb-3">Berufsbezeichnungen</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Add Designation Form -->
        <h2 class="panel mb-4">Neue Bezeichnung erstellen</h2>
        <form action="" method="post" id="designationForm">
            <input type="hidden" name="action" value="add">

            <?php
            $languages = ['de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano'];
            foreach ($languages as $lang => $langName):
            ?>

            <div class="mb-3">
                <label for="name_<?= $lang ?>" class="form-label">Name (<?= $langName ?>)</label>
                <input type="text" class="form-control designation-field" id="name_<?= $lang ?>" name="name_<?= $lang ?>" required>
            </div>
            <?php endforeach; ?>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                <label class="form-check-label" for="is_active">Aktiv</label>
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>

        <!-- List of Designations -->
        <h2 class="panel mt-5">Berufstitel</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Aktiv</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($designations as $designation): ?>
                <tr>
                    <td><?= htmlspecialchars($designation['id']) ?></td>
                    <td><?= htmlspecialchars($designation['name_' . $currentLang] ?? '') ?></td>
                    <td><?= $designation['is_active'] ? 'Ja' : 'Nein' ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $designation['id'] ?>">
                            Bearbeiten
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal for each designation -->
                <div class="modal fade" id="editModal<?= $designation['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $designation['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?= $designation['id'] ?>">Bezeichnung bearbeiten</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="" method="post">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $designation['id'] ?>">
                                    <?php foreach ($languages as $lang => $langName): ?>
                                    <div class="mb-3">
                                        <label for="name_<?= $lang ?><?= $designation['id'] ?>" class="form-label">Name (<?= $langName ?>)</label>
                                        <input type="text" class="form-control" id="name_<?= $lang ?><?= $designation['id'] ?>" name="name_<?= $lang ?>" value="<?= htmlspecialchars($designation['name_'.$lang] ?? '') ?>" required>
                                    </div>
                                    <?php endforeach; ?>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active<?= $designation['id'] ?>" name="is_active" <?= $designation['is_active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active<?= $designation['id'] ?>">Aktiv</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Delete Designation Form -->
    <h2 class="panel mt-5">Bezeichnung löschen</h2>
    <form action="" method="post" onsubmit="return confirm('Sind Sie sicher, dass Sie diese Bezeichnung löschen möchten?');">
        <input type="hidden" name="action" value="delete">
        <div class="mb-3">
            <label for="delete_designation" class="form-label">Bezeichnung auswählen</label>
            <select class="form-control" id="delete_designation" name="id" required>
                <?php foreach ($designations as $designation): ?>
                    <option value="<?= $designation['id'] ?>">
                         <?= htmlspecialchars($designation['name_' . $currentLang] ?? $designation['name_de']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-danger">Löschen</button>
    </form>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>