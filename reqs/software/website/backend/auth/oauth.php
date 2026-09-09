<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

$env = loadEnv();

if (isset($_GET['action'])) {
    $_SESSION['oauth_action'] = $_GET['action'];
}
if (isset($_GET['provider'])) {
    $_SESSION['oauth_method'] = $_GET['provider'];
}
if (isset($_GET['remember'])) {
    $_SESSION['oauth_remember'] = (int) $_GET['remember'];
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$action = $_SESSION['oauth_action'] ?? $_GET['action'] ?? 'login';
$method = $_SESSION['oauth_method'] ?? $_GET['method'] ?? 'google';

$redirectUrl = ($action === 'register') ? '/auth/authenticate?action=register' : '/auth/authenticate?action=login';

if ($method === 'microsoft') {
    recordLogs("OAuth login attempt with deprecated Microsoft provider", 'warning');
    $_SESSION['info'] = 'auth.oauth.microsoft_deprecated';
    unset($_SESSION['oauth_action'], $_SESSION['oauth_method'], $_SESSION['oauth_remember']);
    header("Location: $redirectUrl");
    exit();
}

if (isset($_GET['error'])) {
    recordLogs("OAuth authentication failed: {$_GET['error']}", 'warning');
    $_SESSION['error'] = 'auth.oauth.auth_failed';
    header("Location: $redirectUrl");
    exit();
}

require_once __DIR__ . '/../../../composer/vendor/autoload.php';
$client = new Google_Client();
$client->setClientId($env['OAUTH_CLIENT_ID'] ?? '');
$client->setClientSecret($env['OAUTH_CLIENT_SECRET'] ?? '');
$client->setRedirectUri(appUrl('/auth/oauth', $env));
$client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
$client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit();
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error']) || !isset($token['access_token'])) {
    recordLogs("OAuth token exchange error", 'error');
    $_SESSION['error'] = 'auth.oauth.auth_error';
    header("Location: $redirectUrl");
    exit();
}

$client->setAccessToken($token['access_token']);
$oauth2 = new Google_Service_Oauth2($client);
$userInfo = $oauth2->userinfo->get();

$acntid = $userInfo->id;
$email = filter_var($userInfo->email, FILTER_SANITIZE_EMAIL);
$name = filter_var($userInfo->name, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$nameParts = explode(' ', trim($name));
$fstname = $nameParts[0] ?? '';
$lstname = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
$avatar = filter_var($userInfo->picture ?? '', FILTER_VALIDATE_URL) ?: 'nouser.webp';

checkEmailFormat($email, $redirectUrl);

$conn = getDb();

if ($action === 'register') {
    $stmt = $conn->prepare("SELECT id, accountStatus FROM USERS WHERE email = ? OR oauthID = ?");
    $stmt->execute([$email, $acntid]);
    $existing = $stmt->fetch();
    if ($existing) {
        recordLogs("OAuth registration attempt with existing account: {$email}", 'warning');
        $_SESSION['warning'] = 'auth.oauth.already_registered';
        header("Location: /auth/authenticate?action=login");
        exit();
    }

    $username = generateUsername($fstname, $lstname, $conn);

    $stmt = $conn->prepare("INSERT INTO USERS (first_name, last_name, email, username, gender, avatarType, avatar, password, accountType, oauthID, accountStatus, termsAccepted) VALUES (?, ?, ?, ?, 'not specified', 'oauth', ?, NULL, 'oauth', ?, 'verified', 1)");
    if ($stmt->execute([$fstname, $lstname, $email, $username, $avatar, $acntid])) {
        $userId = $conn->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['oauth_id'] = $acntid;
        $_SESSION['is_verified'] = true;

        $stmt = $conn->prepare("SELECT username, email, first_name, last_name, avatar FROM USERS WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $mailData = [
            'dashboardUrl' => appUrl('/dashboard', $env),
            'fstname'      => $fstname,
            'accountType'  => 'Google'
        ];
        $lang = $_SESSION['lang'] ?? 'en';
        sendAppEmail($email, $fstname, 'oauth_verified', 'Welcome to IAS42!', $mailData, $lang);

        recordLogs("User successfully registered via OAuth: {$username} (ID: {$userId}, Email: {$email})", 'info');
        $_SESSION['success'] = json_encode([
            'key' => 'auth.oauth.login_success',
            'params' => ['username' => htmlspecialchars($username)]
        ]);
        $intended = $_SESSION['intended_url'] ?? '/dashboard';
        unset($_SESSION['intended_url']);
        unset($_SESSION['oauth_action'], $_SESSION['oauth_method'], $_SESSION['oauth_remember']);
        header("Location: $intended");
        exit();
    } else {
        recordLogs("OAuth registration failed: database insert error for {$email}", 'error');
        $_SESSION['error'] = 'auth.general.error';
        header("Location: $redirectUrl");
        exit();
    }
} else { // login
    $stmt = $conn->prepare("SELECT id, accountStatus, avatar FROM USERS WHERE oauthID = ?");
    $stmt->execute([$acntid]);
    $user = $stmt->fetch();
    if (!$user) {
        recordLogs("OAuth login attempt for non-existent account: {$email}", 'warning');
        $_SESSION['warning'] = 'auth.register.required';
        header("Location: /auth/authenticate?action=register");
        exit();
    }

    if ($user['accountStatus'] !== 'verified') {
        recordLogs("OAuth login attempt for unverified account: User ID {$user['id']}", 'warning');
        $_SESSION['warning'] = 'auth.login.unverified';
        header("Location: /auth/authenticate?action=login");
        exit();
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['oauth_id'] = $acntid;
    $_SESSION['is_verified'] = true;
    $_SESSION['last_activity'] = time();

    $stmt = $conn->prepare("SELECT username, email, first_name, last_name, avatar FROM USERS WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();

    $remember = $_SESSION['oauth_remember'] ?? 0;
    $_SESSION['remember_me'] = (bool) $remember;
    if ($remember) {
        $tokenData = generateRememberTokenAndExpiry('+30 days');
        $token = $tokenData['token'];
        $expiresAt = $tokenData['expires_at'];
        $stmt = $conn->prepare("INSERT INTO AUTH_TOKENS (user_id, tokenType, tokenValue, expires_at) VALUES (?, 'rememberMe', ?, ?) ON DUPLICATE KEY UPDATE tokenValue = VALUES(tokenValue), expires_at = VALUES(expires_at)");
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

    $_SESSION['success'] = json_encode([
        'key' => 'auth.login.welcome',
        'params' => ['username' => htmlspecialchars($userData['username'])]
    ]);

    recordLogs("User successfully logged in via OAuth: {$userData['username']} (ID: {$user['id']})", 'info');
    $intended = $_SESSION['intended_url'] ?? '/dashboard';
    unset($_SESSION['intended_url']);
    unset($_SESSION['oauth_action'], $_SESSION['oauth_method'], $_SESSION['oauth_remember']);
    header("Location: $intended");
    exit();
}
