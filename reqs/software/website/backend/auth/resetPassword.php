<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/authenticate?action=login');
    exit();
}

$env = loadEnv();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/secure?action=reset');

$conn = getDb();
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$lang = $_SESSION['lang'] ?? 'en';
$redirectError = '/auth/secure?action=reset&token=' . urlencode($token);

checkResetToken($token, $redirectError);

$passwordError = checkPasswordSecurity($password);
if ($passwordError) {
    recordLogs("Password reset failed: weak password for token", 'warning');
    $_SESSION['warning'] = $passwordError;
    header("Location: $redirectError");
    exit();
}

if (!checkPasswordMatch($password, $confirmPassword)) {
    recordLogs("Password reset failed: password mismatch", 'warning');
    $_SESSION['warning'] = 'auth.reset.mismatch';
    header("Location: $redirectError");
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT user_id, expires_at FROM AUTH_TOKENS
        WHERE tokenValue = ? AND tokenType = 'passwordReset'
    ");
    $stmt->execute([$token]);
    $tokenRecord = $stmt->fetch();

    if (!$tokenRecord) {
        recordLogs("Password reset failed: invalid token", 'warning');
        $_SESSION['error'] = 'auth.reset.invalid_token';
        header('Location: /auth/recover?action=forgot');
        exit();
    }

    if (strtotime($tokenRecord['expires_at']) < time()) {
        $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE tokenValue = ? AND tokenType = 'passwordReset'");
        $stmt->execute([$token]);
        recordLogs("Password reset failed: expired token", 'warning');
        $_SESSION['error'] = 'auth.reset.expired_token';
        header('Location: /auth/recover?action=forgot');
        exit();
    }

    $userId = $tokenRecord['user_id'];
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE USERS SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);

    $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE tokenValue = ? AND tokenType = 'passwordReset'");
    $stmt->execute([$token]);
    $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE user_id = ? AND tokenType = 'passwordReset'");
    $stmt->execute([$userId]);

    $stmt = $conn->prepare("SELECT first_name, email FROM USERS WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if ($user) {
        $mailData = [
            'fstname'  => $user['first_name'],
            'loginUrl' => appUrl('/auth/authenticate?action=login', $env)
        ];
        recordLogs("User password successfully reset (ID: {$userId}, Email: {$user['email']})", 'info');
        sendAppEmail($user['email'], $user['first_name'], 'reseted', 'Password Reset Successful', $mailData, $lang);
    }

    $_SESSION['success'] = 'auth.reset.success';
    header('Location: /auth/authenticate?action=login');
    exit();
} catch (PDOException $e) {
    recordLogs("Password reset database error: " . $e->getMessage(), 'error');
    error_log("Reset password error: " . $e->getMessage());
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectError");
    exit();
}
