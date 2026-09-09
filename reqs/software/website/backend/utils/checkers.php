<?php

function validate(string $data): string
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function checkEmptyFields(array $fields, string $redirectUrl): void
{
    foreach ($fields as $field => $value) {
        if (empty($value)) {
            $_SESSION['warning'] = 'validation.required';
            header("Location: $redirectUrl");
            exit();
        }
    }
}

function checkEmailFormat(string $email, string $redirectUrl): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['warning'] = 'validation.email';
        header("Location: $redirectUrl");
        exit();
    }
}

function checkPasswordSecurity(string $password): ?string
{
    if (strlen($password) < 8) return 'validation.password.length';
    if (!preg_match('/[A-Z]/', $password)) return 'validation.password.uppercase';
    if (!preg_match('/[a-z]/', $password)) return 'validation.password.lowercase';
    if (!preg_match('/\d/', $password)) return 'validation.password.digit';
    if (!preg_match('/[\W_]/', $password)) return 'validation.password.special';
    return null;
}

function checkPasswordMatch(string $password, string $confirmPassword): bool
{
    return $password === $confirmPassword;
}

function checkOTPFormat(string $otp): ?string
{
    if (!preg_match('/^\d{6}$/', $otp)) return 'validation.otp';
    return null;
}

function checkResetToken(string $token, string $redirectUrl): void
{
    if (empty($token)) {
        $_SESSION['error'] = 'auth.reset.invalid_token';
        header("Location: $redirectUrl");
        exit();
    }
}

function checkDatabaseConnection(PDO $conn, string $redirectUrl): void
{
    try {
        $conn->query("SELECT 1");
    } catch (PDOException $e) {
        $_SESSION['error'] = 'auth.general.error';
        header("Location: $redirectUrl");
        exit();
    }
}

function verifyCsrfToken(string $token, string $redirectUrl): void
{
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['error'] = 'auth.general.csrf';
        header("Location: $redirectUrl");
        exit();
    }
}

function redirectIfAuthenticated(): void
{
    if (isset($_SESSION['user_id'], $_SESSION['is_verified']) && $_SESSION['is_verified'] === true) {
        header("Location: /dashboard");
        exit();
    }
}

function requireAuthentication(?string $pageName = null): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['is_verified']) || $_SESSION['is_verified'] !== true) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['info'] = 'auth.login.required';
        header("Location: /auth/authenticate?action=login");
        exit();
    }
}

function checkUsername(string $username, PDO $conn): bool
{
    try {
        $stmt = $conn->prepare("SELECT 1 FROM USERS WHERE username = ?");
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    } catch (PDOException $e) {
        error_log("checkUsername error: " . $e->getMessage());
        return true;
    }
}

function checkNameFormat(string $name, string $redirectUrl): void
{
    if (!preg_match('/^[\p{L}\s\-]+$/u', $name)) {
        $_SESSION['warning'] = 'validation.firstname.characters';
        header("Location: $redirectUrl");
        exit();
    }
}

function checkUsernameFormat(string $username, string $redirectUrl): void
{
    if (!preg_match('/^[\p{L}\p{N}_]+$/u', $username)) {
        $_SESSION['warning'] = 'validation.username.characters';
        header("Location: $redirectUrl");
        exit();
    }
}

function isActive(string $desiredPage, string $currentPage): string
{
    return $desiredPage === $currentPage ? 'active-link' : '';
}

function requirePostMethod(string $redirectUrl): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header("Location: $redirectUrl");
        exit();
    }
}
