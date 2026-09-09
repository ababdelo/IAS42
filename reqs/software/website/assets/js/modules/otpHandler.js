export function initOtpHandler() {
    const otpContainer = document.querySelector(".otp-container");
    if (!otpContainer) return;

    const otpInputs = otpContainer.querySelectorAll("input");
    const hiddenOtpInput = document.getElementById("otp-token-hidden");

    function updateHiddenOtp() {
        if (!hiddenOtpInput) return;
        let code = "";
        otpInputs.forEach(inp => code += inp.value);
        hiddenOtpInput.value = code;
    }

    otpInputs.forEach((input, index) => {
        input.addEventListener("input", (e) => {
            const value = e.target.value;
            if (value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            updateHiddenOtp();
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && !input.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });

        input.addEventListener("paste", (e) => {
            e.preventDefault();
            const pasteData = e.clipboardData.getData("text").trim();
            if (/^\d{6}$/.test(pasteData)) {
                otpInputs.forEach((inp, i) => {
                    inp.value = pasteData[i];
                });
                otpInputs[otpInputs.length - 1].focus();
                updateHiddenOtp();
            }
        });
    });
}
