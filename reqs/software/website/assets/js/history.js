(() => {
  'use strict';

  const fieldSelect = document.getElementById('historyField');
  const typeSelect = document.getElementById('historyType');
  const periodSelect = document.getElementById('historyPeriod');
  const container = document.getElementById('historyContainer');
  const empty = document.getElementById('historyEmpty');
  const count = document.getElementById('historyCount');

  if (!fieldSelect || !typeSelect || !periodSelect || !container || !empty || !count) return;

  let allEvents = [];
  let fields = [];
  let lastLanguage = document.documentElement.lang || 'en';

  const tr = (key, params = {}) => {
    if (typeof window.t !== 'function') return key;
    return window.t(key, params) ?? key;
  };

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const eventTypeKey = {
    irrigation: 'history.types.irrigation',
    alert: 'history.types.alert',
    temperature: 'history.types.temperature',
    update: 'history.types.update'
  };

  function setFieldOptions() {
    const selected = fieldSelect.value;
    fieldSelect.innerHTML = `<option value="all">${escapeHtml(tr('history.filters.all_fields'))}</option>`;

    fields.forEach((field) => {
      fieldSelect.insertAdjacentHTML(
        'beforeend',
        `<option value="${escapeHtml(field.id)}">${escapeHtml(field.name)}</option>`
      );
    });

    fieldSelect.value = fields.some((field) => String(field.id) === String(selected)) ? selected : 'all';
  }

  function parseDate(value) {
    const date = new Date(String(value).replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? null : date;
  }

  function formatDateHeader(value) {
    const date = parseDate(value);

    if (!date) return value;

    return date.toLocaleDateString(document.documentElement.lang || 'en', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  }

  function formatEventTime(value) {
    const date = parseDate(value);

    if (!date) return value;

    return date.toLocaleTimeString(document.documentElement.lang || 'en', {
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function renderLoading() {
    container.setAttribute('aria-busy', 'true');
    container.innerHTML = `
      <div class="history-loading" aria-hidden="true">
        <div class="history-loading-item"></div>
        <div class="history-loading-item"></div>
        <div class="history-loading-item"></div>
      </div>
    `;
    empty.hidden = true;
  }

  function waitForTranslations() {
    if (window.translations) return Promise.resolve();

    return new Promise((resolve) => {
      let settled = false;
      const finish = () => {
        if (settled) return;
        settled = true;
        document.removeEventListener('languageChanged', finish);
        resolve();
      };

      document.addEventListener('languageChanged', finish, { once: true });
      window.setTimeout(finish, 2000);
    });
  }

  function render() {
    const fieldValue = fieldSelect.value;
    const typeValue = typeSelect.value;

    const filtered = allEvents.filter((event) => {
      const fieldMatches = fieldValue === 'all' || String(event.field_id) === String(fieldValue);
      const typeMatches = typeValue === 'all' || event.type === typeValue;
      return fieldMatches && typeMatches;
    });

    count.textContent = String(filtered.length);
    container.setAttribute('aria-busy', 'false');
    empty.hidden = filtered.length > 0;

    if (!filtered.length) {
      container.innerHTML = '';
      return;
    }

    const groups = new Map();

    filtered.forEach((event) => {
      const dateKey = String(event.timestamp).slice(0, 10);

      if (!groups.has(dateKey)) {
        groups.set(dateKey, []);
      }

      groups.get(dateKey).push(event);
    });

    container.innerHTML = [...groups.entries()].map(([date, events]) => `
      <section class="history-day">
        <h2>${escapeHtml(formatDateHeader(date))}</h2>
        <div class="history-card">
          ${events.map((event) => {
      const params = event.params || {};
      const type = event.type || 'update';
      const eventKind = String(event.id || type).split('-')[0];
      const typeLabel = tr(eventTypeKey[type] || eventTypeKey.update);
      const fieldLabel = event.field_name ? escapeHtml(event.field_name) : '';

      return `
              <article class="history-event history-event-${escapeHtml(type)}" data-event-kind="${escapeHtml(eventKind)}">
                <div class="history-event-icon">
                  <i class="${escapeHtml(event.icon || 'ri-information-line')}" aria-hidden="true"></i>
                </div>
                <div class="history-event-content">
                  <strong>${escapeHtml(tr(event.title_key, params))}</strong>
                  <p>${escapeHtml(tr(event.description_key, params))}</p>
                  <div class="history-event-meta">
                    <span class="history-event-type">${escapeHtml(typeLabel)}</span>
                    ${fieldLabel ? `<span class="history-event-field">${fieldLabel}</span>` : ''}
                  </div>
                </div>
                <time datetime="${escapeHtml(event.timestamp)}">${escapeHtml(formatEventTime(event.timestamp))}</time>
              </article>
            `;
    }).join('')}
        </div>
      </section>
    `).join('');
  }

  async function load() {
    renderLoading();
    await waitForTranslations();

    try {
      const response = await fetch(`/api/history?days=${encodeURIComponent(periodSelect.value || '7')}`, {
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json'
        },
        cache: 'no-store'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const result = await response.json();

      if (result.status !== 'success') {
        throw new Error(result.message || 'History request failed.');
      }

      allEvents = Array.isArray(result.events) ? result.events : [];
      fields = Array.isArray(result.fields) ? result.fields : [];

      setFieldOptions();
      render();
    } catch (error) {
      console.error('History loading failed:', error);
      allEvents = [];
      fields = [];
      setFieldOptions();
      count.textContent = '0';
      container.setAttribute('aria-busy', 'false');
      container.innerHTML = '';
      empty.hidden = false;
    }
  }

  fieldSelect.addEventListener('change', render);
  typeSelect.addEventListener('change', render);
  periodSelect.addEventListener('change', load);

  document.addEventListener('languageChanged', () => {
    const nextLanguage = document.documentElement.lang || 'en';

    if (nextLanguage !== lastLanguage) {
      lastLanguage = nextLanguage;
      setFieldOptions();
      render();
    }
  });

  load();
})();
