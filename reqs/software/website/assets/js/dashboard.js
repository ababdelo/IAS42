/**
 * IAS42 Dashboard - Local Weather & Sensor Telemetry Controller
 */
(() => {
  'use strict';

  window.IAS42_LastTelemetry = null;

  const WEATHER_CONDITIONS = {
    STORM_LIKE: { key: 'dashboard.weather.conditions.storm', default: 'Storm-like', icon: 'ri-thunderstorms-fill', color: '#6366f1' },
    RAINY: { key: 'dashboard.weather.conditions.rainy', default: 'Rainy', icon: 'ri-rainy-fill', color: '#3b82f6' },
    WINDY: { key: 'dashboard.weather.conditions.windy', default: 'Windy', icon: 'ri-windy-fill', color: '#8b5cf6' },
    HOT_DRY: { key: 'dashboard.weather.conditions.hot_dry', default: 'Hot & Dry', icon: 'ri-sun-line', color: '#f97316' },
    HOT_HUMID: { key: 'dashboard.weather.conditions.hot_humid', default: 'Hot & Humid', icon: 'ri-temp-hot-line', color: '#ef4444' },
    COOL: { key: 'dashboard.weather.conditions.cool', default: 'Cool', icon: 'ri-snowflake-line', color: '#06b6d4' },
    CLEAR: { key: 'dashboard.weather.conditions.clear', default: 'Clear', icon: 'ri-sun-fill', color: '#eab308' }
  };

  function inferLocalWeather({ temp, humidity, windSpeed, isRaining }) {
    if (isRaining && windSpeed > 30) return WEATHER_CONDITIONS.STORM_LIKE;
    if (isRaining) return WEATHER_CONDITIONS.RAINY;
    if (windSpeed > 25) return WEATHER_CONDITIONS.WINDY;
    if (temp >= 30 && humidity < 40) return WEATHER_CONDITIONS.HOT_DRY;
    if (temp >= 30 && humidity >= 60) return WEATHER_CONDITIONS.HOT_HUMID;
    if (temp <= 18) return WEATHER_CONDITIONS.COOL;
    return WEATHER_CONDITIONS.CLEAR;
  }

  function updateDashboardUI(data) {
    if (!data) return;
    window.IAS42_LastTelemetry = data;
    
    const condition = inferLocalWeather(data);
    const iconEl = document.getElementById('weatherIcon');
    const labelEl = document.getElementById('weatherConditionLabel');
    const tempEl = document.getElementById('airTemp');
    const humidityEl = document.getElementById('airHumidity');
    const windEl = document.getElementById('windSpeed');

    // BUG FIX: Ensure dynamically rendered data sets data-i18n so it correctly translates if the user switches languages
    if (labelEl) {
      labelEl.setAttribute('data-i18n', condition.key);
      labelEl.textContent = window.t ? (window.t(condition.key) || condition.default) : condition.default;
    }
    
    if (tempEl) tempEl.textContent = Math.round(data.temp);
    if (humidityEl) humidityEl.textContent = `${Math.round(data.humidity)}%`;
    
    if (windEl) {
      const unitLabel = window.t ? (window.t('dashboard.units.kmh') || 'km/h') : 'km/h';
      windEl.textContent = `${Math.round(data.windSpeed)} ${unitLabel}`;
    }
    if (iconEl) {
      iconEl.className = condition.icon + " weather-icon-large";
      iconEl.style.color = condition.color;
    }
  }

  function startLiveClock() {
    const dateEl = document.getElementById('currentDate');
    const timeEl = document.getElementById('currentTime');
    if (!dateEl || !timeEl) return;
    const dateTextEl = dateEl.querySelector('.weather-datetime-text');
    const timeTextEl = timeEl.querySelector('.weather-datetime-text');
    if (!dateTextEl || !timeTextEl) return;
    let previousDateText = '';
    let previousTimeText = '';

    const updateTime = () => {
      const now = new Date();
      const currentLang = document.documentElement.getAttribute('lang') || 'en';
      let locale = 'en-US';
      if (currentLang === 'fr') locale = 'fr-FR';
      if (currentLang === 'es') locale = 'es-ES';
      if (currentLang === 'ar') locale = 'ar-MA';

      // Year included natively
      const dateText = now.toLocaleDateString(locale, {
          weekday: 'long', 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric' 
      });
      const timeText = now.toLocaleTimeString(locale, { hour: 'numeric', minute: '2-digit' });

      if (dateText !== previousDateText) {
        dateTextEl.textContent = dateText;
        previousDateText = dateText;
      }
      if (timeText !== previousTimeText) {
        timeTextEl.textContent = timeText;
        previousTimeText = timeText;
      }
    };

    updateTime();
    setInterval(updateTime, 1000);
    window.IAS42_UpdateTime = updateTime;
  }

  document.addEventListener('DOMContentLoaded', () => {
    startLiveClock();
    updateDashboardUI({ temp: 27, humidity: 64, windSpeed: 12, isRaining: false });
  });

  document.addEventListener('languageChanged', () => {
    if (window.IAS42_UpdateTime) window.IAS42_UpdateTime();
    updateDashboardUI(window.IAS42_LastTelemetry);
  });

  window.IAS42_UpdateTelemetry = updateDashboardUI;
})();
