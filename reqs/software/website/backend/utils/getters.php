<?php

/**
 * Fetches user details from the database based on the provided user ID.
 *
 * @param PDO $conn The PDO database connection object.
 * @param int $userId The ID of the user to fetch details for.
 * @return array|null Returns an associative array of user details if found, or null if not found or on error.
 */
function getUserDetails(PDO $conn, int $userId): ?array
{
    try {
        $stmt = $conn->prepare("SELECT id, username, email, first_name, last_name, avatarType, avatar, gender FROM USERS WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['avatar'] = getAvatarPath($user);
            return $user;
        } else {
            return ['error' => "Error: No available user. Please create an account first."];
        }
    } catch (PDOException $e) {
        return ['error' => "An error occurred. Please try again later."];
    }
}

/**
 * Determines the appropriate avatar path for a user based on their avatar type and value.
 *
 * @param array $user An associative array containing user details, including 'avatarType' and 'avatar'.
 * @return string|null Returns the avatar path as a string if valid, or null if no valid avatar is found.
 */
function getAvatarPath(array $user): ?string
{
    $baseAvatarPath = '/assets/imgs/avatars/';
    $defaultAvatarPath = $baseAvatarPath . 'nouser.webp';

    if ($user['avatarType'] === "default") {
        $avatarPath = __DIR__ . "/../.." . $baseAvatarPath . "default/" . $user['avatar'];
        return file_exists($avatarPath)
            ? $baseAvatarPath . "default/" . htmlspecialchars($user['avatar'])
            : $defaultAvatarPath;
    } elseif ($user['avatarType'] === "oauth") {
        return filter_var($user['avatar'], FILTER_VALIDATE_URL)
            ? htmlspecialchars($user['avatar'])
            : $defaultAvatarPath;
    }
    return $defaultAvatarPath;
}

/**
 * Returns the current brand color for a navigation section.
 *
 * @param int $index Kept for compatibility with existing sidebar callers.
 * @return string The CSS style string for the navigation color.
 */
function getNavColor(&$index): string
{
    return '--nav-color: var(--first-color);';
}
