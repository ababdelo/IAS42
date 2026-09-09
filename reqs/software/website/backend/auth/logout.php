<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../utils/helpers.php';

if (isset($_SESSION['user_id'])) {
    $conn = getDb();
    $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE user_id = ? AND tokenType = 'rememberMe'");
    $stmt->execute([$_SESSION['user_id']]);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

session_start();
recordLogs("User logged out successfully", 'info');
$_SESSION['success'] = 'auth.logout.success';
header('Location: /auth/authenticate?action=login');
exit();
