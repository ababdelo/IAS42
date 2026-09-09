<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/secure?action=verify');
    exit();
}

$env = initAuthPage();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/secure?action=verify');

$conn = getDb();
$otp = $_POST['otp_token'] ?? '';
$lang = $_SESSION['lang'] ?? 'en';
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    $_SESSION['error'] = 'auth.verify.session_expired';
    header('Location: /auth/authenticate?action=register');
    exit();
}

$otpError = checkOTPFormat($otp);
if ($otpError) {
    $_SESSION['warning'] = $otpError;
    header('Location: /auth/secure?action=verify');
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT id, expires_at FROM AUTH_TOKENS
        WHERE user_id = ? AND tokenType = 'otp' AND tokenValue = ?
    ");
    $stmt->execute([$userId, $otp]);
    $tokenRecord = $stmt->fetch();

    if (!$tokenRecord) {
        recordLogs("Account verification failed: invalid OTP for user ID {$userId}", 'warning');
        $_SESSION['warning'] = 'auth.verify.invalid_code';
        header('Location: /auth/secure?action=verify');
        exit();
    }

    if (strtotime($tokenRecord['expires_at']) < time()) {
        recordLogs("Account verification failed: expired OTP for user ID {$userId}", 'warning');
        $_SESSION['warning'] = 'auth.verify.expired_code';
        header('Location: /auth/recover?action=otp');
        exit();
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE USERS SET accountStatus = 'verified' WHERE id = ?");
    $stmt->execute([$userId]);

    $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE id = ?");
    $stmt->execute([$tokenRecord['id']]);

    $conn->commit();

    $stmt = $conn->prepare("SELECT username, email, first_name, last_name, avatar FROM USERS WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user) {
        $mailData = [
            'fstname'      => $user['first_name'],
            'dashboardUrl' => appUrl('/dashboard', $env)
        ];
        sendAppEmail($user['email'], $user['first_name'], 'verified', 'Account Verified', $mailData, $lang);

        recordLogs("User account successfully verified (ID: {$userId}, Email: {$user['email']})", 'info');
    }

    $_SESSION['is_verified'] = true;
    $_SESSION['success'] = 'auth.verify.success';
    header('Location: /dashboard');
    exit();
} catch (PDOException $e) {
    recordLogs("Account verification database error: " . $e->getMessage(), 'error');
    $conn->rollBack();
    error_log("OTP verification error: " . $e->getMessage());
    $_SESSION['error'] = 'auth.general.error';
    header('Location: /auth/secure?action=verify');
    exit();
}
