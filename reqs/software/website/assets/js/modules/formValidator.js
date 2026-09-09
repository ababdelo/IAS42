// modules/formValidator.js

/**
 * Validates a single field based on its data-validate attribute.
 * @param {HTMLInputElement} input - The input element.
 * @param {string} ruleString - e.g. "required|email|password"
 * @param {Object} options - Additional configuration (e.g., form inputs for cross-field checks).
 * @returns {string|null} - Error message or null if valid.
 */
function validateField(input, ruleString, options) {
    const rules = ruleString.split('|').map(r => r.trim());
    const value = input.value.trim();

    for (const rule of rules) {
        switch (rule) {
            case 'required':
                if (!value) {
                    return window.t('validation.required') || 'This field is required.';
                }
                break;

            case 'email':
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return window.t('validation.email') || 'Please enter a valid email address.';
                }
                break;

            case 'password': {
                if (value.length < 8) {
                    return window.t('validation.password.length') || 'Password must be at least 8 characters long.';
                }
                if (!/[A-Z]/.test(value)) {
                    return window.t('validation.password.uppercase') || 'Password must contain at least one uppercase letter.';
                }
                if (!/[a-z]/.test(value)) {
                    return window.t('validation.password.lowercase') || 'Password must contain at least one lowercase letter.';
                }
                if (!/\d/.test(value)) {
                    return window.t('validation.password.digit') || 'Password must contain at least one digit.';
                }
                if (!/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                    return window.t('validation.password.special') || 'Password must contain at least one special character.';
                }
                break;
            }

            case 'username': {
                if (value.length < 3) {
                    return window.t('validation.username.length') || 'Username must be at least 3 characters long.';
                }
                // Allow Unicode letters, numbers, underscore (any script)
                if (!/^[\p{L}\p{N}_]+$/u.test(value)) {
                    return window.t('validation.username.characters') || 'Username can only contain letters, numbers, and underscores.';
                }
                break;
            }

            case 'firstname': {
                if (value.length < 2) {
                    return window.t('validation.firstname.length') || 'First name must be at least 2 characters.';
                }
                // Allow Unicode letters, spaces, hyphens, apostrophes
                if (!/^[\p{L}\s\-']+$/u.test(value)) {
                    return window.t('validation.firstname.characters') || 'First name can only contain letters, spaces, hyphens, and apostrophes.';
                }
                break;
            }

            case 'lastname': {
                if (value.length < 2) {
                    return window.t('validation.lastname.length') || 'Last name must be at least 2 characters.';
                }
                if (!/^[\p{L}\s\-']+$/u.test(value)) {
                    return window.t('validation.lastname.characters') || 'Last name can only contain letters, spaces, hyphens, and apostrophes.';
                }
                break;
            }

            case 'otp': {
                const otpContainer = input.closest('.otp-container');
                let code = value;
                if (otpContainer) {
                    const inputs = otpContainer.querySelectorAll('input');
                    code = Array.from(inputs).map(inp => inp.value).join('');
                }
                if (!/^\d{6}$/.test(code)) {
                    return window.t('validation.otp') || 'Please enter a valid 6-digit OTP.';
                }
                break;
            }

            case 'terms': {
                if (!input.checked) {
                    return window.t('validation.terms') || 'You must agree to the Terms of Use and Privacy Policy.';
                }
                break;
            }

            case 'match': {
                const targetId = input.getAttribute('data-match');
                if (!targetId) break;
                const targetInput = document.getElementById(targetId.replace('#', ''));
                if (targetInput && value !== targetInput.value) {
                    return window.t('validation.match') || 'Passwords do not match.';
                }
                break;
            }

            default:
                break;
        }
    }
    return null;
}

/**
 * Displays an error message for a given input element.
 */
function setError(input, message) {
    const wrapper = input.closest('.input-wrapper') || input.parentElement;
    let errorEl = wrapper?.querySelector('.error-msg');
    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'error-msg';
        errorEl.style.cssText = 'display:block; color:#dc2626; font-size:12px; margin-top:4px;';
        if (wrapper) {
            wrapper.appendChild(errorEl);
        } else {
            input.insertAdjacentElement('afterend', errorEl);
        }
    }
    errorEl.textContent = message;
    errorEl.style.display = 'block';

    const otpContainer = input.closest('.otp-container');
    if (otpContainer) {
        otpContainer.querySelectorAll('input').forEach(inp => inp.classList.add('error'));
    } else {
        input.classList.add('error');
    }
}

function clearError(input) {
    const wrapper = input.closest('.input-wrapper') || input.parentElement;
    const errorEl = wrapper?.querySelector('.error-msg');
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.style.display = 'none';
    }

    const otpContainer = input.closest('.otp-container');
    if (otpContainer) {
        otpContainer.querySelectorAll('input').forEach(inp => inp.classList.remove('error'));
    } else {
        input.classList.remove('error');
    }
}

/**
 * Validates an entire form.
 */
function validateForm(form) {
    let isValid = true;

    form.querySelectorAll('.error-msg').forEach(el => {
        el.textContent = '';
        el.style.display = 'none';
    });
    form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

    const inputs = form.querySelectorAll('[data-validate]');
    const fieldsToValidate = [];

    const termsCheckbox = form.querySelector('input[name="terms"]');
    if (termsCheckbox) {
        fieldsToValidate.push({ input: termsCheckbox, ruleString: 'terms' });
    }

    inputs.forEach(input => {
        const ruleString = input.getAttribute('data-validate');
        if (ruleString) {
            fieldsToValidate.push({ input, ruleString });
        }
    });

    for (const item of fieldsToValidate) {
        const { input, ruleString } = item;
        const error = validateField(input, ruleString, { form });
        if (error) {
            setError(input, error);
            isValid = false;
        } else {
            clearError(input);
        }
    }

    return isValid;
}

/**
 * Attaches real‑time validation events.
 */
function attachRealtimeValidation(input) {
    const eventTypes = ['input', 'change'];
    eventTypes.forEach(eventType => {
        input.addEventListener(eventType, function () {
            const ruleString = this.getAttribute('data-validate');
            if (!ruleString) return;
            const form = this.closest('form');
            if (!form) return;
            const error = validateField(this, ruleString, { form });
            if (error) {
                setError(this, error);
            } else {
                clearError(this);
            }
        });
    });
}

/**
 * Refreshes all error messages (for language change).
 */
function refreshAllErrors() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const errorInputs = form.querySelectorAll('.error');
        errorInputs.forEach(input => {
            const ruleString = input.getAttribute('data-validate');
            if (!ruleString) return;
            const error = validateField(input, ruleString, { form });
            if (error) {
                setError(input, error);
            } else {
                clearError(input);
            }
        });

        const termsCheckbox = form.querySelector('input[name="terms"]');
        if (termsCheckbox && termsCheckbox.classList.contains('error')) {
            const error = validateField(termsCheckbox, 'terms', { form });
            if (error) {
                setError(termsCheckbox, error);
            } else {
                clearError(termsCheckbox);
            }
        }
    });
}

export function initFormValidation() {
    const forms = document.querySelectorAll('form');

    document.addEventListener('languageChanged', refreshAllErrors);

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!validateForm(this)) {
                e.preventDefault();
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.focus();
                }
            }
        });

        const fields = form.querySelectorAll('[data-validate]');
        fields.forEach(input => attachRealtimeValidation(input));

        const termsCheckbox = form.querySelector('input[name="terms"]');
        if (termsCheckbox) {
            termsCheckbox.setAttribute('data-validate', 'terms');
            attachRealtimeValidation(termsCheckbox);
        }
    });
}
