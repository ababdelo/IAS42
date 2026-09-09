<?php
require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/authenticate?action=login');
    exit();
}

$env = initAuthPage();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/authenticate?action=login');

$supportedLanguages = ['en', 'fr', 'es', 'ar'];
$language = $_POST['lang'] ?? ($_SESSION['lang'] ?? 'en');
if (!in_array($language, $supportedLanguages, true)) {
    $language = 'en';
}
$_SESSION['lang'] = $language;

$conn = getDb();
$username = validate($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);
$redirectUrl = '/auth/authenticate?action=login';

checkEmptyFields(['username' => $username, 'password' => $password], $redirectUrl);

try {
    $stmt = $conn->prepare("
        SELECT id, username, email, password, accountStatus, first_name, last_name, avatar
        FROM USERS
        WHERE username = ?
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        recordLogs("Failed login attempt for username: {$username}", 'warning');
        $_SESSION['form_data'] = ['username' => $username, 'remember' => $remember];
        $_SESSION['error'] = 'auth.login.invalid';
        header("Location: $redirectUrl");
        exit();
    }

    if ($user['accountStatus'] !== 'verified') {
        recordLogs("Login attempted for unverified account: {$username}", 'warning');
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_verified'] = false;
        $_SESSION['warning'] = 'auth.login.unverified';
        header('Location: /auth/secure?action=verify');
        exit();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_verified'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['remember_me'] = $remember;

    if ($remember) {
        $tokenData = generateRememberTokenAndExpiry('+30 days');
        $token = $tokenData['token'];
        $expiresAt = $tokenData['expires_at'];

        $stmt = $conn->prepare("
            INSERT INTO AUTH_TOKENS (user_id, tokenType, tokenValue, expires_at)
            VALUES (?, 'rememberMe', ?, ?)
            ON DUPLICATE KEY UPDATE tokenValue = VALUES(tokenValue), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$user['id'], $token, $expiresAt]);

        setcookie('remember_token', $token, [
            'expires' => time() + (60 * 60 * 24 * 30),
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    } else {
        $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE user_id = ? AND tokenType = 'rememberMe'");
        $stmt->execute([$user['id']]);
        clearRememberCookie();
    }

    $intended = $_SESSION['intended_url'] ?? '/dashboard';
    unset($_SESSION['intended_url']);
    recordLogs("User successfully logged in: {$user['username']} (ID: {$user['id']})", 'info');
    $_SESSION['success'] = json_encode([
        'key' => 'auth.login.welcome',
        'params' => ['username' => htmlspecialchars($user['username'])]
    ]);
    header("Location: $intended");
    exit();
} catch (PDOException $e) {
    recordLogs("Login database error: " . $e->getMessage(), 'error');
    error_log("Login error: " . $e->getMessage());
    $_SESSION['form_data'] = ['username' => $username, 'remember' => $remember];
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectUrl");
    exit();
}
