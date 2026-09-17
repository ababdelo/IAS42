<?php

function getUserDetails(PDO $conn, int $userId): ?array
{
    try {
        $stmt = $conn->prepare(
            "SELECT id, username, email, first_name, last_name, avatarType, avatar, gender
             FROM USERS
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return [
                'error' =>
                    'Error: No available user. Please create an account first.'
            ];
        }

        $user['avatar'] = getAvatarPath($user);

        return $user;
    } catch (PDOException $e) {
        return [
            'error' =>
                'An error occurred. Please try again later.'
        ];
    }
}

function getAvatarPath(array $user): ?string
{
    $baseAvatarPath = '/assets/imgs/avatars/';
    $defaultAvatarPath =
        $baseAvatarPath . 'nouser.webp';

    $type =
        (string)($user['avatarType'] ?? 'default');

    $avatar =
        basename(
            (string)($user['avatar'] ?? 'nouser.webp')
        );

    if ($type === 'default') {
        if (
            $avatar === 'nouser.webp' ||
            preg_match(
                '/^[mf](?:[1-9]|[12][0-9]|3[0-2])\.webp$/',
                $avatar
            )
        ) {
            $avatarPath =
                __DIR__ .
                '/../../assets/imgs/avatars/default/' .
                $avatar;

            if (is_file($avatarPath)) {
                return
                    $baseAvatarPath .
                    'default/' .
                    rawurlencode($avatar);
            }
        }

        return $defaultAvatarPath;
    }

    if ($type === 'uploaded') {
        if (
            preg_match(
                '/^u[0-9]+_[a-f0-9]{16}\.(?:jpg|png|webp)$/',
                $avatar
            )
        ) {
            $avatarPath =
                __DIR__ .
                '/../../assets/imgs/avatars/uploads/' .
                $avatar;

            if (is_file($avatarPath)) {
                return
                    $baseAvatarPath .
                    'uploads/' .
                    rawurlencode($avatar);
            }
        }

        return $defaultAvatarPath;
    }

    if (
        $type === 'oauth' &&
        filter_var(
            (string)($user['avatar'] ?? ''),
            FILTER_VALIDATE_URL
        )
    ) {
        return (string)$user['avatar'];
    }

    return $defaultAvatarPath;
}

function getNavColor(&$index): string
{
    return '--nav-color: var(--first-color);';
}
