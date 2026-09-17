<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$pageTitle = 'history';
$extraCss = '<link rel="stylesheet" href="/assets/css/history.css">';
$extraJs = '<script src="/assets/js/history.js" defer></script>';

require_once __DIR__ . '/components/sideBar.php';
?>
<section class="ias-page history-page" aria-labelledby="history-page-title">
    <header class="history-page-header">
        <div class="history-heading">
            <h1 id="history-page-title" data-i18n="history.title">Activity History</h1>
        </div>
    </header>

    <section class="history-toolbar" aria-label="History filters">
        <div class="history-toolbar-header">
            <div>
                <h2 data-i18n="history.filters.title">Filter activity</h2>
                <p data-i18n="history.filters.subtitle">Narrow your history by field, event type, or time period.</p>
            </div>
            <span class="history-toolbar-status"><i class="ri-filter-3-line" aria-hidden="true"></i><span data-i18n="history.filters.status">Live filters</span></span>
        </div>

        <div class="history-filter-grid">
            <div class="history-filter">
                <label for="historyField" data-i18n="history.filters.field">Field</label>
                <div class="history-select-wrap">
                    <i class="ri-leaf-line" aria-hidden="true"></i>
                    <select id="historyField">
                        <option value="all" data-i18n="history.filters.all_fields">All fields</option>
                    </select>
                    <i class="ri-arrow-down-s-line history-select-chevron" aria-hidden="true"></i>
                </div>
            </div>

            <div class="history-filter">
                <label for="historyType" data-i18n="history.filters.event_type">Event type</label>
                <div class="history-select-wrap">
                    <i class="ri-pulse-line" aria-hidden="true"></i>
                    <select id="historyType">
                        <option value="all" data-i18n="history.filters.all_types">All event types</option>
                        <option value="irrigation" data-i18n="history.types.irrigation">Irrigation</option>
                        <option value="alert" data-i18n="history.types.alert">Alert</option>
                        <option value="temperature" data-i18n="history.types.temperature">Temperature</option>
                        <option value="update" data-i18n="history.types.update">Update</option>
                    </select>
                    <i class="ri-arrow-down-s-line history-select-chevron" aria-hidden="true"></i>
                </div>
            </div>

            <div class="history-filter">
                <label for="historyPeriod" data-i18n="history.filters.period">Period</label>
                <div class="history-select-wrap">
                    <i class="ri-calendar-line" aria-hidden="true"></i>
                    <select id="historyPeriod">
                        <option value="7" data-i18n="history.filters.days_7">Last 7 days</option>
                        <option value="14" data-i18n="history.filters.days_14">Last 14 days</option>
                        <option value="30" data-i18n="history.filters.days_30">Last 30 days</option>
                    </select>
                    <i class="ri-arrow-down-s-line history-select-chevron" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="history-results-header">
        <div>
            <h2 data-i18n="history.timeline.title">Recent activity</h2>
        </div>
        <div class="history-count" aria-live="polite">
            <strong id="historyCount">0</strong>
            <span data-i18n="history.count_label">events</span>
            <span class="history-results-dot" aria-hidden="true"></span>
        </div>
    </section>

    <section id="historyContainer" class="history-container" aria-live="polite" aria-busy="true"></section>

    <div class="history-empty" id="historyEmpty" hidden>
        <div class="history-empty-icon"><i class="ri-inbox-archive-line" aria-hidden="true"></i></div>
        <h2 data-i18n="history.empty.title">No activity found</h2>
        <p data-i18n="history.empty.description">There are no matching events for the selected filters.</p>
    </div>
</section>
<?php require_once __DIR__ . '/components/footer.php'; ?>
