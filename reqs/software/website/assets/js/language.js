/**
 * Language switcher for IAS42.
 * Strictly translates mapped keys without interfering with dynamic user data.
 */
(function () {
    'use strict';

    const LANGUAGES = [
        { code: 'en', label: 'English', dir: 'ltr', flag: 'en.webp', names: { en: 'English', fr: 'Anglais', es: 'Inglés', ar: 'الإنجليزية' } },
        { code: 'fr', label: 'Français', dir: 'ltr', flag: 'fr.webp', names: { en: 'French', fr: 'Français', es: 'Francés', ar: 'الفرنسية' } },
        { code: 'es', label: 'Español', dir: 'ltr', flag: 'sp.webp', names: { en: 'Spanish', fr: 'Espagnol', es: 'Español', ar: 'الإسبانية' } },
        { code: 'ar', label: 'العربية', dir: 'rtl', flag: 'ar.webp', names: { en: 'Arabic', fr: 'Arabe', es: 'Árabe', ar: 'العربية' } }
    ];

    const LANGUAGE_LABELS = { en: 'Language', fr: 'Langue', es: 'Idioma', ar: 'اللغة' };
    const MODULES = ['global', 'validation', 'auth', 'notifier', 'email', 'dashboard', 'analytics', 'history', 'notifications', 'profile', 'settings', 'fields', 'sectors'];

    window.t = function (key, params = {}) {
        if (!window.translations || !key) return null;
        const keys = key.split('.');
        let value = window.translations;
        
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return null;
            }
        }
        
        if (typeof value !== 'string') return null;
        
        for (const [paramKey, paramValue] of Object.entries(params)) {
            value = value.replace(new RegExp(`\\{\\{${paramKey}\\}\\}`, 'g'), paramValue);
        }
        return value;
    };

    let currentLang = localStorage.getItem('lang') || 'en';
    if (!LANGUAGES.some(l => l.code === currentLang)) currentLang = 'en';

    const langToggle = document.getElementById('lang-toggle');
    const languageMenu = document.getElementById('language-menu');

    function applyLanguage(lang) {
        const langObj = LANGUAGES.find(l => l.code === lang);
        if (!langObj) return;

        document.documentElement.setAttribute('lang', lang);
        document.documentElement.setAttribute('dir', langObj.dir);

        const triggerLabel = LANGUAGE_LABELS[lang] || LANGUAGE_LABELS.en;
        const triggerEl = document.querySelector('[data-lang-label="trigger"]');
        
        if (triggerEl) triggerEl.textContent = triggerLabel;
        if (langToggle) langToggle.setAttribute('aria-label', triggerLabel);

        if (languageMenu) {
            languageMenu.querySelectorAll('[data-lang]').forEach(option => {
                const optionLanguage = LANGUAGES.find(language => language.code === option.dataset.lang);
                option.setAttribute('aria-checked', String(option.dataset.lang === lang));
                option.querySelector('.lang-option-label').textContent = optionLanguage.names[lang] || optionLanguage.label;
            });
        }

        localStorage.setItem('lang', lang);
        currentLang = lang;
        document.querySelectorAll('input[name="lang"]').forEach(el => el.value = lang);
    }

    function closeLanguageMenu() {
        if (!languageMenu || !langToggle) return;
        languageMenu.hidden = true;
        langToggle.setAttribute('aria-expanded', 'false');
    }

    function toggleLanguageMenu() {
        if (!languageMenu || !langToggle) return;
        const isOpen = !languageMenu.hidden;
        languageMenu.hidden = isOpen;
        langToggle.setAttribute('aria-expanded', String(!isOpen));
    }

    function selectLanguage(event) {
        const nextLang = event.currentTarget.dataset.lang;
        applyLanguage(nextLang);
        loadTranslations(nextLang);
        closeLanguageMenu();
    }

    if (langToggle && languageMenu) {
        langToggle.addEventListener('click', toggleLanguageMenu);
        languageMenu.querySelectorAll('[data-lang]').forEach(option => {
            option.addEventListener('click', selectLanguage);
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.sidebar__language')) closeLanguageMenu();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeLanguageMenu();
        });
    }

    async function loadTranslations(lang) {
        try {
            const modulePromises = MODULES.map(module =>
                fetch(`/assets/lang/${lang}/${module}.json`)
                    .then(res => {
                        if (!res.ok) throw new Error(`Module ${module} not found`);
                        return res.json();
                    })
                    .catch(() => {
                        return fetch(`/assets/lang/en/${module}.json`)
                            .then(res => res.ok ? res.json() : {})
                            .catch(() => ({}));
                    })
            );

            const modulesData = await Promise.all(modulePromises);
            const nested = {};
            MODULES.forEach((module, index) => {
                nested[module] = modulesData[index] || {};
            });

            window.translations = nested;
            applyTranslationsToDOM();
            document.dispatchEvent(new Event('languageChanged'));
        } catch (error) {
            console.error('Language loading error:', error);
        }
    }

    function applyTranslationsToDOM() {
        // Standard innerHTML translation
        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (!key) return;
            const translated = window.t(key);
            if (translated !== null && translated !== undefined) {
                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                    el.placeholder = translated;
                } else {
                    if (el.innerHTML !== translated) {
                        el.innerHTML = translated;
                    }
                }
            }
        });

        // Standard placeholder translation
        document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
            const key = el.getAttribute('data-i18n-placeholder');
            if (!key) return;
            const translated = window.t(key);
            if (translated !== null && translated !== undefined) {
                el.placeholder = translated;
            }
        });

        // Title translation
        document.querySelectorAll('[data-i18n-title]').forEach(el => {
            const key = el.getAttribute('data-i18n-title');
            if (!key) return;
            const translated = window.t(key);
            if (translated !== null && translated !== undefined) {
                if (el.tagName === 'TITLE') {
                    document.title = "IAS42 : " + translated;
                } else {
                    el.title = translated;
                }
            }
        });
    }

    function initLanguage() {
        applyLanguage(currentLang);
        loadTranslations(currentLang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLanguage);
    } else {
        initLanguage();
    }
})();
