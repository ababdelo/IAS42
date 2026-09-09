
export function initPasswordToggle() {
    const toggleIcons = document.querySelectorAll(".toggle-password");
    
    if (toggleIcons.length === 0) return;

    toggleIcons.forEach(icon => {
        icon.addEventListener("click", function () {
            const input = this.parentElement.querySelector("input");
            
            if (input) {
                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove("fa-eye-slash");
                    this.classList.add("fa-eye");
                } else {
                    input.type = "password";
                    this.classList.remove("fa-eye");
                    this.classList.add("fa-eye-slash");
                }
            }
        });
    });
}
