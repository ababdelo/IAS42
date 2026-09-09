<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/recover?action=forgot');
    exit();
}

$env = initAuthPage();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/recover?action=forgot');

$conn = getDb();
$email = validate($_POST['email'] ?? '');
$lang  = $_POST['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$redirectUrl = '/auth/recover?action=forgot';

checkEmptyFields(['email' => $email], $redirectUrl);
checkEmailFormat($email, $redirectUrl);

try {
    $stmt = $conn->prepare("SELECT id, first_name, email FROM USERS WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        recordLogs("Password reset request for non-existent email: {$email}", 'warning');
        $_SESSION['form_data'] = ['email' => $email];
        $_SESSION['info'] = 'auth.forgot.sent';
        header("Location: $redirectUrl");
        exit();
    }

    $tokenData = generateResetTokenAndExpiry('+1 hour');
    $token = $tokenData['resetToken'];
    $expiresAt = $tokenData['resetTokenExpiry'];

    $stmt = $conn->prepare("
        INSERT INTO AUTH_TOKENS (user_id, tokenType, tokenValue, expires_at)
        VALUES (?, 'passwordReset', ?, ?)
        ON DUPLICATE KEY UPDATE tokenValue = VALUES(tokenValue), expires_at = VALUES(expires_at)
    ");
    $stmt->execute([$user['id'], $token, $expiresAt]);

    $resetLink = appUrl('/auth/secure?action=reset&token=' . urlencode($token), $env);
    $mailData = [
        'fstname'     => $user['first_name'],
        'resetUrl'    => $resetLink,
        'token'       => $token,
        'expiryHours' => '1'
    ];
    sendAppEmail($email, $user['first_name'], 'forgot', 'Reset Your Password', $mailData, $lang);

    recordLogs("Password reset email sent for user: {$user['first_name']} (Email: {$email})", 'info');
    $_SESSION['info'] = 'auth.forgot.sent';
    header("Location: $redirectUrl");
    exit();
} catch (PDOException $e) {
    recordLogs("Forgot password database error: " . $e->getMessage(), 'error');
    error_log("Forgot password error: " . $e->getMessage());
    $_SESSION['form_data'] = ['email' => $email];
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectUrl");
    exit();
}
