/**
 * IAS42 Dashboard - Database-backed telemetry renderer.
 *
 * MQTT is intentionally handled by the server-side bridge.
 * This page only reads the latest verified data from telemetry.php.
 */
(() => {
  'use strict';

  const gridContainer = document.getElementById('dynamicSectorsGrid');
  const activityContainer = document.getElementById('liveActivityTimeline');
  const POLL_INTERVAL = 5000;
  let lastTelemetry = [];

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const numberOrDash = (value, digits = 1) => {
    if (value === null || value === undefined || value === '') return '--';
    const number = Number(value);
    if (!Number.isFinite(number)) return '--';
    return number.toFixed(digits).replace(/\.0+$/, '');
  };

  async function fetchTelemetry() {
    try {
      const response = await fetch('/api/telemetry', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();
      if (result.status !== 'success') {
        throw new Error(result.message || 'Telemetry API returned an error.');
      }

      lastTelemetry = Array.isArray(result.data) ? result.data : [];
      renderSectors(lastTelemetry);
      renderActivities(lastTelemetry);
      updateGlobalWeather(lastTelemetry);
      updateSystemState(lastTelemetry);
    } catch (error) {
      console.error('Failed to fetch telemetry:', error);
      updateSystemState([], false);
    }
  }

  function renderSectors(sectors) {
    if (!gridContainer) return;

    if (sectors.length === 0) {
      gridContainer.innerHTML = `
        <p style="grid-column: 1 / -1; text-align: center; color: var(--text-color);">
          No sectors are assigned to this account yet.
        </p>`;
      return;
    }

    gridContainer.innerHTML = sectors.map((sector, index) => {
      const temp = numberOrDash(sector.air_temperature);
      const hum = numberOrDash(sector.air_humidity);
      const moist = numberOrDash(sector.soil_moisture);
      const ph = numberOrDash(sector.soil_ph);
      const n = numberOrDash(sector.nitrogen);
      const p = numberOrDash(sector.phosphorus);
      const k = numberOrDash(sector.potassium);
      const wind = numberOrDash(sector.wind_speed);
      const water = numberOrDash(sector.water_level);
      const brightness = numberOrDash(sector.brightness, 0);
      const pumpOn = sector.pump_status === true || sector.pump_status === 1 || sector.pump_status === '1';
      const isOnline = sector.node_status === 'online';
      const isMaintenance = sector.node_status === 'maintenance';

      const imgName = sector.crop_name
        ? String(sector.crop_name).toLowerCase().trim().replace(/\s+/g, '-')
        : 'no-image';

      const statusClass = isMaintenance
        ? 'status-warning'
        : (isOnline ? 'status-optimal' : 'status-warning');

      const statusLabel = isMaintenance
        ? 'Maintenance'
        : (isOnline ? 'Online' : 'Offline');

      const gridStyle = (index === 2 && sectors.length === 3)
        ? 'style="grid-column: 1 / -1;"'
        : '';

      return `
        <div class="sector-box" ${gridStyle}>
          <div class="sector-box-header">
            <div class="sector-icon">
              <img
                src="/assets/imgs/plants/${escapeHtml(imgName)}.webp"
                alt="${escapeHtml(sector.crop_name || 'Crop')}"
                onerror="this.src='/assets/imgs/plants/no image.webp'"
              >
            </div>
            <div class="sector-title">
              <h4>${escapeHtml(sector.crop_name || 'Unknown Crop')}</h4>
              <span>${escapeHtml(sector.sector_name || 'Unknown Sector')} (${escapeHtml(sector.node_id || 'No Node')})</span>
            </div>
            <div class="sector-status ${statusClass}">
              <i class="fa-solid ${isOnline ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i>
              <span>${statusLabel}</span>
            </div>
          </div>

          <div class="sector-box-metrics">
            <div class="metric-block color-moisture">
              <span class="label"><i class="fa-solid fa-droplet"></i> Moisture</span>
              <strong class="value ${Number(sector.soil_moisture) < 30 ? 'warning' : 'optimal'}">${moist}%</strong>
            </div>
            <div class="metric-block color-ph">
              <span class="label"><i class="fa-solid fa-flask"></i> pH Level</span>
              <strong class="value">${ph}</strong>
            </div>
            <div class="metric-block color-pump">
              <span class="label"><i class="fa-solid fa-faucet-drip"></i> Pump</span>
              <strong class="value ${pumpOn ? 'optimal' : 'stopped'}">${pumpOn ? 'ON' : 'OFF'}</strong>
            </div>
            <div class="metric-block color-n">
              <span class="label"><i class="fa-solid fa-n"></i> Nitrogen</span>
              <strong class="value">${n} <small>mg/kg</small></strong>
            </div>
            <div class="metric-block color-p">
              <span class="label"><i class="fa-solid fa-p"></i> Phosphorus</span>
              <strong class="value">${p} <small>mg/kg</small></strong>
            </div>
            <div class="metric-block color-k">
              <span class="label"><i class="fa-solid fa-k"></i> Potassium</span>
              <strong class="value">${k} <small>mg/kg</small></strong>
            </div>
          </div>
        </div>`;
    }).join('');
  }

  function renderActivities(sectors) {
    if (!activityContainer) return;

    if (sectors.length === 0) {
      activityContainer.innerHTML = '<p class="timeline-empty">No activity yet.</p>';
      return;
    }

    const activities = sectors.map((sector) => {
      const sectorName = sector.sector_name || 'Unknown sector';
      const nodeName = sector.node_id || 'Unknown node';
      const hasTelemetry = Boolean(sector.telemetry_at);

      if (sector.pump_status) {
        return {
          icon: 'ri-drop-line',
          state: 'optimal',
          title: 'Irrigation active',
          description: `${sectorName} is being watered.`,
          timestamp: sector.telemetry_at
        };
      }

      if (sector.is_raining) {
        return {
          icon: 'ri-rainy-line',
          state: 'warning',
          title: 'Rain detected',
          description: `${sectorName} is receiving rain.`,
          timestamp: sector.telemetry_at
        };
      }

      if (sector.node_status === 'maintenance') {
        return {
          icon: 'ri-tools-line',
          state: 'warning',
          title: 'Node maintenance',
          description: `${nodeName} needs maintenance.`,
          timestamp: sector.last_seen
        };
      }

      if (sector.node_status === 'online' && hasTelemetry) {
        return {
          icon: 'ri-wifi-line',
          state: 'neutral',
          title: 'Telemetry online',
          description: `${nodeName} is reporting from ${sectorName}.`,
          timestamp: sector.telemetry_at
        };
      }

      return {
        icon: 'ri-wifi-off-line',
        state: 'warning',
        title: 'Node offline',
        description: `${nodeName} has no recent telemetry.`,
        timestamp: sector.last_seen
      };
    }).sort((first, second) => dateValue(second.timestamp) - dateValue(first.timestamp));

    activityContainer.innerHTML = activities.map((activity) => `
      <div class="time-node">
        <div class="node-icon ${activity.state}"><i class="${activity.icon}" aria-hidden="true"></i></div>
        <div class="node-data">
          <strong>${escapeHtml(activity.title)}</strong>
          <p>${escapeHtml(activity.description)}</p>
          <span>${escapeHtml(formatRelativeTime(activity.timestamp))}</span>
        </div>
      </div>`).join('');
  }

  function dateValue(value) {
    const parsed = Date.parse(value || '');
    return Number.isNaN(parsed) ? 0 : parsed;
  }

  function formatRelativeTime(value) {
    const timestamp = dateValue(value);
    if (!timestamp) return 'Time unavailable';

    const elapsedMinutes = Math.max(0, Math.floor((Date.now() - timestamp) / 60000));
    if (elapsedMinutes < 1) return 'Just now';
    if (elapsedMinutes < 60) return `${elapsedMinutes} min${elapsedMinutes === 1 ? '' : 's'} ago`;

    const elapsedHours = Math.floor(elapsedMinutes / 60);
    if (elapsedHours < 24) return `${elapsedHours} hr${elapsedHours === 1 ? '' : 's'} ago`;

    const elapsedDays = Math.floor(elapsedHours / 24);
    return `${elapsedDays} day${elapsedDays === 1 ? '' : 's'} ago`;
  }

  function updateGlobalWeather(sectors) {
    if (sectors.length === 0) {
      setText('airTemp', '--');
      setText('airHumidity', '--%');
      setText('windSpeed', '--');
      setText('tankPercentLabel', '--%');
      setText('weatherConditionLabel', 'No telemetry');
      setWeatherIcon('fa-solid fa-circle-question', 'unknown');
      setMetricState('windStatusIcon', 'unknown');
      setMetricState('humidityStatusIcon', 'unknown');
      setTemperatureState('unknown');
      return;
    }

    const node = sectors.find((item) => item.node_status === 'online') || sectors[0];
    const temperature = Number(node.air_temperature);
    const humidity = Number(node.air_humidity);
    const windSpeed = Number(node.wind_speed);
    const brightness = Number(node.brightness);

    setText('airTemp', numberOrDash(node.air_temperature, 0));
    setText('airHumidity', `${numberOrDash(node.air_humidity, 0)}%`);
    setText('windSpeed', numberOrDash(node.wind_speed));

    const conditionLabel = document.getElementById('weatherConditionLabel');
    const weather = classifyWeather({
      temperature,
      humidity,
      windSpeed,
      raining: Boolean(node.is_raining),
      brightness,
      hour: new Date().getHours()
    });

    setWeatherIcon(weather.icon, weather.iconState);
    setTemperatureState(weather.temperatureState);
    setMetricState('windStatusIcon', weather.windState);
    setMetricState('humidityStatusIcon', weather.humidityState);
    if (conditionLabel) conditionLabel.textContent = weather.label;

    const tankLabel = document.getElementById('tankPercentLabel');
    const tankBar = document.getElementById('tankFillBar');
    const pumpBadge = document.querySelector('.pump-badge span:last-child');

    if (node.water_level !== null && node.water_level !== undefined && Number.isFinite(Number(node.water_level))) {
      const distance = Number(node.water_level);
      const percent = Math.max(0, Math.min(100, 100 - distance));
      if (tankLabel) tankLabel.textContent = `${Math.round(percent)}%`;
      if (tankBar) tankBar.style.top = `${100 - percent}%`;
    } else {
      if (tankLabel) tankLabel.textContent = '--%';
      if (tankBar) tankBar.style.top = '100%';
    }

    if (pumpBadge) {
      const pumpOn = node.pump_status === true || node.pump_status === 1 || node.pump_status === '1';
      pumpBadge.textContent = pumpOn ? 'Running' : 'Idle';
    }
  }

  function classifyWeather({ temperature, humidity, windSpeed, raining, brightness, hour }) {
    const isDay = Number.isFinite(brightness)
      ? brightness >= 50
      : (hour >= 6 && hour < 18);
    const freezing = Number.isFinite(temperature) && temperature <= 0;
    const cold = Number.isFinite(temperature) && temperature <= 8;
    const hot = Number.isFinite(temperature) && temperature >= 30;
    const extremeHeat = Number.isFinite(temperature) && temperature >= 38;
    const highWind = Number.isFinite(windSpeed) && windSpeed >= 40;
    const mediumWind = Number.isFinite(windSpeed) && windSpeed >= 20;
    const windState = highWind ? 'danger' : (mediumWind ? 'warning' : 'calm');
    const humidityState = Number.isFinite(humidity)
      ? (humidity >= 80 ? 'humid' : (humidity <= 30 ? 'dry' : 'balanced'))
      : 'unknown';

    if (raining && highWind) {
      return { icon: 'fa-solid fa-cloud-bolt', iconState: 'storm', temperatureState: getTemperatureState(temperature), label: 'Storm risk', windState, humidityState };
    }
    if (freezing) {
      return { icon: 'fa-solid fa-snowflake', iconState: 'freezing', temperatureState: 'freezing', label: raining ? 'Freezing rain' : 'Freezing cold', windState, humidityState };
    }
    if (raining) {
      return {
        icon: isDay ? 'fa-solid fa-cloud-sun-rain' : 'fa-solid fa-cloud-moon-rain',
        iconState: 'rain',
        temperatureState: getTemperatureState(temperature),
        label: 'Rain detected',
        windState,
        humidityState
      };
    }
    if (extremeHeat) {
      return { icon: 'fa-solid fa-temperature-high', iconState: 'extreme-heat', temperatureState: 'extreme-heat', label: 'Extreme heat', windState, humidityState };
    }
    if (hot) {
      return { icon: 'fa-solid fa-sun', iconState: 'hot', temperatureState: 'hot', label: isDay ? 'Hot and clear' : 'Warm night', windState, humidityState };
    }
    if (cold) {
      return { icon: 'fa-solid fa-snowflake', iconState: 'cold', temperatureState: 'cold', label: 'Cold and clear', windState, humidityState };
    }
    if (highWind) {
      return { icon: 'fa-solid fa-tornado', iconState: 'danger', temperatureState: getTemperatureState(temperature), label: 'Dangerous wind', windState, humidityState };
    }
    if (mediumWind) {
      return { icon: 'fa-solid fa-wind', iconState: 'wind', temperatureState: getTemperatureState(temperature), label: 'Windy', windState, humidityState };
    }

    return {
      icon: isDay ? 'fa-solid fa-sun' : 'fa-solid fa-moon',
      iconState: isDay ? 'clear-day' : 'clear-night',
      temperatureState: getTemperatureState(temperature),
      label: humidityState === 'humid' ? 'Clear and humid' : (humidityState === 'dry' ? 'Clear and dry' : 'Clear'),
      windState,
      humidityState
    };
  }

  function getTemperatureState(temperature) {
    if (!Number.isFinite(temperature)) return 'unknown';
    if (temperature <= 0) return 'freezing';
    if (temperature <= 8) return 'cold';
    if (temperature >= 38) return 'extreme-heat';
    if (temperature >= 30) return 'hot';
    return 'comfortable';
  }

  function setWeatherIcon(iconClass, state) {
    const weatherIcon = document.getElementById('weatherIcon');
    if (!weatherIcon) return;
    weatherIcon.className = `${iconClass} weather-icon-large weather-icon--${state}`;
  }

  function setMetricState(id, state) {
    const metricIcon = document.getElementById(id);
    if (metricIcon) metricIcon.className = `fa-solid ${id === 'humidityStatusIcon' ? 'fa-droplet' : 'fa-wind'} metric-icon--${state}`;
  }

  function setTemperatureState(state) {
    const temperature = document.getElementById('temperatureStatus');
    if (temperature) temperature.className = `temp-wrapper temperature--${state}`;
  }

  function updateSystemState(sectors, requestSucceeded = true) {
    const online = sectors.some((sector) => sector.node_status === 'online');
    const liveDot = document.querySelector('.live-status .pulse-dot');
    const liveText = document.querySelector('.live-status span:last-child');

    if (!requestSucceeded) {
      if (liveText) liveText.textContent = 'Offline';
      return;
    }

    if (liveText) liveText.textContent = online ? 'Live' : 'Waiting for node';
    if (liveDot) liveDot.style.opacity = online ? '1' : '0.45';
  }

  function setText(id, text) {
    const element = document.getElementById(id);
    if (element) element.textContent = text;
  }

  function startLiveClock() {
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) return;

    const dateTextEl = dateEl.querySelector('.weather-datetime-text');
    const timeTextEl = timeEl.querySelector('.weather-datetime-text');

    const tick = () => {
      const now = new Date();
      if (dateTextEl) dateTextEl.textContent = now.toLocaleDateString();
      if (timeTextEl) {
        timeTextEl.textContent = now.toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit'
        });
      }
    };

    tick();
    setInterval(tick, 1000);
  }

  document.addEventListener('DOMContentLoaded', () => {
    startLiveClock();
    fetchTelemetry();
    setInterval(fetchTelemetry, POLL_INTERVAL);
  });
})();
