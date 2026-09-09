export function initPasswordGenerator() {
    const generateBtns = document.querySelectorAll(".generate-password-btn");

    if (generateBtns.length === 0) return;

    function generateSecurePassword(length = 16) {
        const CAP_LET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const LOW_LET = 'abcdefghijklmnopqrstuvwxyz';
        const DIGITS = '0123456789';
        const SPECIALS = '!^.*_+~:-';

        const charset = CAP_LET + LOW_LET + DIGITS + SPECIALS;

        const getRandomChar = (str) => {
            const randomValues = new Uint32Array(1);
            window.crypto.getRandomValues(randomValues);
            return str[randomValues[0] % str.length];
        };

        let passwordArray = [
            getRandomChar(CAP_LET),
            getRandomChar(LOW_LET),
            getRandomChar(DIGITS),
            getRandomChar(SPECIALS)
        ];

        const remainingLength = length - passwordArray.length;
        const randomValues = new Uint32Array(remainingLength);
        window.crypto.getRandomValues(randomValues);

        for (let i = 0; i < remainingLength; i++) {
            passwordArray.push(charset[randomValues[i] % charset.length]);
        }

        for (let i = passwordArray.length - 1; i > 0; i--) {
            const jArray = new Uint32Array(1);
            window.crypto.getRandomValues(jArray);
            const j = jArray[0] % (i + 1);
            [passwordArray[i], passwordArray[j]] = [passwordArray[j], passwordArray[i]];
        }

        return passwordArray.join('');
    }

    generateBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            const targets = btn.getAttribute("data-targets").split(",");
            const newPassword = generateSecurePassword(16);

            targets.forEach(targetId => {
                const input = document.getElementById(targetId.trim());
                if (input) {
                    input.value = newPassword;

                    if (input.type === "password") {
                        input.type = "text";
                        const toggleIcon = input.parentElement.querySelector(".toggle-password");
                        if (toggleIcon) {
                            toggleIcon.classList.remove("fa-eye-slash");
                            toggleIcon.classList.add("fa-eye");
                        }
                    }
                }
            });

            // Use the translation function to get the "Copied!" message
            const copiedMessage = window.t ? window.t('global.copied') : 'Copied!';
            const originalHTML = btn.innerHTML;
            btn.innerHTML = `<i class="fa-solid fa-check"></i> ${copiedMessage}`;
            btn.style.color = "#7da417";

            navigator.clipboard.writeText(newPassword).then(() => {
                // Already updated above
            }).catch(err => {
                console.error("Failed to copy password to clipboard:", err);
                // revert if copy fails? But we already changed the button; better to revert after a timeout anyway
            });

            // Revert after 2 seconds
            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.style.color = "";
            }, 2000);
        });
    });
}
