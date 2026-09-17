<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();
generateCsrfToken();

$conn = getDb();
$userId = (int)$_SESSION['user_id'];
$fieldId = filter_input(INPUT_GET, 'field', FILTER_VALIDATE_INT) ?: 0;

$stmt = $conn->prepare('SELECT id, name FROM FIELDS WHERE id = ? AND user_id = ? LIMIT 1');
$stmt->execute([$fieldId, $userId]);
$currentField = $stmt->fetch();

if (!$currentField) {
    $_SESSION['error'] = 'Field not found or access denied.';
    header('Location: /fields');
    exit;
}

function redirectToSectors(int $fieldId): never
{
    header('Location: /sectors?field=' . $fieldId);
    exit;
}

function normalizeMacAddress(string $value): string
{
    $value = strtoupper(trim($value));
    $hex = preg_replace('/[^0-9A-F]/', '', $value) ?? '';

    if (strlen($hex) !== 12 || !ctype_xdigit($hex)) {
        return '';
    }

    return implode(':', str_split($hex, 2));
}

function isValidNodeId(string $value): bool
{
    return (bool)preg_match('/^[A-Z0-9_-]{1,50}$/', $value);
}

function verifyCurrentAccountPassword(PDO $conn, int $userId, string $password): bool
{
    if ($password === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT password FROM USERS WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!is_string($hash) || $hash === '') {
        return false;
    }

    return password_verify($password, $hash);
}

function validateDefaultImage(string $selectedImage, string $plantsDir): ?string
{
    $selectedImage = basename($selectedImage);

    if ($selectedImage === '' || strtolower(pathinfo($selectedImage, PATHINFO_EXTENSION)) !== 'webp') {
        return null;
    }

    $filesystemPath = $plantsDir . $selectedImage;

    if (!is_file($filesystemPath)) {
        return null;
    }

    return '/assets/imgs/plants/' . $selectedImage;
}

function storeUploadedImage(array $file, string $customDir): array
{
    if (!isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Please select an image to upload.');
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed.');
    }

    if ((int)$file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('The image must be 2 MB or smaller.');
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('The uploaded image is invalid.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');
    }

    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }

    if (!is_dir($customDir) && !mkdir($customDir, 0755, true) && !is_dir($customDir)) {
        throw new RuntimeException('The image storage directory could not be created.');
    }

    $storedName = 'sector-' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $destination = $customDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The uploaded image could not be stored.');
    }

    return [
        'filesystem' => $destination,
        'public' => '/assets/imgs/plants/custom/' . $storedName
    ];
}

$plantsDir = __DIR__ . '/../assets/imgs/plants/';
$customPlantsDir = $plantsDir . 'custom/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if ($action === 'add_sector') {
        verifyCsrfToken($csrfToken, '/sectors?field=' . $fieldId);

        $sectorName = trim((string)($_POST['sector_name'] ?? ''));
        $nodeId = strtoupper(trim((string)($_POST['node_id'] ?? '')));
        $macAddress = normalizeMacAddress((string)($_POST['mac_address'] ?? ''));
        $cropType = trim((string)($_POST['crop_type'] ?? ''));
        $customCrop = trim((string)($_POST['custom_crop'] ?? ''));
        $imageMode = ($_POST['image_mode'] ?? 'default') === 'upload' ? 'upload' : 'default';
        $selectedImage = basename((string)($_POST['sector_image'] ?? ''));
        $storedUpload = null;
        $errors = [];

        if ($sectorName === '') {
            $errors[] = 'Sector name is required.';
        } elseif (mb_strlen($sectorName) > 255) {
            $errors[] = 'Sector name is too long.';
        }

        if ($nodeId === '') {
            $errors[] = 'ESP node ID is required.';
        } elseif (!isValidNodeId($nodeId)) {
            $errors[] = 'ESP node ID contains invalid characters.';
        }

        if ($macAddress === '') {
            $errors[] = 'MAC address must use the format AA:BB:CC:DD:EE:FF.';
        }

        if ($cropType === '') {
            $errors[] = 'Crop selection is required.';
        } elseif ($cropType === 'other') {
            if ($customCrop === '') {
                $errors[] = 'Custom crop name is required.';
            } elseif (mb_strlen($customCrop) > 100) {
                $errors[] = 'Custom crop name is too long.';
            }
            $cropType = $customCrop;
        }

        $imagePath = null;

        if ($imageMode === 'default') {
            if ($selectedImage !== '') {
                $imagePath = validateDefaultImage($selectedImage, $plantsDir);
                if ($imagePath === null) {
                    $errors[] = 'The selected default image is invalid.';
                }
            }
        }

        if ($imageMode === 'upload') {
            try {
                $uploaded = storeUploadedImage($_FILES['sector_image_file'] ?? [], $customPlantsDir);
                $storedUpload = $uploaded['filesystem'];
                $imagePath = $uploaded['public'];
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            try {
                $stmt = $conn->prepare('SELECT id, sector_id FROM IOT_NODES WHERE node_id = ? LIMIT 1');
                $stmt->execute([$nodeId]);
                $existingNode = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existingNode && $existingNode['sector_id'] !== null) {
                    throw new RuntimeException('This ESP node ID is already registered.');
                }

                $stmt = $conn->prepare('SELECT id FROM IOT_NODES WHERE mac_address = ? LIMIT 1');
                $stmt->execute([$macAddress]);
                $existingMacNodeId = $stmt->fetchColumn();
                if ($existingMacNodeId && (!$existingNode || (int)$existingMacNodeId !== (int)$existingNode['id'])) {
                    throw new RuntimeException('This MAC address is already registered.');
                }

                $conn->beginTransaction();

                $stmt = $conn->prepare('INSERT INTO SECTORS (field_id, name, crop_name, image_path) VALUES (?, ?, ?, ?)');
                $stmt->execute([$fieldId, $sectorName, $cropType, $imagePath]);
                $sectorId = (int)$conn->lastInsertId();

                $secret = $nodeId === 'A84F92'
                    ? 'c72f9a83d90c72f9a83d90c72f9a83d90'
                    : bin2hex(random_bytes(16));

                if ($existingNode) {
                    $stmt = $conn->prepare('UPDATE IOT_NODES SET sector_id = ?, secret_hash = ?, mac_address = ? WHERE id = ?');
                    $stmt->execute([$sectorId, $secret, $macAddress, (int)$existingNode['id']]);
                } else {
                    $stmt = $conn->prepare('INSERT INTO IOT_NODES (sector_id, node_id, secret_hash, mac_address) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$sectorId, $nodeId, $secret, $macAddress]);
                }

                $conn->commit();

                $_SESSION['success'] = 'Sector and hardware node were successfully registered.';
                redirectToSectors($fieldId);
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                if ($storedUpload && is_file($storedUpload)) {
                    @unlink($storedUpload);
                }

                $_SESSION['error'] = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Database error while saving the sector.';
                redirectToSectors($fieldId);
            }
        }

        if ($storedUpload && is_file($storedUpload)) {
            @unlink($storedUpload);
        }

        $_SESSION['warning'] = implode(' ', $errors);
        redirectToSectors($fieldId);
    }

    if ($action === 'edit_sector') {
        verifyCsrfToken($csrfToken, '/sectors?field=' . $fieldId);

        $sectorId = filter_input(INPUT_POST, 'sector_id', FILTER_VALIDATE_INT) ?: 0;
        $sectorName = trim((string)($_POST['sector_name'] ?? ''));
        $nodeId = strtoupper(trim((string)($_POST['node_id'] ?? '')));
        $macAddress = normalizeMacAddress((string)($_POST['mac_address'] ?? ''));
        $cropType = trim((string)($_POST['crop_type'] ?? ''));
        $customCrop = trim((string)($_POST['custom_crop'] ?? ''));
        $imageMode = (string)($_POST['image_mode'] ?? 'keep');
        $selectedImage = basename((string)($_POST['sector_image'] ?? ''));
        $storedUpload = null;
        $newImagePath = null;
        $errors = [];

        if ($sectorId <= 0) {
            $errors[] = 'Invalid sector.';
        }

        $stmt = $conn->prepare('SELECT s.id, s.name, s.crop_name, s.image_path, n.id AS node_db_id, n.node_id, n.mac_address FROM SECTORS s LEFT JOIN IOT_NODES n ON n.sector_id = s.id INNER JOIN FIELDS f ON f.id = s.field_id WHERE s.id = ? AND s.field_id = ? AND f.user_id = ? LIMIT 1');
        $stmt->execute([$sectorId, $fieldId, $userId]);
        $currentSector = $stmt->fetch();

        if (!$currentSector) {
            $_SESSION['error'] = 'Sector not found or access denied.';
            redirectToSectors($fieldId);
        }

        if ($sectorName === '') {
            $errors[] = 'Sector name is required.';
        } elseif (mb_strlen($sectorName) > 255) {
            $errors[] = 'Sector name is too long.';
        }

        if ($nodeId === '') {
            $errors[] = 'ESP node ID is required.';
        } elseif (!isValidNodeId($nodeId)) {
            $errors[] = 'ESP node ID contains invalid characters.';
        }

        if ($macAddress === '') {
            $errors[] = 'MAC address must use the format AA:BB:CC:DD:EE:FF.';
        }

        if ($cropType === '') {
            $errors[] = 'Crop selection is required.';
        } elseif ($cropType === 'other') {
            if ($customCrop === '') {
                $errors[] = 'Custom crop name is required.';
            } elseif (mb_strlen($customCrop) > 100) {
                $errors[] = 'Custom crop name is too long.';
            }
            $cropType = $customCrop;
        }

        if ($imageMode === 'default') {
            if ($selectedImage === '') {
                $errors[] = 'Please select a default image or keep the current image.';
            } else {
                $newImagePath = validateDefaultImage($selectedImage, $plantsDir);
                if ($newImagePath === null) {
                    $errors[] = 'The selected default image is invalid.';
                }
            }
        } elseif ($imageMode === 'upload') {
            try {
                $uploaded = storeUploadedImage($_FILES['sector_image_file'] ?? [], $customPlantsDir);
                $storedUpload = $uploaded['filesystem'];
                $newImagePath = $uploaded['public'];
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        } else {
            $newImagePath = $currentSector['image_path'] !== null
                ? (string)$currentSector['image_path']
                : null;
        }

        if (!$errors) {
            try {
                $stmt = $conn->prepare('SELECT id FROM IOT_NODES WHERE node_id = ? AND id <> ? LIMIT 1');
                $stmt->execute([$nodeId, (int)$currentSector['node_db_id']]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('This ESP node ID is already registered.');
                }

                $stmt = $conn->prepare('SELECT id FROM IOT_NODES WHERE mac_address = ? AND id <> ? LIMIT 1');
                $stmt->execute([$macAddress, (int)$currentSector['node_db_id']]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('This MAC address is already registered.');
                }

                $conn->beginTransaction();

                $stmt = $conn->prepare('UPDATE SECTORS SET name = ?, crop_name = ?, image_path = ? WHERE id = ? AND field_id = ?');
                $stmt->execute([$sectorName, $cropType, $newImagePath, $sectorId, $fieldId]);

                if ($currentSector['node_db_id']) {
                    $stmt = $conn->prepare('UPDATE IOT_NODES SET node_id = ?, mac_address = ? WHERE id = ? AND sector_id = ?');
                    $stmt->execute([
                        $nodeId,
                        $macAddress,
                        (int)$currentSector['node_db_id'],
                        $sectorId
                    ]);
                }

                $conn->commit();

                $oldImage = (string)($currentSector['image_path'] ?? '');

                if (
                    $oldImage !== '' &&
                    $oldImage !== (string)$newImagePath &&
                    str_starts_with($oldImage, '/assets/imgs/plants/custom/')
                ) {
                    $oldFilesystem = __DIR__ . '/..' . $oldImage;

                    if (is_file($oldFilesystem)) {
                        @unlink($oldFilesystem);
                    }
                }

                $_SESSION['success'] = 'Sector details were successfully updated.';
                redirectToSectors($fieldId);
            } catch (Throwable $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                if ($storedUpload && is_file($storedUpload)) {
                    @unlink($storedUpload);
                }

                $_SESSION['error'] = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Database error while updating the sector.';
                redirectToSectors($fieldId);
            }
        }

        if ($storedUpload && is_file($storedUpload)) {
            @unlink($storedUpload);
        }

        $_SESSION['warning'] = implode(' ', $errors);
        redirectToSectors($fieldId);
    }

    if ($action === 'delete_sector') {
        verifyCsrfToken($csrfToken, '/sectors?field=' . $fieldId);

        $sectorId = filter_input(INPUT_POST, 'sector_id', FILTER_VALIDATE_INT) ?: 0;
        $password = (string)($_POST['password'] ?? '');

        if ($sectorId <= 0 || !verifyCurrentAccountPassword($conn, $userId, $password)) {
            $_SESSION['error'] = 'The sector could not be deleted. Please verify your account password.';
            redirectToSectors($fieldId);
        }

        $stmt = $conn->prepare('SELECT s.id, s.image_path FROM SECTORS s INNER JOIN FIELDS f ON f.id = s.field_id WHERE s.id = ? AND s.field_id = ? AND f.user_id = ? LIMIT 1');
        $stmt->execute([$sectorId, $fieldId, $userId]);
        $sectorToDelete = $stmt->fetch();

        if (!$sectorToDelete) {
            $_SESSION['error'] = 'Sector not found or access denied.';
            redirectToSectors($fieldId);
        }

        $customImage = (string)($sectorToDelete['image_path'] ?? '');

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare('DELETE FROM IOT_NODES WHERE sector_id = ?');
            $stmt->execute([$sectorId]);

            $stmt = $conn->prepare('DELETE FROM SECTORS WHERE id = ? AND field_id = ?');
            $stmt->execute([$sectorId, $fieldId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('The sector could not be deleted.');
            }

            $conn->commit();

            if ($customImage !== '' && str_starts_with($customImage, '/assets/imgs/plants/custom/')) {
                $customFilesystem = __DIR__ . '/..' . $customImage;
                if (is_file($customFilesystem)) {
                    @unlink($customFilesystem);
                }
            }

            $_SESSION['success'] = 'Sector and its related node data were permanently deleted.';
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['error'] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Database error while deleting the sector.';
        }

        redirectToSectors($fieldId);
    }
}

$stmt = $conn->prepare('
    SELECT
        s.id,
        s.name,
        s.crop_name,
        s.image_path,
        n.id AS node_db_id,
        n.node_id,
        n.mac_address,
        n.last_seen,
        CASE
            WHEN n.status = \'maintenance\' THEN \'maintenance\'
            WHEN n.last_seen IS NOT NULL AND TIMESTAMPDIFF(SECOND, n.last_seen, CURRENT_TIMESTAMP) <= 30 THEN \'online\'
            ELSE \'offline\'
        END AS node_status
    FROM SECTORS s
    LEFT JOIN IOT_NODES n ON n.sector_id = s.id
    WHERE s.field_id = ?
    ORDER BY s.created_at ASC, s.id ASC
');
$stmt->execute([$fieldId]);
$sectors = $stmt->fetchAll();

$cropFiles = [];
if (is_dir($plantsDir)) {
    foreach (glob($plantsDir . '*.webp') ?: [] as $file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        if ($filename === 'no image' || $filename === 'icons8-melon-64') {
            continue;
        }
        $cropFiles[] = $filename;
    }
}
sort($cropFiles, SORT_NATURAL | SORT_FLAG_CASE);

$pageTitle = 'sectors';
$extraCss = '<link rel="stylesheet" href="/assets/css/sectors.css">';
$extraJs = '<script src="/assets/js/sectors.js?v=20260913-1700" defer></script>';
require_once __DIR__ . '/components/sideBar.php';
?>

<div class="sectors-wrapper">
    <div class="page-header-actions">
        <div>
            <nav class="page-breadcrumb" aria-label="Breadcrumb">
                <a href="/fields" data-i18n="sectors.page.back">Fields</a>
                <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
                <span><?= htmlspecialchars($currentField['name'], ENT_QUOTES, 'UTF-8') ?></span>
                <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
                <span class="page-breadcrumb__current" data-i18n="sectors.page.title">Sectors</span>
            </nav>
            <h1 data-i18n="sectors.page.title">Sectors</h1>
        </div>

        <?php if ($sectors): ?>
            <button type="button" class="btn-solid-primary" id="openAddModalBtn">
                <i class="ri-add-line" aria-hidden="true"></i>
                <span data-i18n="sectors.page.add_sector">Add Sector</span>
            </button>
        <?php endif; ?>
    </div>

    <div class="sectors-grid" id="sectorsGrid">
        <?php if (!$sectors): ?>
            <div class="sectors-empty">
                <img src="/assets/imgs/icons/noItemFound.webp" alt="">
                <h3>No sectors found.</h3>
                <p>Create your first sector to get started!</p>
                <button type="button" class="btn-solid-primary" id="openAddModalBtn">
                    <i class="ri-add-line" aria-hidden="true"></i>
                    <span data-i18n="sectors.page.add_sector">Add Sector</span>
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($sectors as $sector): ?>
                <?php
                $cropSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string)$sector['crop_name']), '-'));
                $imageSrc = !empty($sector['image_path'])
                    ? (string)$sector['image_path']
                    : '/assets/imgs/plants/' . $cropSlug . '.webp';
                $status = (string)($sector['node_status'] ?? 'offline');
                ?>
                <article
                    class="sector-card-square"
                    data-sector-id="<?= (int)$sector['id'] ?>"
                    data-sector-name="<?= htmlspecialchars((string)$sector['name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-crop-name="<?= htmlspecialchars((string)$sector['crop_name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-image-path="<?= htmlspecialchars((string)($sector['image_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-node-id="<?= htmlspecialchars((string)($sector['node_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-mac-address="<?= htmlspecialchars((string)($sector['mac_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="sc-header">
                        <div class="sc-icon">
                            <img
                                src="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars((string)$sector['crop_name'], ENT_QUOTES, 'UTF-8') ?>"
                                onerror="this.src='/assets/imgs/plants/no image.webp'">
                        </div>
                        <div class="sc-actions">
                            <button
                                type="button"
                                class="action-btn edit"
                                data-sector-action="edit"
                                aria-label="Edit sector"
                                title="Edit sector"
                                data-i18n-title="sectors.card.edit">
                                <i class="ri-pencil-line" aria-hidden="true"></i>
                            </button>
                            <button
                                type="button"
                                class="action-btn delete"
                                data-sector-action="delete"
                                aria-label="Delete sector"
                                title="Delete sector"
                                data-i18n-title="sectors.card.delete">
                                <i class="ri-delete-bin-line" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="sc-body">
                        <h3><?= htmlspecialchars((string)$sector['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="sc-device-info">
                            <span class="sc-node">
                                <i class="ri-router-line" aria-hidden="true"></i>
                                <?= htmlspecialchars((string)($sector['node_id'] ?: 'Unassigned'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (!empty($sector['mac_address'])): ?>
                                <span class="sc-mac" title="MAC address">
                                    <i class="fa-solid fa-microchip" aria-hidden="true"></i>
                                    <?= htmlspecialchars((string)$sector['mac_address'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sc-footer">
                        <div class="sc-stat">
                            <span data-i18n="sectors.card.crop">Crop</span>
                            <strong><?= htmlspecialchars(ucwords(str_replace('-', ' ', (string)$sector['crop_name'])), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="sc-divider" aria-hidden="true"></div>
                        <div class="sc-stat">
                            <span data-i18n="sectors.card.status">Status</span>
                            <span class="status-badge <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($status === 'online'): ?><span class="pulse-dot"></span><?php endif; ?>
                                <span data-status-label><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/components/modals/sectors/add.php'; ?>
<?php require_once __DIR__ . '/components/modals/sectors/edit.php'; ?>
<?php require_once __DIR__ . '/components/modals/sectors/delete.php'; ?>
<?php require_once __DIR__ . '/components/footer.php'; ?>
