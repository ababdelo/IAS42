<?php

declare(strict_types=1);

require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

generateCsrfToken();

$pageTitle = 'analytics';

$extraCss =
    '<link rel="stylesheet" href="/assets/css/analytics.css">';

$extraJs =
    '<script src="https://code.highcharts.com/highcharts.js" defer></script>' .
    '<script src="/assets/js/analytics.js?v=20260914-7" defer></script>';

require_once __DIR__ . '/components/sideBar.php';
?>

<section
    class="ias-page analytics-page"
    aria-labelledby="analytics-page-title">

    <header class="ias-page-header">
        <div>
            <h1
                id="analytics-page-title"
                data-i18n="analytics.title">
                Analytics
            </h1>

            <p data-i18n="analytics.subtitle">
                Environmental and irrigation trends across your farms.
            </p>
        </div>

        <div
            class="analytics-scope"
            id="analyticsScope"
            aria-live="polite">

            <i
                class="ri-focus-3-line"
                aria-hidden="true">
            </i>

            <span data-i18n="analytics.scope.all">
                All farms
            </span>
        </div>
    </header>

    <section
        class="analytics-toolbar"
        aria-label="Analytics filters">

        <div class="analytics-filter">
            <label
                for="analyticsField"
                data-i18n="analytics.filters.field">
                Field
            </label>

            <select
                id="analyticsField"
                autocomplete="off">

                <option
                    value="all"
                    data-i18n="analytics.filters.all_fields">
                    All fields
                </option>
            </select>
        </div>

        <div class="analytics-filter">
            <label
                for="analyticsSector"
                data-i18n="analytics.filters.sector">
                Sector
            </label>

            <select
                id="analyticsSector"
                autocomplete="off">

                <option
                    value="all"
                    data-i18n="analytics.filters.all_sectors">
                    All sectors
                </option>
            </select>
        </div>

        <div class="analytics-filter">
            <label
                for="analyticsNode"
                data-i18n="analytics.filters.node">
                IoT node
            </label>

            <select
                id="analyticsNode"
                autocomplete="off">

                <option
                    value="all"
                    data-i18n="analytics.filters.all_nodes">
                    All nodes
                </option>
            </select>
        </div>

        <div class="analytics-filter">
            <label
                for="analyticsPeriod"
                data-i18n="analytics.filters.period">
                Period
            </label>

            <select
                id="analyticsPeriod"
                autocomplete="off">

                <option
                    value="7"
                    data-i18n="analytics.filters.days_7">
                    Last 7 days
                </option>

                <option
                    value="14"
                    data-i18n="analytics.filters.days_14">
                    Last 14 days
                </option>

                <option
                    value="30"
                    data-i18n="analytics.filters.days_30">
                    Last 30 days
                </option>
            </select>
        </div>
    </section>

    <section
        class="analytics-summary"
        id="analyticsSummary"
        aria-live="polite">
    </section>

    <section
        class="analytics-grid"
        aria-label="Analytics charts">

        <article class="analytics-card">
            <header class="analytics-card-header">
                <div>
                    <h2 data-i18n="analytics.charts.soil.title">
                        Soil moisture &amp; pH
                    </h2>

                    <p data-i18n="analytics.charts.soil.subtitle">
                        Recent telemetry history for moisture and pH
                    </p>
                </div>
            </header>

            <div
                class="chart-area"
                id="soilChart"
                role="img"
                aria-label="Soil moisture and pH chart">
            </div>
        </article>

        <article class="analytics-card">
            <header class="analytics-card-header">
                <div>
                    <h2 data-i18n="analytics.charts.nutrients.title">
                        Nutrient levels (NPK)
                    </h2>

                    <p data-i18n="analytics.charts.nutrients.subtitle">
                        Recent nitrogen, phosphorus and potassium telemetry
                    </p>
                </div>
            </header>

            <div
                class="chart-area"
                id="nutrientChart"
                role="img"
                aria-label="Nutrient levels chart">
            </div>
        </article>

        <article class="analytics-card">
            <header class="analytics-card-header">
                <div>
                    <h2 data-i18n="analytics.charts.environment.title">
                        Temperature &amp; humidity
                    </h2>

                    <p data-i18n="analytics.charts.environment.subtitle">
                        Recent temperature and humidity telemetry
                    </p>
                </div>
            </header>

            <div
                class="chart-area"
                id="environmentChart"
                role="img"
                aria-label="Temperature and humidity chart">
            </div>
        </article>

        <article class="analytics-card">
            <header class="analytics-card-header">
                <div>
                    <h2 data-i18n="analytics.charts.irrigation.title">
                        Irrigation activity
                    </h2>

                    <p data-i18n="analytics.charts.irrigation.subtitle">
                        Estimated pump-active minutes by day
                    </p>
                </div>

                <span
                    class="analytics-card-unit"
                    data-i18n="analytics.units.minutes">
                    min
                </span>
            </header>

            <div
                class="chart-area chart-area-compact"
                id="irrigationChart"
                role="img"
                aria-label="Irrigation activity chart">
            </div>

            <p
                class="analytics-note"
                data-i18n="analytics.notes.irrigation">
                Calculated from pump telemetry intervals; no flow meter data is assumed.
            </p>
        </article>

        <article class="analytics-card">
            <header class="analytics-card-header">
                <div>
                    <h2 data-i18n="analytics.charts.condition.title">
                        Sector condition
                    </h2>

                    <p data-i18n="analytics.charts.condition.subtitle">
                        Rule-based overview from current telemetry
                    </p>
                </div>

                <i
                    class="ri-information-line analytics-info"
                    data-i18n-title="analytics.notes.condition"
                    title="Rule-based indicator using moisture and pH ranges.">
                </i>
            </header>

            <div class="condition-layout">
                <div
                    class="condition-donut-wrap"
                    id="conditionChart"
                    aria-label="Sector condition chart">
                </div>

                <div
                    class="condition-legend"
                    id="conditionLegend">
                </div>
            </div>

            <p
                class="analytics-note"
                data-i18n="analytics.notes.condition">
                This is a simple telemetry heuristic, not an AI disease prediction.
            </p>
        </article>
    </section>

    <div
        class="analytics-empty"
        id="analyticsEmpty"
        hidden>

        <i
            class="ri-bar-chart-box-line"
            aria-hidden="true">
        </i>

        <h2 data-i18n="analytics.empty.title">
            No telemetry available
        </h2>

        <p data-i18n="analytics.empty.description">
            Connect a registered node and wait for telemetry data to appear.
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/components/footer.php'; ?>
