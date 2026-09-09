/*=============== RESPONSIVE SIDEBAR / HEADER ===============*/
(() => {
  const MOBILE_MAX = 600;
  const TABLET_MAX = 1024;
  const SIDEBAR_WIDTH_EXPANDED = '275px';
  const SIDEBAR_WIDTH_COLLAPSED = '90px';
  const SIDEBAR_STORAGE_KEY = 'cslm42-sidebar-collapsed';

  const root = document.documentElement;
  const body = document.body;
  const sidebar = document.getElementById('sidebar');
  const toggle = document.getElementById('header-toggle');
  const overlay = document.getElementById('sidebar-overlay');

  if (!sidebar || !toggle) return;

  const isMobile = () => window.innerWidth <= MOBILE_MAX;
  const isTablet = () => window.innerWidth > MOBILE_MAX && window.innerWidth <= TABLET_MAX;
  const isDesktop = () => window.innerWidth > TABLET_MAX;

  const setSidebarWidth = (value) => {
    root.style.setProperty('--sidebar-width', value);
  };

  const showOverlay = () => {
    if (overlay) overlay.hidden = false;
  };

  const hideOverlay = () => {
    if (overlay) overlay.hidden = true;
  };

  // Utility to close language menu on sidebar toggle
  const closeLanguageMenu = () => {
    const langMenu = document.getElementById('language-menu');
    const langToggle = document.getElementById('lang-toggle');
    if (langMenu && !langMenu.hidden) {
      langMenu.hidden = true;
      if (langToggle) langToggle.setAttribute('aria-expanded', 'false');
    }
  };

  const openMobileSidebar = () => {
    sidebar.classList.add('is-open');
    body.classList.add('sidebar-open');
    toggle.setAttribute('aria-expanded', 'true');
    showOverlay();
  };

  const closeMobileSidebar = () => {
    sidebar.classList.remove('is-open');
    body.classList.remove('sidebar-open');
    toggle.setAttribute('aria-expanded', 'false');
    hideOverlay();
  };

  const applyDesktopSidebarState = () => {
    const savedState = localStorage.getItem(SIDEBAR_STORAGE_KEY);
    if (savedState === 'false') {
      sidebar.classList.remove('is-collapsed');
    } else {
      sidebar.classList.add('is-collapsed');
    }
    setSidebarWidth(sidebar.classList.contains('is-collapsed') ? SIDEBAR_WIDTH_COLLAPSED : SIDEBAR_WIDTH_EXPANDED);
    toggle.removeAttribute('aria-disabled');
    toggle.setAttribute('aria-expanded', String(!sidebar.classList.contains('is-collapsed')));
  };

  const applyTabletSidebarState = () => {
    sidebar.classList.add('is-collapsed');
    sidebar.classList.remove('is-open');
    body.classList.remove('sidebar-open');
    setSidebarWidth(SIDEBAR_WIDTH_COLLAPSED);
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-disabled', 'true');
    hideOverlay();
  };

  const applyMobileSidebarState = () => {
    sidebar.classList.add('is-collapsed');
    setSidebarWidth('0px');
    toggle.removeAttribute('aria-disabled');
    if (!sidebar.classList.contains('is-open')) {
      toggle.setAttribute('aria-expanded', 'false');
      body.classList.remove('sidebar-open');
      hideOverlay();
    }
  };

  const syncLayout = () => {
    if (isMobile()) {
      applyMobileSidebarState();
      return;
    }
    closeMobileSidebar();
    if (isTablet()) {
      applyTabletSidebarState();
      return;
    }
    if (isDesktop()) {
      applyDesktopSidebarState();
    }
  };

  toggle.addEventListener('click', () => {
    closeLanguageMenu(); // Close accordion when toggling sidebar
    
    if (isMobile()) {
      sidebar.classList.contains('is-open') ? closeMobileSidebar() : openMobileSidebar();
      return;
    }
    if (isTablet()) {
      applyTabletSidebarState();
      return;
    }
    
    sidebar.classList.toggle('is-collapsed');
    const isCollapsed = sidebar.classList.contains('is-collapsed');
    localStorage.setItem(SIDEBAR_STORAGE_KEY, String(isCollapsed));
    setSidebarWidth(isCollapsed ? SIDEBAR_WIDTH_COLLAPSED : SIDEBAR_WIDTH_EXPANDED);
    toggle.setAttribute('aria-expanded', String(!isCollapsed));
  });

  overlay?.addEventListener('click', closeMobileSidebar);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isMobile() && sidebar.classList.contains('is-open')) {
      closeMobileSidebar();
    }
  });

  sidebar.querySelectorAll('a.sidebar__link').forEach((link) => {
    link.addEventListener('click', () => {
      if (isMobile()) closeMobileSidebar();
    });
  });

  window.addEventListener('resize', syncLayout);
  syncLayout();
})();

/*=============== LINK ACTIVE ===============*/
const sidebarLinks = document.querySelectorAll('.sidebar__list a');
const linkColor = function () {
  sidebarLinks.forEach((link) => link.classList.remove('active-link'));
  this.classList.add('active-link');
};
sidebarLinks.forEach((link) => link.addEventListener('click', linkColor));

/*=============== DARK / LIGHT THEME ===============*/
const themeButton = document.getElementById('theme-button');
const darkTheme = 'dark-theme';
const lightIcon = 'ri-sun-line';
const darkIcon = 'ri-moon-clear-line';

if (themeButton) {
  const selectedTheme = localStorage.getItem('selected-theme');
  const selectedIcon = localStorage.getItem('selected-icon');

  const getCurrentTheme = () => document.body.classList.contains(darkTheme) ? 'dark' : 'light';
  const getCurrentIcon = () => themeButton.classList.contains(lightIcon) ? lightIcon : darkIcon;

  const setThemeIcon = (icon) => {
    themeButton.classList.remove(lightIcon, darkIcon);
    themeButton.classList.add(icon);
  };

  if (selectedTheme) {
    document.body.classList[selectedTheme === 'dark' ? 'add' : 'remove'](darkTheme);
    setThemeIcon(selectedIcon === lightIcon || selectedIcon === darkIcon 
      ? selectedIcon 
      : selectedTheme === 'dark' ? lightIcon : darkIcon);
  }

  themeButton.addEventListener('click', () => {
    document.body.classList.toggle(darkTheme);
    setThemeIcon(getCurrentTheme() === 'dark' ? lightIcon : darkIcon);
    localStorage.setItem('selected-theme', getCurrentTheme());
    localStorage.setItem('selected-icon', getCurrentIcon());
  });
}
