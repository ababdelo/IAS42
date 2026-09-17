<?php
require_once __DIR__ . '/header.php';
$colorIndex = 1;
?>
<nav class="sidebar is-collapsed" id="sidebar" aria-label="Main navigation">
    <div class="sidebar__container">
        <!-- Logo -->
        <div class="sidebar__logo">
            <div class="sidebar__img"><img src="/assets/imgs/logo/logo.png" alt="IAS42 Logo"></div>
            <div class="sidebar__info">
                <h2>IAS42</h2>
                <!-- Added data-i18n attribute here -->
                <span data-i18n="global.app_subtitle">Your Agricultural Companion</span>
            </div>
        </div>

        <!-- User Info (Mobile/Expanded) -->
        <a href="/profile" class="sidebar__user" aria-label="Profile">
            <img src="<?= htmlspecialchars($avatarSrc); ?>" alt="User Profile Picture">
            <div class="sidebar__user-info">
                <strong><?= htmlspecialchars($displayName); ?></strong>
            </div>
        </a>

        <!-- Navigation Links -->
        <div class="sidebar__content">
            <div style="<?= getNavColor($colorIndex); ?>">
                <h3 class="sidebar__title" data-i18n="global.sidebar.general">GENERAL</h3>
                <div class="sidebar__list">
                    <a href="/dashboard" class="sidebar__link <?= isActive('dashboard', $currentPage) ?>">
                        <i class="ri-dashboard-line"></i><span data-i18n="global.sidebar.dashboard">Dashboard</span>
                    </a>
                    <a href="/fields" class="sidebar__link <?= isActive('fields', $currentPage) ?: isActive('sectors', $currentPage) ?>">
                        <i class="ri-leaf-line"></i><span data-i18n="global.sidebar.fields">Fields</span>
                    </a>
                    <a href="/analytics" class="sidebar__link <?= isActive('analytics', $currentPage) ?>">
                        <i class="ri-bar-chart-line"></i><span data-i18n="global.sidebar.analytics">Analytics</span>
                    </a>
                    <a href="/history" class="sidebar__link <?= isActive('history', $currentPage) ?>">
                        <i class="ri-history-line"></i><span data-i18n="global.sidebar.history">History</span>
                    </a>
                    <a href="/notifications" class="sidebar__link notif <?= isActive('notifications', $currentPage) ?>">
                        <i class="fa-regular fa-bell"></i><span data-i18n="global.sidebar.notifications">Notifications</span>
                    </a>
                </div>
            </div>

            <div style="<?= getNavColor($colorIndex); ?>">
                <h3 class="sidebar__title" data-i18n="global.sidebar.preferences">PREFERENCES</h3>
                <div class="sidebar__list">
                    <a href="/profile" class="sidebar__link <?= isActive('profile', $currentPage) ?>">
                        <i class="ri-account-circle-line"></i><span data-i18n="global.sidebar.profile">Profile</span>
                    </a>
                    <a href="/settings" class="sidebar__link <?= isActive('settings', $currentPage) ?>">
                        <i class="ri-settings-3-line"></i><span data-i18n="global.sidebar.settings">Settings</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Sidebar Actions -->
        <div class="sidebar__actions">
            <!-- Language Switcher -->
            <div class="sidebar__language">
                <button type="button" class="sidebar__link" id="lang-toggle" aria-label="Language" aria-haspopup="true" aria-expanded="false" aria-controls="language-menu">
                    <i class="fa-solid fa-language"></i>
                    <span class="lang-trigger-label" data-lang-label="trigger">Language</span>
                </button>

                <div class="language-menu" id="language-menu" role="menu" hidden>
                    <button type="button" role="menuitemradio" data-lang="en" aria-checked="true">
                        <img src="/assets/imgs/flags/en.webp" alt="English Flag">
                        <span class="lang-option-label" data-lang-label="en">English</span>
                        <span class="lang-option-code">EN</span>
                    </button>
                    <button type="button" role="menuitemradio" data-lang="fr" aria-checked="false">
                        <img src="/assets/imgs/flags/fr.webp" alt="French Flag">
                        <span class="lang-option-label" data-lang-label="fr">Français</span>
                        <span class="lang-option-code">FR</span>
                    </button>
                    <button type="button" role="menuitemradio" data-lang="es" aria-checked="false">
                        <img src="/assets/imgs/flags/sp.webp" alt="Spanish Flag">
                        <span class="lang-option-label" data-lang-label="es">Español</span>
                        <span class="lang-option-code">ES</span>
                    </button>
                    <button type="button" role="menuitemradio" data-lang="ar" aria-checked="false">
                        <img src="/assets/imgs/flags/ar.webp" alt="Arabic Flag">
                        <span class="lang-option-label" data-lang-label="ar">العربية</span>
                        <span class="lang-option-code">AR</span>
                    </button>
                </div>
            </div>

            <!-- Theme Toggle -->
            <button type="button" aria-label="Toggle theme">
                <i class="ri-moon-clear-line sidebar__link sidebar__theme" id="theme-button"><span data-i18n="global.sidebar.theme">Theme</span></i>
            </button>

            <!-- Logout -->
            <a href="/auth/logout" class="sidebar__link">
                <i class="fa-solid fa-arrow-right-from-bracket"></i><span data-i18n="global.sidebar.logout">Log Out</span>
            </a>
        </div>
    </div>
</nav>

<div class="sidebar__overlay" id="sidebar-overlay" hidden></div>
<main class="main container" id="main">
