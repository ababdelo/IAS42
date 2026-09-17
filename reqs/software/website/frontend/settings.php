<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

generateCsrfToken();
$pageTitle = 'settings';
$extraCss = '<link rel="stylesheet" href="/assets/css/settings.css">';
$extraJs = '<script src="/assets/js/settings.js" defer></script>';

require_once __DIR__ . '/components/sideBar.php';

$settingsUser = $user ?? getUserDetails(getDb(), (int)$_SESSION['user_id']);
$settingsUser = is_array($settingsUser) ? $settingsUser : [];
$firstName = (string)($settingsUser['first_name'] ?? '');
$lastName = (string)($settingsUser['last_name'] ?? '');
$username = (string)($settingsUser['username'] ?? '');
$email = (string)($settingsUser['email'] ?? '');
$accountType = (string)($settingsUser['accountType'] ?? 'normal');
?>
<section class="ias-page settings-page" aria-labelledby="settings-page-title">
    <header class="ias-page-header">
        <div>
            <h1 id="settings-page-title" data-i18n="settings.title">Settings</h1>
            <p data-i18n="settings.subtitle">Manage your profile, preferences, and notifications.</p>
        </div>
    </header>

    <div class="settings-layout">
        <div class="settings-main-column">
            <article class="settings-card">
                <header class="settings-card-header">
                    <div>
                        <h2 data-i18n="settings.profile.title">Profile</h2>
                        <p data-i18n="settings.profile.subtitle">Update your account details.</p>
                    </div>
                    <div class="settings-avatar" aria-hidden="true">
                        <img src="<?= htmlspecialchars($avatarSrc ?? '/assets/imgs/avatars/nouser.webp') ?>" alt="">
                    </div>
                </header>

                <form id="settingsProfileForm" class="settings-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="settings-field">
                        <label for="settingsFirstName" data-i18n="settings.profile.first_name">First name</label>
                        <input id="settingsFirstName" name="first_name" type="text" maxlength="42" autocomplete="given-name" value="<?= htmlspecialchars($firstName) ?>">
                    </div>

                    <div class="settings-field">
                        <label for="settingsLastName" data-i18n="settings.profile.last_name">Last name</label>
                        <input id="settingsLastName" name="last_name" type="text" maxlength="42" autocomplete="family-name" value="<?= htmlspecialchars($lastName) ?>">
                    </div>

                    <div class="settings-field">
                        <label for="settingsUsername" data-i18n="settings.profile.username">Username</label>
                        <input id="settingsUsername" name="username" type="text" maxlength="42" autocomplete="username" value="<?= htmlspecialchars($username) ?>">
                    </div>

                    <div class="settings-field">
                        <label for="settingsEmail" data-i18n="settings.profile.email">Email</label>
                        <input id="settingsEmail" name="email" type="email" value="<?= htmlspecialchars($email) ?>" readonly>
                        <small data-i18n="settings.profile.email_readonly">Email changes require account verification and are not available here.</small>
                    </div>

                    <div class="settings-form-actions">
                        <span class="settings-form-status" id="settingsProfileStatus" role="status" aria-live="polite"></span>
                        <button type="submit" class="settings-button settings-button-primary">
                            <i class="ri-save-line" aria-hidden="true"></i>
                            <span data-i18n="settings.profile.save">Save changes</span>
                        </button>
                    </div>
                </form>
            </article>

            <article class="settings-card">
                <header class="settings-card-header">
                    <div>
                        <h2 data-i18n="settings.notifications.title">Notifications</h2>
                        <p data-i18n="settings.notifications.subtitle">Choose what you want to be alerted about.</p>
                    </div>
                </header>

                <div class="settings-options-list">
                    <label class="settings-option-row">
                        <span>
                            <strong data-i18n="settings.notifications.irrigation.title">Irrigation completed</strong>
                            <small data-i18n="settings.notifications.irrigation.description">When a pump cycle completes.</small>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" data-notification="irrigation" checked>
                            <span></span>
                        </span>
                    </label>

                    <label class="settings-option-row">
                        <span>
                            <strong data-i18n="settings.notifications.weather.title">Weather alerts</strong>
                            <small data-i18n="settings.notifications.weather.description">Rain and extreme-temperature alerts.</small>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" data-notification="weather" checked>
                            <span></span>
                        </span>
                    </label>

                    <label class="settings-option-row">
                        <span>
                            <strong data-i18n="settings.notifications.moisture.title">Low soil moisture</strong>
                            <small data-i18n="settings.notifications.moisture.description">When soil moisture falls below the warning threshold.</small>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" data-notification="moisture" checked>
                            <span></span>
                        </span>
                    </label>

                    <label class="settings-option-row">
                        <span>
                            <strong data-i18n="settings.notifications.summary.title">Weekly summary</strong>
                            <small data-i18n="settings.notifications.summary.description">A digest of your recent activity.</small>
                        </span>
                        <span class="settings-switch">
                            <input type="checkbox" data-notification="summary">
                            <span></span>
                        </span>
                    </label>
                </div>
            </article>
        </div>

        <aside class="settings-side-column">
            <article class="settings-card">
                <header class="settings-card-header">
                    <div>
                        <h2 data-i18n="settings.appearance.title">Appearance</h2>
                        <p data-i18n="settings.appearance.subtitle">Choose your interface theme.</p>
                    </div>
                </header>

                <div class="theme-options" role="radiogroup" aria-label="Theme">
                    <button type="button" class="theme-option" data-theme-choice="light" role="radio" aria-checked="false">
                        <i class="ri-sun-line" aria-hidden="true"></i>
                        <span data-i18n="settings.appearance.light">Light</span>
                    </button>
                    <button type="button" class="theme-option" data-theme-choice="dark" role="radio" aria-checked="false">
                        <i class="ri-moon-clear-line" aria-hidden="true"></i>
                        <span data-i18n="settings.appearance.dark">Dark</span>
                    </button>
                </div>
            </article>

            <article class="settings-card">
                <header class="settings-card-header">
                    <div>
                        <h2 data-i18n="settings.account.title">Account</h2>
                        <p data-i18n="settings.account.subtitle">Current account information.</p>
                    </div>
                </header>

                <dl class="settings-account-list">
                    <div>
                        <dt data-i18n="settings.account.username">Username</dt>
                        <dd><?= htmlspecialchars($username) ?></dd>
                    </div>
                    <div>
                        <dt data-i18n="settings.account.type">Account type</dt>
                        <dd><?= htmlspecialchars(ucfirst($accountType)) ?></dd>
                    </div>
                    <div>
                        <dt data-i18n="settings.account.status">Status</dt>
                        <dd class="account-status">
                            <span class="status-dot"></span>
                            <span data-i18n="settings.account.verified">Verified</span>
                        </dd>
                    </div>
                </dl>
            </article>

            <article class="settings-card settings-help-card">
                <i class="ri-shield-check-line" aria-hidden="true"></i>
                <div>
                    <h2 data-i18n="settings.security.title">Security</h2>
                    <p data-i18n="settings.security.description">Keep your password private and use the account recovery flow when needed.</p>
                </div>
            </article>
        </aside>
    </div>
</section>
<?php require_once __DIR__ . '/components/footer.php'; ?>
