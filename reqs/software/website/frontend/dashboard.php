<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();
$pageTitle = 'dashboard';
$extraCss = '<link rel="stylesheet" href="/assets/css/dashboard.css">';
$extraJs  = '<script src="/assets/js/dashboard.js" defer></script>';
require_once __DIR__ . "/components/sideBar.php";
?>
<div class="dashboard-wrapper">
    <div class="dashboard-layout">
        <div class="main-column">
            <!-- Weather Card -->
            <article class="organic-card weather-card">
                <div class="card__header">
                    <div>
                        <h2 class="card__title" data-i18n="dashboard.weather.title">Local Field Weather</h2>
                        <span class="card__subtitle" data-i18n="dashboard.weather.subtitle">Real-time local sensor telemetry</span>
                    </div>
                    <div class="live-status"><span class="pulse-dot"></span> <span data-i18n="dashboard.weather.live">Live</span></div>
                </div>
                <div class="weather-compact-core">
                    <div class="weather-left">
                        <i class="fa-solid fa-sun weather-icon-large weather-icon--clear-day" id="weatherIcon" aria-hidden="true"></i>
                        <div class="weather-temp-cond">
                            <div class="temp-wrapper" id="temperatureStatus">
                                <span class="temp" id="airTemp">--</span><span class="unit" data-i18n="dashboard.units.celsius">°C</span>
                            </div>
                            <h3 id="weatherConditionLabel" data-i18n="dashboard.weather.conditions.clear">Clear</h3>
                        </div>
                    </div>
                    <div class="weather-right">
                        <div class="weather-datetime">
                            <strong id="currentDate" class="weather-datetime-value">
                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                <span class="weather-datetime-text">--</span>
                            </strong>
                            <span id="currentTime" class="weather-datetime-value">
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                <span class="weather-datetime-text">--:--</span>
                            </span>
                        </div>
                        <div class="weather-minor-metrics">
                            <div class="metric"><i class="fa-solid fa-wind" id="windStatusIcon" aria-hidden="true"></i> <span id="windSpeed">--</span></div>
                            <div class="metric"><i class="fa-solid fa-droplet" id="humidityStatusIcon" aria-hidden="true"></i> <span id="airHumidity">--%</span></div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Latest verified MQTT telemetry is supplied by the server-side bridge. -->
            <article class="organic-card fields-card">
                <div class="card__header">
                    <div>
                        <h2 class="card__title" data-i18n="dashboard.fields.title">Cultivated Sectors</h2>
                        <span class="card__subtitle" data-i18n="dashboard.fields.subtitle">Active sensor nodes & soil metrics</span>
                    </div>
                    <a href="/fields" class="btn-text" style="text-decoration:none;"><span data-i18n="dashboard.fields.manage">Manage</span> <i class="ri-arrow-right-line"></i></a>
                </div>
                <!-- JS WILL INJECT DATA HERE -->
                <div class="sector-cards-grid" id="dynamicSectorsGrid"></div>
            </article>
        </div>

        <div class="side-column">
            <!-- Water Management -->
            <article class="organic-card water-card">
                <div class="card__header align-center">
                    <h2 class="card__title" data-i18n="dashboard.water.title">Water Reserve</h2>
                    <div class="pump-badge">
                        <span class="pulse"></span> <span data-i18n="dashboard.water.idle">Idle</span>
                    </div>
                </div>
                <div class="droplet-container">
                    <div class="circular-tank">
                        <div class="wave-fill" id="tankFillBar" style="top: 100%;"></div>
                        <div class="tank-value">
                            <h3 id="tankPercentLabel">--%</h3>
                            <span>Reserve</span>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Live Activity -->
            <article class="organic-card timeline-card">
                <div class="card__header">
                    <h2 class="card__title" data-i18n="dashboard.events.title">Live Activity</h2>
                </div>
                <div class="timeline-scroll-area">
                    <div class="playful-timeline" id="liveActivityTimeline"></div>
                </div>
            </article>
        </div>
    </div>
</div>
<?php require_once __DIR__ . "/components/footer.php"; ?>
