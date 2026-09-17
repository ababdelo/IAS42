<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$conn = getDb();
$userId = (int)$_SESSION['user_id'];
generateCsrfToken();

function verifyFieldAccountPassword(PDO $conn, int $userId, string $password): bool
{
    if ($password === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT password FROM USERS WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    return is_string($hash) && $hash !== '' && password_verify($password, $hash);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    verifyCsrfToken($csrfToken, '/fields');

    if ($action === 'delete_field') {
        $fieldId = filter_input(INPUT_POST, 'field_id', FILTER_VALIDATE_INT) ?: 0;
        $password = (string)($_POST['password'] ?? '');

        if ($fieldId <= 0 || !verifyFieldAccountPassword($conn, $userId, $password)) {
            $_SESSION['error'] = 'The field could not be deleted. Please verify your account password.';
            header('Location: /fields');
            exit;
        }

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare(
                'DELETE n
                 FROM IOT_NODES n
                 INNER JOIN SECTORS s ON s.id = n.sector_id
                 INNER JOIN FIELDS f ON f.id = s.field_id
                 WHERE f.id = ? AND f.user_id = ?'
            );
            $stmt->execute([$fieldId, $userId]);

            $stmt = $conn->prepare('DELETE FROM FIELDS WHERE id = ? AND user_id = ?');
            $stmt->execute([$fieldId, $userId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('Field not found or access denied.');
            }

            $conn->commit();
            $_SESSION['success'] = 'Field and its related sector nodes were permanently deleted.';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            $_SESSION['error'] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Database error while deleting the field.';
        }

        header('Location: /fields');
        exit;
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $location = trim((string)($_POST['location'] ?? ''));
    $area = (float)($_POST['area'] ?? 0);

    if ($name === '' || mb_strlen($name) > 255) {
        $_SESSION['warning'] = $name === '' ? 'Field name is required.' : 'Field name is too long.';
        header('Location: /fields');
        exit;
    }

    if ($area < 0) {
        $_SESSION['warning'] = 'Field area cannot be negative.';
        header('Location: /fields');
        exit;
    }

    if ($action === 'add_field') {
        try {
            $stmt = $conn->prepare('INSERT INTO FIELDS (user_id, name, location, area) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $name, $location, $area]);
            $_SESSION['success'] = 'Field added successfully.';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Database error while adding field.';
        }

        header('Location: /fields');
        exit;
    }

    if ($action === 'edit_field') {
        $fieldId = filter_input(INPUT_POST, 'field_id', FILTER_VALIDATE_INT) ?: 0;
        $stmt = $conn->prepare('SELECT id FROM FIELDS WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$fieldId, $userId]);

        if (!$stmt->fetchColumn()) {
            $_SESSION['error'] = 'Field not found or access denied.';
            header('Location: /fields');
            exit;
        }

        $stmt = $conn->prepare('UPDATE FIELDS SET name = ?, location = ?, area = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$name, $location, $area, $fieldId, $userId]);
        $_SESSION['success'] = 'Field updated successfully.';
        header('Location: /fields');
        exit;
    }
}

// --- Fetch User's Fields with Analytics ---
$stmt = $conn->prepare("
    SELECT f.*, COUNT(DISTINCT s.id) as sector_count, COUNT(DISTINCT n.id) as node_count
    FROM FIELDS f
    LEFT JOIN SECTORS s ON f.id = s.field_id
    LEFT JOIN IOT_NODES n ON s.id = n.sector_id
    WHERE f.user_id = ?
    GROUP BY f.id
");
$stmt->execute([$userId]);
$fields = $stmt->fetchAll();
$fieldIcons = [
    'ri-leaf-fill',
    'ri-plant-fill',
    'ri-flower-fill',
    'ri-tree-fill'
];

$pageTitle = 'fields';
$extraCss = '<link rel="stylesheet" href="/assets/css/fields.css">';
$extraJs  = '<script src="/assets/js/fields.js" defer></script>';
require_once __DIR__ . "/components/sideBar.php";
?>

<div class="fields-wrapper">
    <div class="page-header-actions">
        <div>
            <h1 data-i18n="fields.page.title">My Fields</h1>
        </div>
        <?php if (!empty($fields)): ?>
            <button type="button" class="btn-primary" id="openFieldModalBtn">
                <i class="ri-add-line"></i> <span data-i18n="fields.page.add_field">Add New Field</span>
            </button>
        <?php endif; ?>
    </div>

    <div class="farms-list">
        <?php if (empty($fields)): ?>
            <div class="fields-empty">
                <img src="/assets/imgs/icons/noItemFound.webp" alt="">
                <h3>No fields found.</h3>
                <p>Create your first field to get started!</p>
                <button type="button" class="btn-primary" id="openFieldModalBtn">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    <span>Add New Field</span>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($fields as $field): ?>
                <?php $fieldIcon = $fieldIcons[(int)$field['id'] % count($fieldIcons)]; ?>
                <article
                    class="farm-row-card"
                    data-field-id="<?= (int)$field['id'] ?>"
                    data-field-name="<?= htmlspecialchars($field['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-field-location="<?= htmlspecialchars($field['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-field-area="<?= htmlspecialchars((string)($field['area'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="farm-row__icon bg-gradient-1">
                        <i class="<?= htmlspecialchars($fieldIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div class="farm-row__core">
                        <div class="farm-row__info">
                            <h3><?= htmlspecialchars($field['name']) ?></h3>
                            <span class="location"><i class="ri-map-pin-line"></i> <?= htmlspecialchars($field['location'] ?? 'Unknown') ?></span>
                        </div>
                        <div class="farm-row__stats">
                            <div class="stat-item">
                                <span class="stat-label">Total Area</span>
                                <div class="stat-val"><strong><?= $field['area'] ?></strong> <small>ha</small></div>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-label">Active Nodes</span>
                                <div class="stat-val"><strong><?= $field['node_count'] ?></strong> <small>Nodes</small></div>
                            </div>
                        </div>
                    </div>
                    <div class="farm-row__actions">
                        <div class="icon-actions">
                            <button type="button" class="action-btn edit" data-field-action="edit" aria-label="Edit field" title="Edit field">
                                <i class="ri-edit-line" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="action-btn delete" data-field-action="delete" aria-label="Delete field" title="Delete field">
                                <i class="ri-delete-bin-line" aria-hidden="true"></i>
                            </button>
                        </div>
                        <a href="/sectors?field=<?= $field['id'] ?>" class="btn-enter">
                            <span data-i18n="fields.actions.manage_zones">Manage Zones</span> <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/components/modals/fields/add.php';
require_once __DIR__ . '/components/modals/fields/edit.php';
require_once __DIR__ . '/components/modals/fields/delete.php';
?>
<?php require_once __DIR__ . "/components/footer.php"; ?>
