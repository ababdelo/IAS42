<?php
session_start();
require_once __DIR__ . '/../../backend/utils/checkers.php';
require_once __DIR__ . '/../../backend/utils/generators.php';
redirectIfAuthenticated();
generateCsrfToken();

$action = $_GET['action'] ?? 'verify';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="auth.page_title_secure">IAS42: Security</title>
    <link rel="shortcut icon" href="/assets/imgs/logo/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="stylesheet" href="/assets/css/notifier.css">
    <link rel="stylesheet" href="/assets/css/fontAwesome.css">
    <script src="/assets/js/notifier.js" defer></script>
    <script src="/assets/js/language.js" defer></script>
</head>

<body>
    <ul class="notifications"></ul>
    <div id="session-messages"
        data-error="<?php echo isset($_SESSION['error']) ? htmlspecialchars($_SESSION['error']) : ''; ?>"
        data-success="<?php echo isset($_SESSION['success']) ? htmlspecialchars($_SESSION['success']) : ''; ?>"
        data-warning="<?php echo isset($_SESSION['warning']) ? htmlspecialchars($_SESSION['warning']) : ''; ?>"
        data-info="<?php echo isset($_SESSION['info']) ? htmlspecialchars($_SESSION['info']) : ''; ?>">
    </div>
    <?php unset($_SESSION['error'], $_SESSION['success'], $_SESSION['warning'], $_SESSION['info']); ?>

    <!-- OTP VERIFICATION -->
    <div id="otp-form" class="form-container layout-left <?php echo ($action === 'verify') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/verify.png" alt="Verify Account illustration">
            <p class="form-switch" data-i18n="auth.switch_to_resend">Need a new code? <a href="/auth/recover?action=otp">Resend OTP</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.verify.title">Verify Account</h2>
            <form action="/auth/verifyAccount" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <input type="hidden" name="otp_token" id="otp-token-hidden">
                <p class="form-instruction" data-i18n="auth.verify.instruction">Please enter the 6-digit verification token sent to your email address.</p>
                <div class="input-wrapper">
                    <label data-i18n="auth.verify.code_label">Verification Code</label>
                    <div class="otp-container">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <input type="text" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    </div>
                    <span class="error-msg"></span>
                </div>
                <button type="submit" class="btn" data-i18n="auth.verify.submit">Verify Token</button>
            </form>
            <p class="form-switch mobile-switch" data-i18n="auth.switch_to_resend">Need a new code? <a href="/auth/recover?action=otp">Resend OTP</a></p>
        </div>
    </div>

    <!-- RESET PASSWORD -->
    <div id="reset-form" class="form-container layout-right <?php echo ($action === 'reset') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/reset.png" alt="Reset password illustration">
            <p class="form-switch" data-i18n="auth.switch_to_login">Remember your password? <a href="/auth/authenticate?action=login">Login here.</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.reset.title">Reset Password</h2>
            <form action="/auth/resetPassword" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                <div class="input-wrapper">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:6px;">
                        <label for="new-password" style="margin-bottom:0;" data-i18n="auth.reset.new_password_label">New Password</label>
                        <button type="button" class="generate-password-btn" data-targets="new-password,confirm-password" data-i18n="auth.reset.generate_password">Generate</button>
                    </div>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="new-password" name="password" placeholder="New Password" autocomplete="new-password" data-validate="required|password" data-i18n-placeholder="auth.reset.new_password_placeholder">
                        <i class="fa-solid fa-eye-slash toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <span class="error-msg"></span>
                </div>
                <div class="input-wrapper">
                    <label for="confirm-password" data-i18n="auth.reset.confirm_password_label">Confirm Password</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password" data-validate="required|match" data-match="#new-password" data-i18n-placeholder="auth.reset.confirm_password_placeholder">
                        <i class="fa-solid fa-eye-slash toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <span class="error-msg"></span>
                </div>
                <button type="submit" class="btn" data-i18n="auth.reset.submit">Update Password</button>
            </form>
            <p class="form-switch mobile-switch" data-i18n="auth.switch_to_login">Remember your password? <a href="/auth/authenticate?action=login">Login here.</a></p>
        </div>
    </div>

    <script type="module" src="/assets/js/auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lang = localStorage.getItem('lang') || 'en';
            document.querySelectorAll('input[name="lang"]').forEach(el => el.value = lang);
        });
    </script>
</body>

</html>
