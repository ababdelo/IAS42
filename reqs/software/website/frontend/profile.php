<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();
generateCsrfToken();

$conn = getDb();
$userId = (int)$_SESSION['user_id'];

$stmt = $conn->prepare('SELECT id, first_name, last_name, username, email, gender, avatarType, avatar, password, accountType FROM USERS WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    http_response_code(404);
    exit('Account not found.');
}

$profileUser['resolved_avatar'] = getAvatarPath($profileUser);

$hasPassword = !empty($profileUser['password']);
$gender = (string)($profileUser['gender'] ?? 'not specified');
$avatarType = (string)($profileUser['avatarType'] ?? 'default');
$avatarValue = (string)($profileUser['avatar'] ?? 'nouser.webp');

$pageTitle = 'profile';
$extraCss = '<link rel="stylesheet" href="/assets/css/profile.css">';
$extraJs = '<script src="/assets/js/profile.js" defer></script>';

require_once __DIR__ . '/components/sideBar.php';
?>

<section class="profile-page" aria-labelledby="profile-page-title">
    <header class="profile-page-header">
        <h1 id="profile-page-title" data-i18n="profile.title">Profile</h1>
        <p data-i18n="profile.subtitle">Manage your personal information and account password.</p>
    </header>

    <article class="profile-card">
        <form id="profileForm" class="profile-form" method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" id="profileCsrfToken" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="default_avatar" id="defaultAvatarInput" value="">

            <div class="profile-picture" id="profilePicture">
                <img
                    id="currentAvatar"
                    src="<?= htmlspecialchars($profileUser['resolved_avatar'] ?? '/assets/imgs/avatars/nouser.webp', ENT_QUOTES, 'UTF-8') ?>"
                    alt=""
                    data-current-type="<?= htmlspecialchars($avatarType, ENT_QUOTES, 'UTF-8') ?>"
                    data-current-value="<?= htmlspecialchars($avatarValue, ENT_QUOTES, 'UTF-8') ?>"
                >

                <button
                    type="button"
                    class="avatar-action"
                    id="openAvatarModal"
                    aria-label="Change profile picture"
                >
                    <i class="ri-camera-line" aria-hidden="true"></i>
                </button>
            </div>

            <div class="profile-fields">
                <div class="profile-row">
                    <div class="profile-field profile-editable" data-field="first_name">
                        <i class="ri-user-3-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="text"
                            name="first_name"
                            id="profileFirstName"
                            maxlength="42"
                            autocomplete="given-name"
                            value="<?= htmlspecialchars((string)$profileUser['first_name'], ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            placeholder=" "
                        >

                        <label for="profileFirstName" data-i18n="profile.first_name">First Name</label>

                        <button
                            type="button"
                            class="profile-field-action edit-field"
                            data-target="profileFirstName"
                            aria-label="Edit first name"
                        >
                            <i class="ri-pencil-line" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="profile-field profile-editable" data-field="last_name">
                        <i class="ri-user-3-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="text"
                            name="last_name"
                            id="profileLastName"
                            maxlength="42"
                            autocomplete="family-name"
                            value="<?= htmlspecialchars((string)$profileUser['last_name'], ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            placeholder=" "
                        >

                        <label for="profileLastName" data-i18n="profile.last_name">Last Name</label>

                        <button
                            type="button"
                            class="profile-field-action edit-field"
                            data-target="profileLastName"
                            aria-label="Edit last name"
                        >
                            <i class="ri-pencil-line" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="profile-row">
                    <div class="profile-field profile-editable" data-field="username">
                        <i class="ri-at-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="text"
                            name="username"
                            id="profileUsername"
                            maxlength="42"
                            autocomplete="username"
                            value="<?= htmlspecialchars((string)$profileUser['username'], ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            placeholder=" "
                        >

                        <label for="profileUsername" data-i18n="profile.username">Username</label>

                        <button
                            type="button"
                            class="profile-field-action edit-field"
                            data-target="profileUsername"
                            aria-label="Edit username"
                        >
                            <i class="ri-pencil-line" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="profile-field profile-readonly">
                        <i class="ri-mail-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="email"
                            name="email"
                            id="profileEmail"
                            value="<?= htmlspecialchars((string)$profileUser['email'], ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            placeholder=" "
                        >

                        <label for="profileEmail" data-i18n="profile.email">Email</label>

                        <i class="ri-lock-line profile-field-trailing" aria-hidden="true"></i>
                    </div>
                </div>

                <?php if ($hasPassword): ?>
                    <div class="profile-field password-field profile-password-current">
                        <i class="ri-lock-2-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="password"
                            name="current_password"
                            id="currentPassword"
                            autocomplete="current-password"
                            placeholder=" "
                        >

                        <label for="currentPassword" data-i18n="profile.current_password">Current Password</label>

                        <button
                            type="button"
                            class="profile-field-action password-toggle"
                            data-target="currentPassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i class="ri-eye-off-line" aria-hidden="true"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="profile-row">
                    <div class="profile-field password-field">
                        <i class="ri-lock-2-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="password"
                            name="new_password"
                            id="newPassword"
                            autocomplete="new-password"
                            placeholder=" "
                        >

                        <label for="newPassword" data-i18n="profile.new_password">New Password</label>

                        <button
                            type="button"
                            class="profile-field-action password-toggle"
                            data-target="newPassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i class="ri-eye-off-line" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="profile-field password-field">
                        <i class="ri-lock-2-line profile-field-icon" aria-hidden="true"></i>

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirmPassword"
                            autocomplete="new-password"
                            placeholder=" "
                        >

                        <label for="confirmPassword" data-i18n="profile.confirm_password">Confirm Password</label>

                        <button
                            type="button"
                            class="profile-field-action password-toggle"
                            data-target="confirmPassword"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i class="ri-eye-off-line" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="generate-password" id="generateProfilePassword">
                    <i class="ri-settings-4-line" aria-hidden="true"></i>
                    <span data-i18n="profile.generate_password">Generate a Secure Password</span>
                </button>

                <div class="profile-actions">
                    <span class="profile-status" id="profileStatus" role="status" aria-live="polite"></span>

                    <button type="submit" class="profile-save-button" id="profileSaveButton">
                        <i class="ri-save-3-line" aria-hidden="true"></i>
                        <span data-i18n="profile.save">Save Changes</span>
                    </button>
                </div>
            </div>
        </form>
    </article>
</section>

<div class="profile-modal" id="avatarModal" hidden>
    <div class="profile-modal-backdrop" data-close-avatar-modal></div>

    <section
        class="profile-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="avatarModalTitle"
    >
        <button
            type="button"
            class="profile-modal-close"
            id="closeAvatarModal"
            aria-label="Close"
        >
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <h2 id="avatarModalTitle" data-i18n="profile.avatar.title">
            Change Profile Picture
        </h2>

        <div class="avatar-tabs" role="tablist">
            <button
                type="button"
                class="avatar-tab is-active"
                id="illustrationsTab"
                data-avatar-tab="illustrations"
                role="tab"
                aria-selected="true"
            >
                <span data-i18n="profile.avatar.illustrations">Illustrations</span>
                <i class="ri-palette-line" aria-hidden="true"></i>
            </button>

            <button
                type="button"
                class="avatar-tab"
                id="uploadTab"
                data-avatar-tab="upload"
                role="tab"
                aria-selected="false"
            >
                <span data-i18n="profile.avatar.computer">From Computer</span>
                <i class="ri-upload-2-line" aria-hidden="true"></i>
            </button>
        </div>

        <div class="avatar-modal-content">
            <div class="avatar-panel is-active" data-avatar-panel="illustrations">
                <div class="avatar-grid">
                    <?php
                    $avatarGenders = $gender === 'male'
                        ? ['m']
                        : ($gender === 'female' ? ['f'] : ['m', 'f']);

                    foreach ($avatarGenders as $avatarGender):
                        for ($i = 1; $i <= 32; $i++):
                            $filename = $avatarGender . $i . '.webp';
                            $avatarPath = "/assets/imgs/avatars/default/{$filename}";
                            $isCurrent = $avatarType === 'default' && $avatarValue === $filename;
                    ?>
                            <button
                                type="button"
                                class="avatar-option<?= $isCurrent ? ' is-selected' : '' ?>"
                                data-avatar-value="<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>"
                                aria-label="<?= htmlspecialchars(ucfirst($avatarGender) . ' avatar ' . $i, ENT_QUOTES, 'UTF-8') ?>"
                                aria-pressed="<?= $isCurrent ? 'true' : 'false' ?>"
                            >
                                <img
                                    src="<?= htmlspecialchars($avatarPath, ENT_QUOTES, 'UTF-8') ?>"
                                    alt=""
                                    loading="lazy"
                                >
                            </button>
                    <?php
                        endfor;
                    endforeach;
                    ?>
                </div>
            </div>

            <div class="avatar-panel" data-avatar-panel="upload" hidden>
                <label class="avatar-drop-area" id="avatarDropArea" for="profileAvatarUpload">
                    <i class="ri-upload-cloud-2-line" aria-hidden="true"></i>

                    <strong data-i18n="profile.avatar.drop_title">
                        Drag and drop an image here or click to browse
                    </strong>

                    <span data-i18n="profile.avatar.drop_description">
                        JPG, PNG or WEBP up to 2 MB.
                    </span>

                    <input
                        type="file"
                        name="avatar_file"
                        id="profileAvatarUpload"
                        accept="image/jpeg,image/png,image/webp"
                        hidden
                    >
                </label>

                <div class="avatar-upload-preview" id="avatarUploadPreview" hidden>
                    <img id="avatarUploadPreviewImage" src="" alt="">

                    <button
                        type="button"
                        id="removeAvatarUpload"
                        class="remove-avatar-upload"
                    >
                        <i class="ri-close-line" aria-hidden="true"></i>
                        <span data-i18n="profile.avatar.remove">
                            Remove selected image
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
