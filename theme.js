document.addEventListener('DOMContentLoaded', () => {
  const lightBtn = document.getElementById('themeLightBtn');
  const darkBtn = document.getElementById('themeDarkBtn');
  const quickThemeBtn = document.getElementById('quickThemeBtn');

  // Activate Light Mode
  function enableLightMode() {
    document.body.classList.remove('dark-theme');
    if (lightBtn && darkBtn) {
      lightBtn.classList.add('active');
      darkBtn.classList.remove('active');
    }
    if (quickThemeBtn) quickThemeBtn.textContent = '🌙';
  }

  // Activate Dark Mode
  function enableDarkMode() {
    document.body.classList.add('dark-theme');
    if (lightBtn && darkBtn) {
      darkBtn.classList.add('active');
      lightBtn.classList.remove('active');
    }
    if (quickThemeBtn) quickThemeBtn.textContent = '☀️';
  }
  if (lightBtn) lightBtn.addEventListener('click', enableLightMode);
  if (darkBtn) darkBtn.addEventListener('click', enableDarkMode);

  
  if (quickThemeBtn) {
    quickThemeBtn.addEventListener('click', () => {
      if (document.body.classList.contains('dark-theme')) {
        enableLightMode();
      } else {
        enableDarkMode();
      }
    });
  }
});