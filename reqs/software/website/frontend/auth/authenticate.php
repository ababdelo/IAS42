<?php
require_once __DIR__ . '/../../backend/includes/init.php';

$env = initAuthPage();
$action = $_GET['action'] ?? 'login';
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
$loginError = $formData['login_error'] ?? '';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-i18n="auth.page_title">IAS42: Authenticate</title>
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

    <!-- ===== LOGIN FORM ===== -->
    <div id="login-form" class="form-container layout-left <?php echo ($action === 'login') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/login.png" alt="Login illustration">
            <p class="form-switch" data-i18n="auth.switch_to_register">Don't have an account? <a href="?action=register">Register here.</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.login.title">Login</h2>
            <form action="/auth/login" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <div class="input-wrapper">
                    <label for="login-username" data-i18n="auth.login.username_label">Username :</label>
                    <div class="input">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="login-username" name="username" placeholder="Username" autocomplete="username" data-validate="required|username" data-i18n-placeholder="auth.login.username_placeholder" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                    </div>
                    <span class="error-msg"></span>
                </div>
                <div class="input-wrapper">
                    <label for="login-password" data-i18n="auth.login.password_label">Password :</label>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="login-password" name="password" placeholder="Password" autocomplete="current-password" data-validate="required" data-i18n-placeholder="auth.login.password_placeholder">
                        <i class="fa-solid fa-eye-slash toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <span class="error-msg"></span>
                    <?php if ($loginError !== ''): ?>
                        <span id="login-server-error" class="error-msg" data-message="<?php echo htmlspecialchars($loginError); ?>"></span>
                    <?php endif; ?>
                </div>
                <div class="login-options">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="remember" id="remember" <?php echo (!empty($formData['remember']) ? 'checked' : ''); ?>>
                        <label for="remember" data-i18n="auth.login.remember_me">Remember me</label>
                    </div>
                    <a href="/auth/recover?action=forgot" data-i18n="auth.login.forgot_password">Forgot Password?</a>
                </div>
                <button type="submit" class="btn" data-i18n="auth.login.submit">Sign In</button>
                <div class="divider"><span data-i18n="auth.common.or_continue_with">OR CONTINUE WITH</span></div>
                <div class="social-auth">
                    <a href="/auth/oauth?action=login&provider=google" class="google">
                        <img src="/assets/imgs/icons/google.webp" alt="Google">
                    </a>
                    <a href="/auth/oauth?action=login&provider=microsoft" class="microsoft">
                        <img src="/assets/imgs/icons/microsoft.webp" alt="Microsoft">
                    </a>
                </div>
            </form>
            <p class="form-switch mobile-switch" data-i18n="auth.switch_to_register">Don't have an account? <a href="?action=register">Register here.</a></p>
        </div>
    </div>

    <!-- ===== REGISTER FORM ===== -->
    <div id="register-form" class="form-container layout-right <?php echo ($action === 'register') ? 'active' : 'hidden'; ?>">
        <div class="illustration-side">
            <img src="/assets/imgs/illustrations/auth/register.png" alt="Register illustration">
            <p class="form-switch" data-i18n="auth.switch_to_login">Already have an account? <a href="?action=login">Login here.</a></p>
        </div>
        <div class="form-side">
            <h2 data-i18n="auth.register.title">Register</h2>
            <form action="/auth/register" method="post" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="lang" id="lang-input" value="en">
                <div class="form-row">
                    <div class="input-wrapper">
                        <label for="register-firstname" data-i18n="auth.register.first_name_label">First Name :</label>
                        <div class="input">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" id="register-firstname" name="first_name" placeholder="First Name" autocomplete="given-name" data-validate="required|firstname" data-i18n-placeholder="auth.register.first_name_placeholder" value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>">
                        </div>
                        <span class="error-msg"></span>
                    </div>
                    <div class="input-wrapper">
                        <label for="register-lastname" data-i18n="auth.register.last_name_label">Last Name :</label>
                        <div class="input">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" id="register-lastname" name="last_name" placeholder="Last Name" autocomplete="family-name" data-validate="required|lastname" data-i18n-placeholder="auth.register.last_name_placeholder" value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>">
                        </div>
                        <span class="error-msg"></span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="input-wrapper">
                        <label for="register-username" data-i18n="auth.register.username_label">Username :</label>
                        <div class="input">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" id="register-username" name="username" placeholder="Username" autocomplete="username" data-validate="required|username" data-i18n-placeholder="auth.register.username_placeholder" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                        </div>
                        <span class="error-msg"></span>
                    </div>
                    <div class="input-wrapper">
                        <label for="register-email" data-i18n="auth.register.email_label">Email :</label>
                        <div class="input">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" id="register-email" name="email" placeholder="Email" autocomplete="email" data-validate="required|email" data-i18n-placeholder="auth.register.email_placeholder" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                        </div>
                        <span class="error-msg"></span>
                    </div>
                </div>
                <fieldset class="gender-picker">
                    <legend data-i18n="auth.register.gender_label">Gender :</legend>
                    <div class="gender-options">
                        <label class="gender-option gender-option--male" title="Male">
                            <input type="radio" name="gender" value="male" <?php echo (($formData['gender'] ?? '') === 'male') ? 'checked' : ''; ?> aria-label="Male">
                            <span class="gender-icon"><i class="fa-solid fa-mars" aria-hidden="true"></i></span>
                            <span class="gender-label male" data-i18n="auth.register.gender_male">Male</span>
                        </label>
                        <label class="gender-option gender-option--unspecified" title="Prefer not to say">
                            <input type="radio" name="gender" value="not specified" <?php echo (($formData['gender'] ?? 'not specified') === 'not specified') ? 'checked' : ''; ?> aria-label="Prefer not to say">
                            <span class="gender-icon"><i class="fa-solid fa-question" aria-hidden="true"></i></span>
                            <span class="gender-label unspecified" data-i18n="auth.register.gender_not_specified">Prefer not to say</span>
                        </label>
                        <label class="gender-option gender-option--female" title="Female">
                            <input type="radio" name="gender" value="female" <?php echo (($formData['gender'] ?? '') === 'female') ? 'checked' : ''; ?> aria-label="Female">
                            <span class="gender-icon"><i class="fa-solid fa-venus" aria-hidden="true"></i></span>
                            <span class="gender-label female" data-i18n="auth.register.gender_female">Female</span>
                        </label>
                    </div>
                </fieldset>
                <div class="input-wrapper">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:6px;">
                        <label for="register-password" style="margin-bottom:0;" data-i18n="auth.register.password_label">Password :</label>
                        <button type="button" class="generate-password-btn" data-targets="register-password" data-i18n="auth.register.generate_password"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate</button>
                    </div>
                    <div class="input">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="register-password" name="password" placeholder="Password" autocomplete="new-password" data-validate="required|password" data-i18n-placeholder="auth.register.password_placeholder">
                        <i class="fa-solid fa-eye-slash toggle-password" aria-label="Toggle password visibility"></i>
                    </div>
                    <span class="error-msg"></span>
                </div>
                <div class="checkbox-wrapper terms-wrapper">
                    <input type="checkbox" name="terms" id="terms" <?php echo (!empty($formData['terms']) ? 'checked' : ''); ?>>
                    <label for="terms" data-i18n="auth.register.terms_accept">I agree to the <a href="/policies/terms">Terms Of Use</a> and <a href="/policies/privacy">Privacy Policy</a></label>
                    <span class="error-msg"></span>
                </div>
                <button type="submit" class="btn" data-i18n="auth.register.submit">Sign Up</button>
                <div class="divider"><span data-i18n="auth.common.or_continue_with">OR CONTINUE WITH</span></div>
                <div class="social-auth">
                    <a href="/auth/oauth?action=register&provider=google" class="google">
                        <img src="/assets/imgs/icons/google.webp" alt="Google">
                    </a>
                    <a href="/auth/oauth?action=register&provider=microsoft" class="microsoft">
                        <img src="/assets/imgs/icons/microsoft.webp" alt="Microsoft">
                    </a>
                </div>
            </form>
            <p class="form-switch mobile-switch" data-i18n="auth.switch_to_login">Already have an account? <a href="?action=login">Login here.</a></p>
        </div>
    </div>

    <script type="module" src="/assets/js/auth.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const oauthLinks = document.querySelectorAll('.social-auth a');
            const rememberCheckbox = document.getElementById('remember');
            oauthLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (rememberCheckbox && rememberCheckbox.checked) {
                        const url = new URL(this.href);
                        url.searchParams.set('remember', '1');
                        this.href = url.toString();
                    }
                });
            });
            const lang = localStorage.getItem('lang') || 'en';
            document.querySelectorAll('input[name="lang"]').forEach(el => el.value = lang);
            oauthLinks.forEach(link => {
                const url = new URL(link.href);
                url.searchParams.set('lang', lang);
                link.href = url.toString();
            });
        });
    </script>
</body>

</html>
