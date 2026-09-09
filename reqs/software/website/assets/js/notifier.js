/**
 * Notifier - Toast notification system
 * Enforces Max Limit = 1 to prevent vertical stacking
 */
(function () {
    'use strict';
    const messageTypes = {
        error: "fa-circle-xmark",
        success: "fa-circle-check",
        warning: "fa-triangle-exclamation",
        info: "fa-circle-info"
    };

    let pendingMessages = {};

    const removeToast = (toast) => {
        toast.classList.add("hide");
        if (toast.timeoutId) clearTimeout(toast.timeoutId);
        setTimeout(() => toast.remove(), 500);
    };

    function parseMessageData(message, fallbackType) {
        let title = window.t('notifier.' + fallbackType);
        if (title === null) {
            title = fallbackType.charAt(0).toUpperCase() + fallbackType.slice(1);
        }
        let description = message;
        
        try {
            const json = JSON.parse(message);
            if (json.key) {
                let translated = window.t(json.key, json.params || {});
                if (translated !== null) {
                    description = translated;
                } else if (json.message) {
                    description = json.message;
                } else {
                    description = json.key;
                }
            } else if (json.message) {
                description = json.message;
            }
        } catch (e) {
            // BUG FIX: Prevent translating raw user data (e.g. "Sector Alpha Saved")
            // Only translate if it matches the dot-separated key structure
            if (message && message.includes('.') && !message.includes(' ')) {
                let translated = window.t(message);
                if (translated !== null) description = translated;
            }
        }
        return { title, description };
    }

    const createToast = (type, iconClass, message) => {
        const notifications = document.querySelector(".notifications");
        if (!notifications) return;

        while (notifications.firstChild) {
            const existingToast = notifications.firstChild;
            if (existingToast.timeoutId) clearTimeout(existingToast.timeoutId);
            notifications.removeChild(existingToast);
        }

        const { title, description } = parseMessageData(message, type);

        const toast = document.createElement("li");
        toast.className = `toast ${type}`;

        const column = document.createElement("div");
        column.className = "column";

        const icon = document.createElement("i");
        icon.className = `fa-solid ${iconClass}`;

        const typeSpan = document.createElement("span");
        typeSpan.className = "type";
        typeSpan.textContent = title;

        const colonSpan = document.createElement("span");
        colonSpan.className = "colon";
        colonSpan.textContent = ":";

        const descriptionSpan = document.createElement("span");
        descriptionSpan.className = "description";
        descriptionSpan.textContent = description;

        column.appendChild(icon);
        column.appendChild(typeSpan);
        column.appendChild(colonSpan);
        column.appendChild(descriptionSpan);

        const closeButton = document.createElement("i");
        closeButton.className = "fa-solid fa-xmark close-btn";

        const progressBar = document.createElement("div");
        progressBar.className = "progress-bar";

        toast.appendChild(column);
        toast.appendChild(closeButton);
        toast.appendChild(progressBar);
        notifications.appendChild(toast);

        toast.timeoutId = setTimeout(() => removeToast(toast), 5000);
        closeButton.addEventListener('click', () => removeToast(toast));
    };

    window.showNotification = function (type, message) {
        if (!Object.keys(messageTypes).includes(type)) type = 'info';
        createToast(type, messageTypes[type], message);
    };

    function processSessionMessages() {
        const sessionMessages = document.getElementById('session-messages');
        if (!sessionMessages) return false;
        let hasMessages = false;
        Object.keys(messageTypes).forEach((type) => {
            const message = sessionMessages.dataset[type];
            if (message) {
                hasMessages = true;
                createToast(type, messageTypes[type], message);
            }
        });
        return hasMessages;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const sessionMessages = document.getElementById('session-messages');
        if (!sessionMessages) return;
        const hasMessages = processSessionMessages();
        if (hasMessages && (!window.translations || Object.keys(window.translations).length === 0)) {
            pendingMessages = {};
            Object.keys(messageTypes).forEach((type) => {
                const message = sessionMessages.dataset[type];
                if (message) pendingMessages[type] = message;
            });
        }
    });

    document.addEventListener('languageChanged', function() {
        if (Object.keys(pendingMessages).length > 0) {
            const notifications = document.querySelector(".notifications");
            if (notifications) {
                while (notifications.firstChild) {
                    const existingToast = notifications.firstChild;
                    if (existingToast.timeoutId) clearTimeout(existingToast.timeoutId);
                    notifications.removeChild(existingToast);
                }
            }
            for (const [type, message] of Object.entries(pendingMessages)) {
                createToast(type, messageTypes[type], message);
            }
            pendingMessages = {};
        }
    });
})();
