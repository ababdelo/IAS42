<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/recover?action=otp');
    exit();
}

$env = initAuthPage();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/recover?action=otp');

$conn = getDb();
$email = validate($_POST['email'] ?? '');
$lang  = $_POST['lang'] ?? 'en';
$_SESSION['lang'] = $lang;
$redirectUrl = '/auth/recover?action=otp';

checkEmptyFields(['email' => $email], $redirectUrl);
checkEmailFormat($email, $redirectUrl);

try {
    $stmt = $conn->prepare("SELECT id, first_name, email, accountStatus FROM USERS WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        recordLogs("OTP resend requested for non-existent email: {$email}", 'warning');
        $_SESSION['info'] = 'auth.forgot.sent';
        header("Location: $redirectUrl");
        exit();
    }

    if ($user['accountStatus'] === 'verified') {
        recordLogs("OTP resend requested for already verified account: {$email}", 'warning');
        $_SESSION['info'] = 'auth.login.already_verified';
        header('Location: /auth/authenticate?action=login');
        exit();
    }

    $otpData = generateOtpAndExpiry('+15 minutes');
    $otp = $otpData['otp'];
    $otpExpiry = $otpData['otpExpiry'];

    $stmt = $conn->prepare("
        INSERT INTO AUTH_TOKENS (user_id, tokenType, tokenValue, expires_at)
        VALUES (?, 'otp', ?, ?)
        ON DUPLICATE KEY UPDATE tokenValue = VALUES(tokenValue), expires_at = VALUES(expires_at)
    ");
    $stmt->execute([$user['id'], $otp, $otpExpiry]);

    $mailData = [
        'fstname'        => $user['first_name'],
        'otpToken'       => $otp,
        'verifyUrl'      => appUrl('/auth/secure?action=verify', $env),
        'requestUrl'     => appUrl('/auth/recover?action=otp', $env),
        'expiryMinutes'  => '15'
    ];
    sendAppEmail($email, $user['first_name'], 'request', 'Verify Your IAS42 Account', $mailData, $lang);

    recordLogs("OTP successfully resent for user: {$user['first_name']} (Email: {$email}, ID: {$user['id']})", 'info');
    $_SESSION['info'] = 'auth.resend.sent';
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_verified'] = false;
    header('Location: /auth/secure?action=verify');
    exit();
} catch (PDOException $e) {
    recordLogs("Resend OTP database error: " . $e->getMessage(), 'error');
    error_log("Resend OTP error: " . $e->getMessage());
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectUrl");
    exit();
}
