<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
requireAuthentication();

header('Content-Type: application/json; charset=UTF-8');

function profileJson(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function profileRequestValue(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function validProfileName(string $value): bool
{
    return preg_match('/^[\p{L}\s\-\']+$/u', $value) === 1;
}

function validProfileUsername(string $value): bool
{
    return preg_match('/^[\p{L}\p{N}_]+$/u', $value) === 1;
}

function allowedDefaultAvatar(string $avatar): bool
{
    return preg_match(
        '/^[mf](?:[1-9]|[12][0-9]|3[0-2])\.webp$/',
        $avatar
    ) === 1 || $avatar === 'nouser.webp';
}

function avatarUploadErrorKey(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE => 'profile.errors.avatar_too_large',

        UPLOAD_ERR_PARTIAL =>
            'profile.errors.avatar_upload_incomplete',

        UPLOAD_ERR_NO_FILE =>
            'profile.errors.avatar_missing',

        default =>
            'profile.errors.avatar_upload_failed',
    };
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.method'
            ],
            405
        );
    }

    $csrf = (string)(
        $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? ''
    );

    if (
        empty($_SESSION['csrf_token']) ||
        $csrf === '' ||
        !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.csrf'
            ],
            403
        );
    }

    $conn = getDb();
    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare(
        'SELECT id, first_name, last_name, username, email, gender, avatarType, avatar, password, accountType
         FROM USERS
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.account_not_found'
            ],
            404
        );
    }

    $firstName = profileRequestValue('first_name');
    $lastName = profileRequestValue('last_name');
    $username = profileRequestValue('username');

    $currentPassword = (string)(
        $_POST['current_password'] ?? ''
    );

    $newPassword = (string)(
        $_POST['new_password'] ?? ''
    );

    $confirmPassword = (string)(
        $_POST['confirm_password'] ?? ''
    );

    $defaultAvatar = profileRequestValue('default_avatar');

    if (
        $firstName === '' ||
        $lastName === '' ||
        $username === ''
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.required'
            ],
            422
        );
    }

    if (
        mb_strlen($firstName) > 42 ||
        mb_strlen($lastName) > 42 ||
        mb_strlen($username) > 42
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.too_long'
            ],
            422
        );
    }

    if (
        mb_strlen($firstName) < 2 ||
        !validProfileName($firstName)
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.first_name'
            ],
            422
        );
    }

    if (
        mb_strlen($lastName) < 2 ||
        !validProfileName($lastName)
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.last_name'
            ],
            422
        );
    }

    if (
        mb_strlen($username) < 3 ||
        !validProfileUsername($username)
    ) {
        profileJson(
            [
                'status' => 'error',
                'message_key' => 'profile.errors.username'
            ],
            422
        );
    }

    $usernameChanged =
        $username !== (string)$user['username'];

    $nameChanged =
        $firstName !== (string)$user['first_name'] ||
        $lastName !== (string)$user['last_name'];

    if ($usernameChanged) {
        $usernameCheck = $conn->prepare(
            'SELECT id
             FROM USERS
             WHERE username = ?
             AND id <> ?
             LIMIT 1'
        );

        $usernameCheck->execute([
            $username,
            $userId
        ]);

        if ($usernameCheck->fetch()) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.username_taken'
                ],
                409
            );
        }
    }

    $passwordRequested =
        $newPassword !== '' ||
        $confirmPassword !== '' ||
        $currentPassword !== '';

    if ($passwordRequested) {
        if (
            $newPassword === '' ||
            $confirmPassword === ''
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.password_required'
                ],
                422
            );
        }

        if ($newPassword !== $confirmPassword) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.password_match'
                ],
                422
            );
        }

        $securityError =
            checkPasswordSecurity($newPassword);

        if ($securityError !== null) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.password_security'
                ],
                422
            );
        }

        if (!empty($user['password'])) {
            if (
                $currentPassword === '' ||
                !password_verify(
                    $currentPassword,
                    (string)$user['password']
                )
            ) {
                profileJson(
                    [
                        'status' => 'error',
                        'message_key' =>
                            'profile.errors.current_password'
                    ],
                    422
                );
            }

            if (
                password_verify(
                    $newPassword,
                    (string)$user['password']
                )
            ) {
                profileJson(
                    [
                        'status' => 'error',
                        'message_key' =>
                            'profile.errors.password_same'
                    ],
                    422
                );
            }
        }
    }

    $avatarChanged = false;

    $avatarType =
        (string)$user['avatarType'];

    $avatarValue =
        (string)$user['avatar'];

    $newUploadedFilename = null;
    $oldUploadedPath = null;

    if (
        isset($_FILES['avatar_file']) &&
        (
            $_FILES['avatar_file']['error']
            ?? UPLOAD_ERR_NO_FILE
        ) !== UPLOAD_ERR_NO_FILE
    ) {
        $uploadError =
            (int)(
                $_FILES['avatar_file']['error']
                ?? UPLOAD_ERR_NO_FILE
            );

        if ($uploadError !== UPLOAD_ERR_OK) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        avatarUploadErrorKey($uploadError)
                ],
                422
            );
        }

        $file = $_FILES['avatar_file'];

        if (
            (int)$file['size'] >
            2 * 1024 * 1024
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_too_large'
                ],
                422
            );
        }

        if (
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_upload_failed'
                ],
                422
            );
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = $finfo
            ? finfo_file(
                $finfo,
                $file['tmp_name']
            )
            : false;

        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];

        if (
            !is_string($mime) ||
            !isset($allowedMimes[$mime])
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_type'
                ],
                422
            );
        }

        if (
            @getimagesize($file['tmp_name']) === false
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_type'
                ],
                422
            );
        }

        $uploadDir =
            rtrim(
                $_SERVER['DOCUMENT_ROOT'] ?? '',
                '/'
            ) .
            '/assets/imgs/avatars/uploads/';

        if (
            $uploadDir ===
            '/assets/imgs/avatars/uploads/'
        ) {
            $uploadDir =
                dirname(__DIR__, 2) .
                '/assets/imgs/avatars/uploads/';
        }

        if (
            !is_dir($uploadDir) &&
            !mkdir(
                $uploadDir,
                0755,
                true
            ) &&
            !is_dir($uploadDir)
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_upload_failed'
                ],
                500
            );
        }

        $newUploadedFilename =
            'u' .
            $userId .
            '_' .
            bin2hex(random_bytes(8)) .
            '.' .
            $allowedMimes[$mime];

        $destination =
            $uploadDir .
            $newUploadedFilename;

        if (
            !move_uploaded_file(
                $file['tmp_name'],
                $destination
            )
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_upload_failed'
                ],
                500
            );
        }

        $avatarChanged =
            $avatarType !== 'uploaded' ||
            $avatarValue !== $newUploadedFilename;

        $avatarType = 'uploaded';
        $avatarValue = $newUploadedFilename;

        if (
            ($user['avatarType'] ?? '') ===
            'uploaded' &&
            !empty($user['avatar'])
        ) {
            $oldUploadedPath =
                $uploadDir .
                basename((string)$user['avatar']);
        }
    } elseif ($defaultAvatar !== '') {
        if (
            !allowedDefaultAvatar($defaultAvatar)
        ) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_invalid'
                ],
                422
            );
        }

        $defaultPath =
            dirname(__DIR__, 2) .
            '/assets/imgs/avatars/default/' .
            $defaultAvatar;

        if (!is_file($defaultPath)) {
            profileJson(
                [
                    'status' => 'error',
                    'message_key' =>
                        'profile.errors.avatar_invalid'
                ],
                422
            );
        }

        $avatarChanged =
            $avatarType !== 'default' ||
            $avatarValue !== $defaultAvatar;

        $avatarType = 'default';
        $avatarValue = $defaultAvatar;

        if (
            ($user['avatarType'] ?? '') ===
            'uploaded' &&
            !empty($user['avatar'])
        ) {
            $uploadDir =
                rtrim(
                    $_SERVER['DOCUMENT_ROOT'] ?? '',
                    '/'
                ) .
                '/assets/imgs/avatars/uploads/';

            if (
                $uploadDir ===
                '/assets/imgs/avatars/uploads/'
            ) {
                $uploadDir =
                    dirname(__DIR__, 2) .
                    '/assets/imgs/avatars/uploads/';
            }

            $oldUploadedPath =
                $uploadDir .
                basename((string)$user['avatar']);
        }
    }

    if (
        !$nameChanged &&
        !$usernameChanged &&
        !$passwordRequested &&
        !$avatarChanged
    ) {
        profileJson([
            'status' => 'success',
            'changed' => false,
            'message_key' =>
                'profile.messages.no_changes'
        ]);
    }

    $updates = [];
    $params = [];

    if (
        $nameChanged ||
        $usernameChanged
    ) {
        $updates[] = 'first_name = ?';
        $params[] = $firstName;

        $updates[] = 'last_name = ?';
        $params[] = $lastName;

        $updates[] = 'username = ?';
        $params[] = $username;
    }

    if ($passwordRequested) {
        $updates[] = 'password = ?';
        $params[] =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );

        if (
            ($user['accountType'] ?? 'normal')
            === 'oauth'
        ) {
            $updates[] = 'accountType = ?';
            $params[] = 'normal';
        }
    }

    if ($avatarChanged) {
        $updates[] = 'avatarType = ?';
        $params[] = $avatarType;

        $updates[] = 'avatar = ?';
        $params[] = $avatarValue;
    }

    $params[] = $userId;

    $conn->beginTransaction();

    try {
        $update = $conn->prepare(
            'UPDATE USERS SET ' .
            implode(', ', $updates) .
            ' WHERE id = ?'
        );

        $update->execute($params);

        $conn->commit();
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        if ($newUploadedFilename !== null) {
            $cleanupDir =
                rtrim(
                    $_SERVER['DOCUMENT_ROOT'] ?? '',
                    '/'
                ) .
                '/assets/imgs/avatars/uploads/';

            if (
                $cleanupDir ===
                '/assets/imgs/avatars/uploads/'
            ) {
                $cleanupDir =
                    dirname(__DIR__, 2) .
                    '/assets/imgs/avatars/uploads/';
            }

            $cleanupPath =
                $cleanupDir .
                basename($newUploadedFilename);

            if (is_file($cleanupPath)) {
                @unlink($cleanupPath);
            }
        }

        throw $e;
    }

    if (
        $oldUploadedPath !== null &&
        is_file($oldUploadedPath)
    ) {
        @unlink($oldUploadedPath);
    }

    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;

    $resolvedAvatar = getAvatarPath([
        'avatarType' => $avatarType,
        'avatar' => $avatarValue
    ]);

    $_SESSION['avatar'] = $resolvedAvatar;

    recordLogs(
        "Profile updated for user ID {$userId}.",
        'info'
    );

    profileJson([
        'status' => 'success',
        'changed' => true,
        'message_key' =>
            'profile.messages.saved',
        'data' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'avatar' => $resolvedAvatar
        ]
    ]);
} catch (Throwable $e) {
    error_log(
        'Profile API error: ' .
        $e->getMessage()
    );

    profileJson(
        [
            'status' => 'error',
            'message_key' =>
                'profile.errors.generic'
        ],
        500
    );
}
