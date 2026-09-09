<?php

/**
 * Load environment variables from a .env file.
 *
 * @param string $filePath Path to the .env file.
 * @return array Associative array of env vars.
 */
function loadEnv(string $filePath = '/var/www/env/.env'): array {
    $env = [];
    if (!is_readable($filePath)) {
        return $env;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '') continue;

        // Remove surrounding quotes
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
        $env[$key] = $value;
    }
    return $env;
}

/**
 * Build a full application URL.
 *
 * @param string $path Relative path.
 * @param array|null $env Environment array (optional).
 * @return string Full URL.
 */
function appUrl(string $path = '', ?array $env = null): string {
    if ($env === null) {
        $env = loadEnv();
    }
    $baseUrl = rtrim($env['WEBSITE_URL'] ?? '', '/');
    if ($baseUrl === '') {
        $domain = $env['APP_DOMAIN'] ?? 'localhost';
        $baseUrl = 'https://' . $domain;
    }
    if ($path === '') {
        return $baseUrl;
    }
    return $baseUrl . '/' . ltrim($path, '/');
}
