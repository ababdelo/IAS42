(() => {
  'use strict';

  const form = document.getElementById('settingsProfileForm');
  const status = document.getElementById('settingsProfileStatus');
  const csrf = form?.querySelector('input[name="csrf_token"]')?.value || '';
  const notificationKey = 'ias42-notification-preferences';
  const unitKey = 'ias42-unit-system';

  const tr = (key, params = {}) => {
    if (typeof window.t !== 'function') return key;
    return window.t(key, params) ?? key;
  };

  function setStatus(message, kind = '') {
    if (!status) return;
    status.textContent = message;
    status.className = `settings-form-status ${kind}`.trim();
  }

  function getTheme() {
    return document.body.classList.contains('dark-theme') ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    const dark = theme === 'dark';
    document.body.classList.toggle('dark-theme', dark);
    localStorage.setItem('selected-theme', dark ? 'dark' : 'light');
    localStorage.setItem('selected-icon', dark ? 'ri-sun-line' : 'ri-moon-clear-line');
    const sidebarThemeButton = document.getElementById('theme-button');
    if (sidebarThemeButton) {
      sidebarThemeButton.classList.remove('ri-sun-line', 'ri-moon-clear-line');
      sidebarThemeButton.classList.add(dark ? 'ri-sun-line' : 'ri-moon-clear-line');
    }
    document.querySelectorAll('[data-theme-choice]').forEach((button) => {
      const selected = button.dataset.themeChoice === (dark ? 'dark' : 'light');
      button.classList.toggle('is-selected', selected);
      button.setAttribute('aria-checked', String(selected));
    });
    document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: dark ? 'dark' : 'light' } }));
  }

  function loadNotifications() {
    const defaults = { irrigation: true, weather: true, moisture: true, summary: false };
    try {
      const saved = JSON.parse(localStorage.getItem(notificationKey) || '{}');
      const merged = { ...defaults, ...(saved && typeof saved === 'object' ? saved : {}) };
      document.querySelectorAll('[data-notification]').forEach((input) => {
        input.checked = Boolean(merged[input.dataset.notification]);
      });
    } catch {
      document.querySelectorAll('[data-notification]').forEach((input) => {
        input.checked = input.dataset.notification !== 'summary';
      });
    }
  }

  function saveNotification(name, checked) {
    let saved = {};
    try {
      saved = JSON.parse(localStorage.getItem(notificationKey) || '{}');
    } catch {
      saved = {};
    }
    saved[name] = checked;
    localStorage.setItem(notificationKey, JSON.stringify(saved));
  }

  async function saveProfile(event) {
    event.preventDefault();
    setStatus(tr('settings.profile.saving'));

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    try {
      const response = await fetch('/api/settings', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-Token': csrf
        },
        body: JSON.stringify(payload)
      });

      const result = await response.json().catch(() => ({}));
      if (!response.ok || result.status !== 'success') {
        throw new Error(result.message || tr('settings.profile.error'));
      }

      setStatus(tr('settings.profile.saved'), 'success');
      window.setTimeout(() => setStatus(''), 3500);
    } catch (error) {
      console.error('Profile update failed:', error);
      setStatus(error.message || tr('settings.profile.error'), 'error');
    }
  }

  if (form) form.addEventListener('submit', saveProfile);

  document.querySelectorAll('[data-notification]').forEach((input) => {
    input.addEventListener('change', () => saveNotification(input.dataset.notification, input.checked));
  });

  document.querySelectorAll('[data-theme-choice]').forEach((button) => {
    button.addEventListener('click', () => applyTheme(button.dataset.themeChoice));
  });

  document.addEventListener('languageChanged', () => {
    const current = getTheme();
    applyTheme(current);
  });

  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(getTheme());
    loadNotifications();
  });
})();
