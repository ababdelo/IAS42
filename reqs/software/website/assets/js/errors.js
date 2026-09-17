'use strict';

(function () {
    const SUPPORTED_LANGUAGES = new Set(['en', 'fr', 'es', 'ar']);
    const SUPPORTED_ERROR_CODES = new Set([400, 401, 403, 404, 408, 413, 500, 503]);
    const DEFAULT_LANGUAGE = 'en';
    const DEFAULT_ERROR_CODE = 404;

    const FALLBACK_TRANSLATIONS = {
        back_to_home: 'Back to home page',
        unknown: {
            title: 'Unknown Error',
            description: 'An unknown error has occurred.'
        },
        400: {
            title: 'Bad Request',
            description: 'The server cannot process the request due to a client error.'
        },
        401: {
            title: 'Unauthorized',
            description: 'Authentication is required to access this resource.'
        },
        403: {
            title: 'Forbidden',
            description: 'You do not have permission to access this resource.'
        },
        404: {
            title: 'Not Found',
            description: 'The requested resource could not be found.'
        },
        408: {
            title: 'Request Timeout',
            description: 'The server timed out while waiting for the request.'
        },
        413: {
            title: 'Payload Too Large',
            description: 'The request payload is too large for the server to process.'
        },
        500: {
            title: 'Internal Server Error',
            description: 'The server encountered an unexpected condition.'
        },
        503: {
            title: 'Service Unavailable',
            description: 'The service is temporarily unavailable. Please try again later.'
        }
    };

    function getErrorCode() {
        const code = Number(window.IAS42ErrorCode);
        return SUPPORTED_ERROR_CODES.has(code) ? code : DEFAULT_ERROR_CODE;
    }

    function getPreferredLanguage() {
        try {
            const storedLanguage = localStorage.getItem('lang');
            return SUPPORTED_LANGUAGES.has(storedLanguage) ? storedLanguage : DEFAULT_LANGUAGE;
        } catch (error) {
            return DEFAULT_LANGUAGE;
        }
    }

    function setDocumentLanguage(language) {
        document.documentElement.lang = language;
        document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
    }

    function getErrorElements() {
        return {
            backLink: document.querySelector('.back-link'),
            backLinkText: document.querySelector('.back-link-text'),
            title: document.querySelector('#error-title'),
            description: document.querySelector('#error-description'),
            code: document.querySelector('.error-code')
        };
    }

    function applyTranslations(translations) {
        const code = getErrorCode();
        const fallbackError = FALLBACK_TRANSLATIONS[String(code)] || FALLBACK_TRANSLATIONS.unknown;
        const error = translations[String(code)] || fallbackError;
        const elements = getErrorElements();

        const title = typeof error.title === 'string' ? error.title : fallbackError.title;
        const description = typeof error.description === 'string'
            ? error.description
            : fallbackError.description;
        const backToHome = typeof translations.back_to_home === 'string'
            ? translations.back_to_home
            : FALLBACK_TRANSLATIONS.back_to_home;

        if (elements.backLinkText) {
            elements.backLinkText.textContent = backToHome;
        }

        if (elements.backLink) {
            elements.backLink.setAttribute('aria-label', backToHome);
        }

        if (elements.code) {
            elements.code.textContent = String(code);
        }

        if (elements.title) {
            elements.title.textContent = title;
        }

        if (elements.description) {
            elements.description.textContent = description;
        }

        document.title = `IAS42 : ${title}`;
    }

    async function loadTranslations(language) {
        const languageUrl = `/assets/lang/${language}/errors.json`;
        const fallbackUrl = '/assets/lang/en/errors.json';

        try {
            const response = await fetch(languageUrl, {
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Translation request failed: ${response.status}`);
            }

            applyTranslations(await response.json());
            return;
        } catch (error) {
            console.warn('IAS42 error-page language fallback:', error);
        }

        try {
            const response = await fetch(fallbackUrl, {
                cache: 'no-store',
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error(`Fallback translation request failed: ${response.status}`);
            }

            applyTranslations(await response.json());
            return;
        } catch (error) {
            console.warn('IAS42 error-page fallback translation failed:', error);
        }

        applyTranslations(FALLBACK_TRANSLATIONS);
    }

    function applyTheme() {
        let selectedTheme = 'light';

        try {
            selectedTheme = localStorage.getItem('selected-theme') || 'light';
        } catch (error) {
            selectedTheme = 'light';
        }

        document.body.classList.toggle('dark-theme', selectedTheme === 'dark');
    }

    function handleStoredPreferenceChange(event) {
        if (event.key === 'lang') {
            const language = getPreferredLanguage();
            setDocumentLanguage(language);
            loadTranslations(language);
        }

        if (event.key === 'selected-theme') {
            applyTheme();
        }
    }

    function initialize() {
        const language = getPreferredLanguage();
        setDocumentLanguage(language);
        applyTheme();
        loadTranslations(language);
        window.addEventListener('storage', handleStoredPreferenceChange);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
