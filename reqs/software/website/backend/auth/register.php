<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /auth/authenticate?action=register');
    exit();
}

$env = initAuthPage();
verifyCsrfToken($_POST['csrf_token'] ?? '', '/auth/authenticate?action=register');

$conn = getDb();
$firstName = validate($_POST['first_name'] ?? '');
$lastName  = validate($_POST['last_name'] ?? '');
$username  = validate($_POST['username'] ?? '');
$email     = validate($_POST['email'] ?? '');
$gender    = $_POST['gender'] ?? 'not specified';
$password  = $_POST['password'] ?? '';
$terms     = isset($_POST['terms']);
$lang      = $_POST['lang'] ?? 'en';
$_SESSION['lang'] = $lang;

$redirectUrl = '/auth/authenticate?action=register';

checkEmptyFields([
    'first_name' => $firstName,
    'last_name'  => $lastName,
    'username'   => $username,
    'email'      => $email,
    'password'   => $password,
], $redirectUrl);

checkEmailFormat($email, $redirectUrl);
checkNameFormat($firstName, $redirectUrl);
checkNameFormat($lastName, $redirectUrl);
checkUsernameFormat($username, $redirectUrl);

if (!in_array($gender, ['male', 'female', 'not specified'], true)) {
    $gender = 'not specified';
}

$avatar = generateDefaultAvatar($gender);

$passwordError = checkPasswordSecurity($password);
if ($passwordError) {
    recordLogs("Registration failed for {$username}: weak password", 'warning');
    $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
    $_SESSION['warning'] = $passwordError;
    header("Location: $redirectUrl");
    exit();
}

if (!$terms) {
    recordLogs("Registration failed for {$username}: terms not accepted", 'warning');
    $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
    $_SESSION['warning'] = 'auth.register.terms_required';
    header("Location: $redirectUrl");
    exit();
}

if (checkUsername($username, $conn)) {
    recordLogs("Registration failed: username already taken - {$username}", 'warning');
    $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
    $_SESSION['warning'] = 'auth.register.username_taken';
    header("Location: $redirectUrl");
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id FROM USERS WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        recordLogs("Registration failed: email already exists - {$email}", 'warning');
        $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
        $_SESSION['warning'] = 'auth.register.email_exists';
        header("Location: $redirectUrl");
        exit();
    }
} catch (PDOException $e) {
    recordLogs("Registration email check failed: " . $e->getMessage(), 'error');
    error_log("Email check failed: " . $e->getMessage());
    $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectUrl");
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO USERS (first_name, last_name, username, email, gender, avatarType, avatar, password, termsAccepted, accountStatus)
        VALUES (?, ?, ?, ?, ?, 'default', ?, ?, ?, 'unverified')
    ");
    $stmt->execute([$firstName, $lastName, $username, $email, $gender, $avatar, $hashedPassword, 1]);
    $userId = $conn->lastInsertId();

    $otpData = generateOtpAndExpiry('+15 minutes');
    $otp = $otpData['otp'];
    $otpExpiry = $otpData['otpExpiry'];

    $stmt = $conn->prepare("
        INSERT INTO AUTH_TOKENS (user_id, tokenType, tokenValue, expires_at)
        VALUES (?, 'otp', ?, ?)
    ");
    $stmt->execute([$userId, $otp, $otpExpiry]);

    $conn->commit();

    $mailData = [
        'fstname'        => $firstName,
        'otpToken'       => $otp,
        'verifyUrl'      => appUrl('/auth/secure?action=verify', $env),
        'requestUrl'     => appUrl('/auth/recover?action=otp', $env),
        'expiryMinutes'  => '15'
    ];
    if (!sendAppEmail($email, $firstName, 'register', 'Verify Your IAS42 Account', $mailData, $lang)) {
        recordLogs("Registration warning for {$username}: email sending failed", 'warning');
        $_SESSION['warning'] = "auth.register.email_failed";
    } else {
        recordLogs("User successfully registered: {$username} (ID: {$userId}) - Verification email sent", 'info');
        $_SESSION['success'] = 'auth.register.success';
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['is_verified'] = false;
    header('Location: /auth/secure?action=verify');
    exit();
} catch (PDOException $e) {
    $conn->rollBack();
    recordLogs("Registration database error: " . $e->getMessage(), 'error');
    error_log("Registration error: " . $e->getMessage());
    $_SESSION['form_data'] = ['first_name' => $firstName, 'last_name' => $lastName, 'username' => $username, 'email' => $email, 'gender' => $gender, 'terms' => $terms];
    $_SESSION['error'] = 'auth.general.error';
    header("Location: $redirectUrl");
    exit();
}
