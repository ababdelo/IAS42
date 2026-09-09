<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../utils/checkers.php';
require_once __DIR__ . '/../utils/generators.php';
require_once __DIR__ . '/../utils/getters.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/credLoader.php';
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/mailer.php';

function initAuthPage(): array
{
    redirectIfAuthenticated();
    generateCsrfToken();
    return loadEnv();
}

function displaySessionMessages(): void
{
    $error   = $_SESSION['error'] ?? '';
    $success = $_SESSION['success'] ?? '';
    $warning = $_SESSION['warning'] ?? '';
    $info    = $_SESSION['info'] ?? '';
?>
    <div id="session-messages"
        data-error="<?php echo htmlspecialchars($error); ?>"
        data-success="<?php echo htmlspecialchars($success); ?>"
        data-warning="<?php echo htmlspecialchars($warning); ?>"
        data-info="<?php echo htmlspecialchars($info); ?>">
    </div>
<?php
    unset($_SESSION['error'], $_SESSION['success'], $_SESSION['warning'], $_SESSION['info']);
}

function getDb(): PDO
{
    return getDbConnection();
}

function initializeAuthentication(): void
{
    $timeout = 42 * 60;

    if (isset($_SESSION['user_id'], $_SESSION['is_verified']) && $_SESSION['is_verified'] === true) {
        $lastActivity = $_SESSION['last_activity'] ?? time();
        if (time() - $lastActivity > $timeout) {
            $_SESSION = [];
        }
    }

    if (!isset($_SESSION['user_id'], $_SESSION['is_verified']) && !empty($_COOKIE['remember_token'])) {
        restoreRememberedSession($_COOKIE['remember_token']);
    }

    if (isset($_SESSION['user_id'], $_SESSION['is_verified']) && $_SESSION['is_verified'] === true) {
        $_SESSION['last_activity'] = time();
    }
}

function restoreRememberedSession(string $token): void
{
    try {
        $conn = getDb();
        $stmt = $conn->prepare(" 
            SELECT u.id
            FROM AUTH_TOKENS t
            INNER JOIN USERS u ON u.id = t.user_id
            WHERE t.tokenType = 'rememberMe'
              AND t.tokenValue = ?
              AND t.expires_at > NOW()
              AND u.accountStatus = 'verified'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = $conn->prepare("DELETE FROM AUTH_TOKENS WHERE tokenType = 'rememberMe' AND tokenValue = ?");
            $stmt->execute([$token]);
            clearRememberCookie();
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_verified'] = true;
        $_SESSION['last_activity'] = time();
    } catch (PDOException $e) {
        error_log('Remember-me restoration error: ' . $e->getMessage());
    }
}

function clearRememberCookie(): void
{
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

initializeAuthentication();
