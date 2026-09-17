<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
requireAuthentication();
header('Content-Type: application/json; charset=UTF-8');

function settingsJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestPayload(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') return $_POST;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function validPersonName(string $value): bool
{
    return preg_match('/^[\p{L}\s\-]+$/u', $value) === 1;
}

function validUsername(string $value): bool
{
    return preg_match('/^[\p{L}\p{N}_]+$/u', $value) === 1;
}

try {
    $conn = getDb();
    $userId = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $conn->prepare('SELECT id, first_name, last_name, username, email, gender, avatarType, avatar, accountType, accountStatus FROM USERS WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) settingsJson(['status' => 'error', 'message' => 'Account not found.'], 404);
        settingsJson(['status' => 'success', 'data' => $user]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        settingsJson(['status' => 'error', 'message' => 'Method not allowed.'], 405);
    }

    $payload = requestPayload();
    $csrf = (string)($payload['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    if (empty($_SESSION['csrf_token']) || empty($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        settingsJson(['status' => 'error', 'message' => 'Invalid security token.'], 403);
    }

    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));
    $username = trim((string)($payload['username'] ?? ''));

    if ($firstName === '' || $lastName === '' || $username === '') {
        settingsJson(['status' => 'error', 'message' => 'All profile fields are required.'], 422);
    }

    if (mb_strlen($firstName) > 42 || mb_strlen($lastName) > 42 || mb_strlen($username) > 42) {
        settingsJson(['status' => 'error', 'message' => 'One or more fields are too long.'], 422);
    }

    if (!validPersonName($firstName) || !validPersonName($lastName)) {
        settingsJson(['status' => 'error', 'message' => 'Names contain unsupported characters.'], 422);
    }

    if (!validUsername($username)) {
        settingsJson(['status' => 'error', 'message' => 'Username may contain letters, numbers, and underscores only.'], 422);
    }

    $usernameCheck = $conn->prepare('SELECT id FROM USERS WHERE username = ? AND id <> ? LIMIT 1');
    $usernameCheck->execute([$username, $userId]);
    if ($usernameCheck->fetch()) {
        settingsJson(['status' => 'error', 'message' => 'That username is already in use.'], 409);
    }

    $update = $conn->prepare('UPDATE USERS SET first_name = ?, last_name = ?, username = ? WHERE id = ?');
    $update->execute([$firstName, $lastName, $username, $userId]);

    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;

    recordLogs("Profile updated for user ID {$userId}.", 'info');

    settingsJson([
        'status' => 'success',
        'message' => 'Profile updated successfully.',
        'data' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
        ],
    ]);
} catch (Throwable $e) {
    error_log('Settings API error: ' . $e->getMessage());
    settingsJson([
        'status' => 'error',
        'message' => 'Failed to update settings.'
    ], 500);
}
