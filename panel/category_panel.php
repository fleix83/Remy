<?php
require_once __DIR__ . '/../includes/init.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                addCategory($pdo);
                break;
            case 'edit':
                editCategory($pdo);
                break;
            case 'delete':
                deleteCategory($pdo);
                break;
        }
    }
}

// Fetch categories
$categories = fetchCategories($pdo);

// Functions
function fetchCategories($pdo) {
    // If you want to exclude inactive categories, uncomment the next line
    // $stmt = $pdo->query("SELECT * FROM categories WHERE is_activ = 1 ORDER BY id ASC");
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addCategory($pdo) {
    $name_de = $_POST['name_de'];
    $name_fr = $_POST['name_fr'];
    $name_it = $_POST['name_it'];
    $description_de = $_POST['description_de'];
    $description_fr = $_POST['description_fr'];
    $description_it = $_POST['description_it'];
    $access_role = $_POST['access_role'];
    $is_activ = isset($_POST['is_activ']) ? 1 : 0;

    $stmt = $pdo->prepare("INSERT INTO categories (name_de, name_fr, name_it, description_de, description_fr, description_it, access_role, is_activ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name_de, $name_fr, $name_it, $description_de, $description_fr, $description_it, $access_role, $is_activ]);
}

function editCategory($pdo) {
    $id = $_POST['id'];
    $name_de = $_POST['name_de'];
    $name_fr = $_POST['name_fr'];
    $name_it = $_POST['name_it'];
    $description_de = $_POST['description_de'];
    $description_fr = $_POST['description_fr'];
    $description_it = $_POST['description_it'];
    $access_role = $_POST['access_role'];
    $is_activ = isset($_POST['is_activ']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE categories SET name_de = ?, name_fr = ?, name_it = ?, description_de = ?, description_fr = ?, description_it = ?, access_role = ?, is_activ = ? WHERE id = ?");
    $stmt->execute([$name_de, $name_fr, $name_it, $description_de, $description_fr, $description_it, $access_role, $is_activ, $id]);
}

function deleteCategory($pdo) {
    global $error, $success;

    $id = $_POST['id'];

    // Check if any posts are associated with this category
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ?");
    $stmt->execute([$id]);
    $postCount = $stmt->fetchColumn();

    if ($postCount > 0) {
        $error = "Kategorie kann nicht gelöscht werden, da noch Beiträge mit dieser Kategorie existieren.";
    } else {
        // Proceed to delete the category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Kategorie wurde erfolgreich gelöscht.";
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../navbar.php';
?>

<body>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

    <div class="container category-wrapper mt-5">
        <h1 class="panel">Kategorie-Verwaltung</h1>

        <!-- Add Category Form -->
        <h2 id="formTitle">Neue Kategorie erstellen</h2>
        <form action="" method="post" id="categoryForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="categoryId" value="">

            <?php
            $languages = ['de' => 'Deutsch', 'fr' => 'Français', 'it' => 'Italiano'];
            foreach ($languages as $lang => $langName):
            ?>
            <div class="mb-3">
                <label for="name_<?= $lang ?>" class="form-label">Name (<?= $langName ?>)</label>
                <input type="text" class="form-control" id="name_<?= $lang ?>" name="name_<?= $lang ?>">
            </div>
            <div class="mb-3">
                <label for="description_<?= $lang ?>" class="form-label">Beschreibung (<?= $langName ?>)</label>
                <textarea class="form-control" id="description_<?= $lang ?>" name="description_<?= $lang ?>"></textarea>
            </div>
            <?php endforeach; ?>

            <div class="mb-3">
                <label for="access_role" class="form-label">Zugriffsrolle</label>
                <select class="form-control" id="access_role" name="access_role">
                    <option value="all">Alle</option>
                    <option value="user">Benutzer</option>
                    <option value="moderator">Moderatoren</option>
                    <option value="admin">Administratoren</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_activ" name="is_activ" checked>
                <label class="form-check-label" for="is_activ">Aktiv</label>
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
        </form>

        <!-- List of Categories -->
        <h2 class="mt-5">Existierende Kategorien</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name (DE)</th>
                    <th>Name (FR)</th>
                    <th>Name (IT)</th>
                    <th>Zugriffsrolle</th>
                    <th>Aktiv</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= htmlspecialchars($category['id']) ?></td>
                    <td><?= htmlspecialchars($category['name_de']) ?></td>
                    <td><?= htmlspecialchars($category['name_fr']) ?></td>
                    <td><?= htmlspecialchars($category['name_it']) ?></td>
                    <td><?= htmlspecialchars($category['access_role']) ?></td>
                    <td><?= $category['is_activ'] ? 'Ja' : 'Nein' ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $category['id'] ?>">
                            Bearbeiten
                        </button>
                    </td>
                </tr>

                <!-- Edit Modal for each category -->
                <div class="modal fade" id="editModal<?= $category['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $category['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?= $category['id'] ?>">Kategorie bearbeiten</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="" method="post">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                    <div class="mb-3">
                                        <label for="name_de<?= $category['id'] ?>" class="form-label">Name (Deutsch)</label>
                                        <input type="text" class="form-control" id="name_de<?= $category['id'] ?>" name="name_de" value="<?= htmlspecialchars($category['name_de']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="name_fr<?= $category['id'] ?>" class="form-label">Name (Französisch)</label>
                                        <input type="text" class="form-control" id="name_fr<?= $category['id'] ?>" name="name_fr" value="<?= htmlspecialchars($category['name_fr']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="name_it<?= $category['id'] ?>" class="form-label">Name (Italienisch)</label>
                                        <input type="text" class="form-control" id="name_it<?= $category['id'] ?>" name="name_it" value="<?= htmlspecialchars($category['name_it']) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_de<?= $category['id'] ?>" class="form-label">Beschreibung (Deutsch)</label>
                                        <textarea class="form-control" id="description_de<?= $category['id'] ?>" name="description_de"><?= htmlspecialchars($category['description_de']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_fr<?= $category['id'] ?>" class="form-label">Beschreibung (Französisch)</label>
                                        <textarea class="form-control" id="description_fr<?= $category['id'] ?>" name="description_fr"><?= htmlspecialchars($category['description_fr']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_it<?= $category['id'] ?>" class="form-label">Beschreibung (Italienisch)</label>
                                        <textarea class="form-control" id="description_it<?= $category['id'] ?>" name="description_it"><?= htmlspecialchars($category['description_it']) ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="access_role<?= $category['id'] ?>" class="form-label">Zugriffsrolle</label>
                                        <select class="form-control" id="access_role<?= $category['id'] ?>" name="access_role">
                                            <option value="all" <?= $category['access_role'] === 'all' ? 'selected' : '' ?>>Alle</option>
                                            <option value="user" <?= $category['access_role'] === 'user' ? 'selected' : '' ?>>Benutzer</option>
                                            <option value="moderator" <?= $category['access_role'] === 'moderator' ? 'selected' : '' ?>>Moderatoren</option>
                                            <option value="admin" <?= $category['access_role'] === 'admin' ? 'selected' : '' ?>>Administratoren</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="is_activ<?= $category['id'] ?>" name="is_activ" <?= $category['is_activ'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_activ<?= $category['id'] ?>">Aktiv</label>
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

        <!-- Delete Category Form -->
        <h2 class="mt-5">Kategorie löschen</h2>
        <form action="" method="post" onsubmit="return confirm('Sind Sie sicher, dass Sie diese Kategorie löschen möchten?');">
            <input type="hidden" name="action" value="delete">
            <div class="mb-3">
                <label for="delete_category" class="form-label">Kategorie auswählen</label>
                <select class="form-control" id="delete_category" name="id" required>
                <?php
                    // Reorder categories for the delete dropdown
                    $categoriesForDelete = $categories;

                    // Move 'Erfahrung' to the end of the array
                    foreach ($categoriesForDelete as $key => $category) {
                        if ($category['name_de'] === 'Erfahrung') {
                            $erfahrungCategory = $category;
                            unset($categoriesForDelete[$key]);
                            $categoriesForDelete[] = $erfahrungCategory;
                            break;
                        }
                    }




                    foreach ($categoriesForDelete as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name_de']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger">Kategorie löschen</button>
        </form>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- No additional JavaScript code needed -->
</body>
</html>
