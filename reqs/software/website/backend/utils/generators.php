<?php

function generateResetTokenAndExpiry(string $expiryInterval = '+1 hour'): array
{
    return [
        'resetToken' => bin2hex(random_bytes(32)),
        'resetTokenExpiry' => date("Y-m-d H:i:s", strtotime($expiryInterval))
    ];
}

function generateOtpAndExpiry(string $expiryInterval = '+15 minutes'): array
{
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    return [
        'otp' => $otp,
        'otpDigits' => str_split($otp),
        'otpExpiry' => date("Y-m-d H:i:s", strtotime($expiryInterval))
    ];
}

function generateDeleteTokenAndExpiry(string $expiryInterval = '+24 hours'): array
{
    return [
        'deleteToken' => bin2hex(random_bytes(32)),
        'deleteTokenExpiry' => date("Y-m-d H:i:s", strtotime($expiryInterval))
    ];
}

function generateUsername(string $firstName, string $lastName, PDO $conn, int $maxLength = 8): string
{
    $lastName = str_replace(' ', '-', trim($lastName));
    $firstName = trim($firstName);
    while (true) {
        $firstPartLength = rand(1, min(3, max(1, strlen($firstName))));
        $lastPartLength = $maxLength - $firstPartLength;
        $lastPartLength = min($lastPartLength, max(1, strlen($lastName)));
        $firstPart = strtolower(substr($firstName, 0, $firstPartLength));
        $lastPart = strtolower(substr($lastName, 0, $lastPartLength));
        $username = $firstPart . $lastPart;
        if (strlen($username) < $maxLength) $username .= rand(10, 99);
        $username = substr($username, 0, $maxLength);
        $stmt = $conn->prepare("SELECT id FROM USERS WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) return $username;
    }
}

function generateRememberTokenAndExpiry(string $expiryInterval = '+30 days'): array
{
    return [
        'token' => bin2hex(random_bytes(32)),
        'expires_at' => date("Y-m-d H:i:s", strtotime($expiryInterval))
    ];
}

function generateDefaultAvatar(string $gender): string
{
    if ($gender === 'male') {
        return 'm' . random_int(1, 32) . '.webp';
    }

    if ($gender === 'female') {
        return 'f' . random_int(1, 32) . '.webp';
    }

    return 'nouser.webp';
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
