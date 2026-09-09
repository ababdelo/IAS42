import { initFormSwitcher } from "./modules/formSwitcher.js";
import { initOtpHandler } from "./modules/otpHandler.js";
import { initPasswordToggle } from "./modules/passwordToggle.js";
import { initPasswordGenerator } from "./modules/passwordGenerator.js";
import { initFormValidation } from "./modules/formValidator.js";

document.addEventListener("DOMContentLoaded", () => {
    initFormSwitcher();
    initOtpHandler();
    initPasswordToggle();
    initPasswordGenerator();
    initFormValidation();
    renderLoginServerError();
});

function renderLoginServerError() {
    const errorElement = document.getElementById("login-server-error");
    if (!errorElement) return;

    const render = () => {
        try {
            const message = JSON.parse(errorElement.dataset.message);
            errorElement.textContent = window.t(message.key, message.params || {}) || message.key;
        } catch {
            errorElement.textContent = errorElement.dataset.message;
        }
        errorElement.style.display = "block";
    };

    render();
    document.addEventListener("languageChanged", render, { once: true });
}
