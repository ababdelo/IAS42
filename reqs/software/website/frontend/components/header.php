<?php
require_once __DIR__ . '/../../backend/includes/init.php';
requireAuthentication();

$userId = $_SESSION['user_id'] ?? null;
$user = $userId ? getUserDetails(getDb(), (int) $userId) : null;
$firstname = $user['first_name'] ?? '';
$lastname = $user['last_name'] ?? '';
$fullname = trim($firstname . ' ' . $lastname);
$displayName = $fullname !== '' ? $fullname : ($user['username'] ?? 'Unknown');
$avatarSrc = $user['avatar'] ?? '/assets/imgs/avatars/nouser.webp';

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$currentPage = basename(trim($currentPath, '/'), ".php") ?: 'dashboard';

// Automatically captures the current page for the title and translations
$pageKey = isset($pageTitle) ? strtolower($pageTitle) : $currentPage;

// Verify if the user logged in with "Remember Me" via session or persistent cookie
$isRememberMe = ((isset($_SESSION['remember_me']) && $_SESSION['remember_me'] === true) || isset($_COOKIE['remember_token'])) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n-title="pages.<?= htmlspecialchars($pageKey) ?>.title">
        IAS42 : <?= htmlspecialchars(ucfirst($pageKey)) ?>
    </title>
    <link rel="stylesheet" href="/assets/css/navSideBar.css">
    <link rel="stylesheet" href="/assets/css/boxicons.css">
    <link rel="stylesheet" href="/assets/css/remixicon.css">
    <link rel="stylesheet" href="/assets/css/notifier.css">
    <link rel="stylesheet" href="/assets/css/chatbot.css">
    <link rel="stylesheet" href="/assets/css/fontAwesome.css">
    <?php if (isset($extraCss)) echo $extraCss; ?>

    <!-- Pass backend Remember Me state to Frontend JS -->
    <script>
        window.IAS42RememberMe = <?= $isRememberMe ?>;
    </script>

    <script src="/assets/js/language.js" defer></script>
    <link rel="shortcut icon" href="/assets/imgs/logo/favicon.ico" type="image/x-icon">
</head>

<body>
    <ul class="notifications nvsdbr"></ul>
    <!-- Flash Messages Data -->
    <div id="session-messages"
        data-error="<?= htmlspecialchars($_SESSION['error'] ?? ''); ?>"
        data-success="<?= htmlspecialchars($_SESSION['success'] ?? ''); ?>"
        data-warning="<?= htmlspecialchars($_SESSION['warning'] ?? ''); ?>"
        data-info="<?= htmlspecialchars($_SESSION['info'] ?? ''); ?>">
    </div>
    <?php unset($_SESSION['error'], $_SESSION['success'], $_SESSION['warning'], $_SESSION['info']); ?>

    <header class="header" id="header">
        <div class="header__container">
            <button class="header__toggle" id="header-toggle" type="button" aria-label="Toggle sidebar" aria-controls="sidebar" aria-expanded="false">
                <i class="ri-menu-line"></i>
            </button>

            <!-- Global Search -->
            <div class="search-container">
                <input type="text" placeholder="Search..." data-i18n-placeholder="global.search" name="search" id="globalSearchInput" class="input" autocomplete="off">
                <button type="button" class="search-button" id="search-btn" aria-label="Search">
                    <i class="ri-search-line"></i>
                </button>
            </div>

            <div class="header__right-actions">
                <a href="/notifications" class="header__link" aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i>
                </a>
                <a href="/profile" class="header__user" aria-label="Profile">
                    <img src="<?= htmlspecialchars($avatarSrc); ?>" alt="User Profile Picture">
                </a>
            </div>
        </div>
    </header>
