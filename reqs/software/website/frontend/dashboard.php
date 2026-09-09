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
        <!-- MAIN COLUMN -->
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
                        <i class="ri-sun-fill weather-icon-large" id="weatherIcon"></i>
                        <div class="weather-temp-cond">
                            <div class="temp-wrapper">
                                <span class="temp" id="airTemp">27</span><span class="unit" data-i18n="dashboard.units.celsius">°C</span>
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
                            <div class="metric"><i class="ri-windy-line"></i> <span id="windSpeed">12 km/h</span></div>
                            <div class="metric"><i class="ri-drop-line"></i> <span id="airHumidity">64%</span></div>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Cultivated Sectors (MVP 3 Crops) -->
            <article class="organic-card fields-card">
                <div class="card__header">
                    <div>
                        <h2 class="card__title" data-i18n="dashboard.fields.title">Cultivated Sectors</h2>
                        <span class="card__subtitle" data-i18n="dashboard.fields.subtitle">Active sensor nodes & soil metrics</span>
                    </div>
                    <a href="/fields" class="btn-text" style="text-decoration:none;"><span data-i18n="dashboard.fields.manage">Manage</span> <i class="ri-arrow-right-line"></i></a>
                </div>

                <div class="sector-cards-grid">
                    <!-- Sector 1 (Tomato) -->
                    <div class="sector-box">
                        <div class="sector-box-header">
                            <div class="sector-icon">
                                <img src="/assets/imgs/plants/tomato.webp" alt="Tomato" onerror="this.src='/assets/imgs/plants/no image.webp'">
                            </div>
                            <div class="sector-title">
                                <h4 data-i18n="dashboard.crops.tomato">Tomato</h4>
                                <span>Sector Alpha (Node-01)</span>
                            </div>
                            <div class="sector-status status-optimal">
                                <i class="fa-solid fa-circle-check"></i> <span data-i18n="dashboard.fields.status_healthy">Healthy</span>
                            </div>
                        </div>
                        <div class="sector-box-metrics">
                            <div class="metric-block color-moisture">
                                <span class="label"><i class="fa-solid fa-droplet"></i> <span data-i18n="dashboard.fields.soil_moisture">Moisture</span></span>
                                <strong class="value optimal">58%</strong>
                            </div>
                            <div class="metric-block color-ph">
                                <span class="label"><i class="fa-solid fa-flask"></i> <span data-i18n="dashboard.fields.ph_level">pH Level</span></span>
                                <strong class="value">6.5</strong>
                            </div>
                            <div class="metric-block color-pump">
                                <span class="label"><i class="fa-solid fa-faucet-drip"></i> <span data-i18n="dashboard.fields.pump_status">Pump</span></span>
                                <strong class="value stopped" data-i18n="dashboard.fields.pump_stopped">Stopped</strong>
                            </div>
                            <div class="metric-block color-n">
                                <span class="label"><i class="fa-solid fa-n"></i> <span data-i18n="dashboard.fields.nitrogen">Nitrogen</span></span>
                                <strong class="value warning">42 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-p">
                                <span class="label"><i class="fa-solid fa-p"></i> <span data-i18n="dashboard.fields.phosphorus">Phosphorus</span></span>
                                <strong class="value">15 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-k">
                                <span class="label"><i class="fa-solid fa-k"></i> <span data-i18n="dashboard.fields.potassium">Potassium</span></span>
                                <strong class="value optimal">120 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                        </div>
                        <!-- Refined Analytical Output -->
                        <div class="sector-ai-insight">
                            <div class="ai-badge status-good">
                                <i class="ri-bug-line"></i> <strong data-i18n="dashboard.ai.healthy">Optimal Health</strong>
                            </div>
                            <div class="ai-badge status-info">
                                <i class="ri-bar-chart-grouped-line"></i> <span><strong data-i18n="dashboard.ai.water">Computed Vol.</strong>: 4.2L</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sector 2 (Corn) -->
                    <div class="sector-box">
                        <div class="sector-box-header">
                            <div class="sector-icon">
                                <img src="/assets/imgs/plants/corn.webp" alt="Corn" onerror="this.src='/assets/imgs/plants/no image.webp'">
                            </div>
                            <div class="sector-title">
                                <h4 data-i18n="dashboard.crops.corn">Corn</h4>
                                <span>Sector Beta (Node-02)</span>
                            </div>
                            <div class="sector-status status-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i> <span data-i18n="dashboard.fields.status_attention">Attention</span>
                            </div>
                        </div>
                        <div class="sector-box-metrics">
                            <div class="metric-block color-moisture">
                                <span class="label"><i class="fa-solid fa-droplet"></i> <span data-i18n="dashboard.fields.soil_moisture">Moisture</span></span>
                                <strong class="value warning">28%</strong>
                            </div>
                            <div class="metric-block color-ph">
                                <span class="label"><i class="fa-solid fa-flask"></i> <span data-i18n="dashboard.fields.ph_level">pH Level</span></span>
                                <strong class="value">6.8</strong>
                            </div>
                            <div class="metric-block color-pump">
                                <span class="label"><i class="fa-solid fa-faucet-drip"></i> <span data-i18n="dashboard.fields.pump_status">Pump</span></span>
                                <strong class="value stopped" data-i18n="dashboard.fields.pump_stopped">Stopped</strong>
                            </div>
                            <div class="metric-block color-n">
                                <span class="label"><i class="fa-solid fa-n"></i><span data-i18n="dashboard.fields.nitrogen">Nitrogen</span></span>
                                <strong class="value optimal">85 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-p">
                                <span class="label"><i class="fa-solid fa-p"></i> <span data-i18n="dashboard.fields.phosphorus">Phosphorus</span></span>
                                <strong class="value warning">8 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-k">
                                <span class="label"><i class="fa-solid fa-k"></i> <span data-i18n="dashboard.fields.potassium">Potassium</span></span>
                                <strong class="value">110 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                        </div>
                        <!-- Refined Analytical Output -->
                        <div class="sector-ai-insight">
                            <div class="ai-badge status-bad">
                                <i class="ri-bug-line"></i> <strong data-i18n="dashboard.ai.blight">Early Blight Risk</strong>
                            </div>
                            <div class="ai-badge status-info">
                                <i class="ri-bar-chart-grouped-line"></i> <span><strong data-i18n="dashboard.ai.water">Computed Vol.</strong>: 12.5L</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sector 3 (Potato) -->
                    <div class="sector-box" style="grid-column: 1 / -1;">
                        <div class="sector-box-header">
                            <div class="sector-icon">
                                <img src="/assets/imgs/plants/potato.webp" alt="Potato" onerror="this.src='/assets/imgs/plants/no image.webp'">
                            </div>
                            <div class="sector-title">
                                <h4 data-i18n="dashboard.crops.potato">Potato</h4>
                                <span>Sector Gamma (Node-03)</span>
                            </div>
                            <div class="sector-status status-optimal">
                                <i class="fa-solid fa-circle-check"></i> <span data-i18n="dashboard.fields.status_healthy">Healthy</span>
                            </div>
                        </div>
                        <div class="sector-box-metrics">
                            <div class="metric-block color-moisture">
                                <span class="label"><i class="fa-solid fa-droplet"></i> <span data-i18n="dashboard.fields.soil_moisture">Moisture</span></span>
                                <strong class="value optimal">65%</strong>
                            </div>
                            <div class="metric-block color-ph">
                                <span class="label"><i class="fa-solid fa-flask"></i> <span data-i18n="dashboard.fields.ph_level">pH Level</span></span>
                                <strong class="value">6.0</strong>
                            </div>
                            <div class="metric-block color-pump">
                                <span class="label"><i class="fa-solid fa-faucet-drip"></i> <span data-i18n="dashboard.fields.pump_status">Pump</span></span>
                                <strong class="value stopped" data-i18n="dashboard.fields.pump_stopped">Stopped</strong>
                            </div>
                            <div class="metric-block color-n">
                                <span class="label"><i class="fa-solid fa-n"></i> <span data-i18n="dashboard.fields.nitrogen">Nitrogen</span></span>
                                <strong class="value">60 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-p">
                                <span class="label"><i class="fa-solid fa-p"></i> <span data-i18n="dashboard.fields.phosphorus">Phosphorus</span></span>
                                <strong class="value">25 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                            <div class="metric-block color-k">
                                <span class="label"><i class="fa-solid fa-k"></i> <span data-i18n="dashboard.fields.potassium">Potassium</span></span>
                                <strong class="value optimal">150 <small data-i18n="dashboard.units.mg_kg">mg/kg</small></strong>
                            </div>
                        </div>
                        <!-- Refined Analytical Output -->
                        <div class="sector-ai-insight">
                            <div class="ai-badge status-good">
                                <i class="ri-bug-line"></i> <strong data-i18n="dashboard.ai.healthy">Optimal Health</strong>
                            </div>
                            <div class="ai-badge status-info">
                                <i class="ri-bar-chart-grouped-line"></i> <span><strong data-i18n="dashboard.ai.water">Computed Vol.</strong>: 2.1L</span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <!-- SIDE COLUMN -->
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
                        <div class="wave-fill" id="tankFillBar" style="top: 22%;"></div>
                        <div class="tank-value">
                            <h3 id="tankPercentLabel">78%</h3>
                            <span>1,560 <span data-i18n="dashboard.units.liters">L</span></span>
                        </div>
                    </div>
                </div>
                <div class="water-stats-grid">
                    <div class="mini-stat">
                        <span data-i18n="dashboard.water.daily_usage">Daily Usage</span>
                        <strong>340 <span data-i18n="dashboard.units.liters">L</span></strong>
                    </div>
                    <div class="mini-stat">
                        <span data-i18n="dashboard.water.cycles">Cycles</span>
                        <strong>4 <span data-i18n="dashboard.water.today">Today</span></strong>
                    </div>
                </div>
            </article>

            <!-- Activity Timeline -->
            <article class="organic-card timeline-card">
                <div class="card__header">
                    <h2 class="card__title" data-i18n="dashboard.events.title">Live Activity</h2>
                </div>
                <div class="timeline-scroll-area">
                    <div class="playful-timeline">
                        <div class="time-node">
                            <div class="node-icon optimal"><i class="ri-water-flash-line"></i></div>
                            <div class="node-data">
                                <strong data-i18n="dashboard.events.item1.title">Irrigation Complete</strong>
                                <p data-i18n="dashboard.events.item1.desc">Sector Alpha delivered 45L.</p>
                                <span>12 <span data-i18n="dashboard.time.mins_ago">mins ago</span></span>
                            </div>
                        </div>
                        <div class="time-node">
                            <div class="node-icon warning"><i class="ri-rainy-line"></i></div>
                            <div class="node-data">
                                <strong data-i18n="dashboard.events.item2.title">Rain Detected</strong>
                                <p data-i18n="dashboard.events.item2.desc">Automated irrigation paused.</p>
                                <span>1 <span data-i18n="dashboard.time.hr_ago">hr ago</span></span>
                            </div>
                        </div>
                        <div class="time-node">
                            <div class="node-icon neutral"><i class="ri-sensor-line"></i></div>
                            <div class="node-data">
                                <strong data-i18n="dashboard.events.item3.title">Telemetry Online</strong>
                                <p data-i18n="dashboard.events.item3.desc">Node-01 MQTT verified.</p>
                                <span>3 <span data-i18n="dashboard.time.hrs_ago">hrs ago</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
<?php require_once __DIR__ . "/components/footer.php"; ?>
