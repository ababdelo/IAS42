const historyData = [
  {
    date: '2026-09-09',
    events: [
      { type: 'irrigation', field: 'oasis', title: 'Irrigation completed for Zone 2', details: 'Oasis Farm · 2026-09-09 at 08:24', icon: '💧', iconClass: 'icon-green' },
      { type: 'alert', field: 'zenata', title: 'Soil moisture dropped below 45%', details: 'Zenata Greenhouse · 2026-09-09 at 07:10', icon: '⚠️', iconClass: 'icon-amber' }
    ]
  },
  {
    date: '2026-09-08',
    events: [
      { type: 'temperature', field: 'atlas', title: 'Temperature increased to 31°C', details: 'Atlas Orchard · 2026-09-08 at 15:42', icon: '🌡️', iconClass: 'icon-blue' },
      { type: 'update', field: 'oasis', title: 'Field details updated', details: 'Oasis Farm · 2026-09-08 at 11:05', icon: '✏️', iconClass: 'icon-blue' }
    ]
  },
  {
    date: '2026-09-07',
    events: [
      { type: 'irrigation', field: 'atlas', title: 'New irrigation zone added (Zone 5)', details: 'Atlas Orchard · 2026-09-07 at 09:30', icon: '➕', iconClass: 'icon-green' },
      { type: 'irrigation', field: 'zenata', title: 'Irrigation completed for Zone 1', details: 'Zenata Greenhouse · 2026-09-07 at 06:15', icon: '💧', iconClass: 'icon-green' }
    ]
  }
];

function renderHistory() {
  const container = document.getElementById('historyContainer');
  const fieldVal = document.getElementById('fieldFilter')?.value || 'all';
  const typeVal = document.getElementById('typeFilter')?.value || 'all';

  if (!container) return;

  let html = '';

  historyData.forEach(group => {
    const filteredEvents = group.events.filter(e => {
      const matchField = fieldVal === 'all' || e.field === fieldVal;
      const matchType = typeVal === 'all' || e.type === typeVal;
      return matchField && matchType;
    });

    if (filteredEvents.length > 0) {
      html += `<div class="history-date">${group.date}</div>`;
      html += `<div class="card history-day-card">`;
      filteredEvents.forEach(e => {
        html += `
          <div class="history-row">
            <div class="icon-circle ${e.iconClass}">${e.icon}</div>
            <div>
              <div class="history-row-title">${e.title}</div>
              <div class="history-row-details">${e.details}</div>
            </div>
          </div>
        `;
      });
      html += `</div>`;
    }
  });

  container.innerHTML = html || '<p style="color:var(--text-muted); padding:10px;">No events found.</p>';
}

document.addEventListener('DOMContentLoaded', () => {
  renderHistory();
  document.getElementById('fieldFilter')?.addEventListener('change', renderHistory);
  document.getElementById('typeFilter')?.addEventListener('change', renderHistory);
});
