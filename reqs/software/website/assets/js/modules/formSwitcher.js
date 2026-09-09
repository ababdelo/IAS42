export function initFormSwitcher() {
    const formMap = {
        "login": document.getElementById("login-form"),
        "register": document.getElementById("register-form"),
        "verify": document.getElementById("otp-form"),
        "forgot": document.getElementById("forgot-form"),
        "otp": document.getElementById("resend-otp-form"),
        "reset": document.getElementById("reset-form")
    };

    const activeForms = {};
    Object.keys(formMap).forEach(action => {
        if (formMap[action]) {
            activeForms[action] = formMap[action];
        }
    });

    if (Object.keys(activeForms).length === 0) return;

    function getCurrentAction() {
        const params = new URLSearchParams(window.location.search);
        const action = params.get("action");
        if (action && activeForms[action]) {
            return action;
        }
        for (const act in activeForms) {
            if (activeForms[act].classList.contains("active")) {
                return act;
            }
        }
        return Object.keys(activeForms)[0] || null;
    }

    function switchForm(action) {
        const targetForm = activeForms[action];
        if (!targetForm) return;

        Object.values(activeForms).forEach(form => {
            form.classList.remove("active");
            form.classList.add("hidden");
        });

        targetForm.classList.remove("hidden");
        targetForm.classList.add("active");
    }

    document.querySelectorAll("a[href*='action=']").forEach(link => {
        // Skip OAuth links to allow normal navigation
        if (link.href.includes('/auth/oauth')) {
            return;
        }
        link.addEventListener("click", (e) => {
            const href = link.getAttribute("href");
            try {
                const urlObj = new URL(href, window.location.origin);
                const action = urlObj.searchParams.get("action");
                if (action && activeForms[action]) {
                    e.preventDefault();
                    window.history.pushState({ action }, "", href);
                    switchForm(action);
                }
            } catch (err) {
                // Fallback to standard navigation
            }
        });
    });

    window.addEventListener("popstate", () => {
        const currentAction = getCurrentAction();
        if (currentAction) {
            switchForm(currentAction);
        }
    });

    const initialAction = getCurrentAction();
    if (initialAction) {
        switchForm(initialAction);
    }
}
