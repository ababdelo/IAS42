<?php
session_start();
require_once __DIR__ . '/../../backend/utils/checkers.php';
require_once __DIR__ . '/../../backend/utils/generators.php';
redirectIfAuthenticated();
generateCsrfToken();

$action = $_GET['action'] ?? 'forgot';
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="auth.page_title_recover">IAS42: Account Recovery</title>
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

    <!-- FORGOT PASSWORD -->
    <div id="forgot-form" class="form-container layout-left <?php echo ($action === 'forgot') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/forgot.png" alt="Forgot password illustration">
            <p class="form-switch" style="margin-top:8px;" data-i18n="auth.switch_to_login">Remember your password? <a href="/auth/authenticate?action=login">Login here.</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.forgot.title">Forgot Password</h2>
            <form action="/auth/forgotPassword" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <p class="form-instruction" data-i18n="auth.forgot.instruction">Enter your registered email address and we will send you a link to reset your password.</p>
                <div class="input-wrapper">
                    <label for="forgot-email" data-i18n="auth.common.email_label">Email Address</label>
                    <div class="input">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="forgot-email" name="email" placeholder="Enter your email" autocomplete="email" data-validate="required|email" data-i18n-placeholder="auth.common.email_placeholder" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                    </div>
                    <span class="error-msg"></span>
                </div>
                <button type="submit" class="btn" data-i18n="auth.forgot.submit">Send Reset Link</button>
            </form>
            <div class="mobile-switch" style="margin-top:24px; text-align:center;">
                <p class="form-switch" data-i18n="auth.switch_to_resend">Need a new verification code? <a href="?action=otp">Resend OTP</a></p>
                <p class="form-switch" style="margin-top:8px;" data-i18n="auth.switch_to_login">Remember your password? <a href="/auth/authenticate?action=login">Login here.</a></p>
            </div>
        </div>
    </div>

    <!-- RESEND OTP -->
    <div id="resend-otp-form" class="form-container layout-right <?php echo ($action === 'otp') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/resend.png" alt="Resend OTP illustration">
            <p class="form-switch" style="margin-top:8px;" data-i18n="auth.switch_to_login">Already verified? <a href="/auth/authenticate?action=login">Login here.</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.resend.title">Resend Verification Code</h2>
            <form action="/auth/resendOtp" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <p class="form-instruction" data-i18n="auth.resend.instruction">Enter your account email address to receive a fresh verification token.</p>
                <div class="input-wrapper">
                    <label for="resend-email" data-i18n="auth.common.email_label">Email Address</label>
                    <div class="input">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" id="resend-email" name="email" placeholder="Enter your email" autocomplete="email" data-validate="required|email" data-i18n-placeholder="auth.common.email_placeholder" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                    </div>
                    <span class="error-msg"></span>
                </div>
                <button type="submit" class="btn" data-i18n="auth.resend.submit">Send New Code</button>
            </form>
            <div class="mobile-switch" style="margin-top:24px; text-align:center;">
                <p class="form-switch" data-i18n="auth.switch_to_forgot">Forgot your password? <a href="?action=forgot">Reset password</a></p>
                <p class="form-switch" style="margin-top:8px;" data-i18n="auth.switch_to_login">Already verified? <a href="/auth/authenticate?action=login">Login here.</a></p>
            </div>
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
