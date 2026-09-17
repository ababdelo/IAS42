<?php

declare(strict_types=1);

/**
 * Authenticate service-to-service API requests with a shared token.
 *
 * The token is read from the server-side .env file and is never returned
 * to clients. This is intended for the IAS42 MVP/mobile integration.
 */
function requireMobileApiToken(): void
{
    $env = loadEnv();
    $expectedToken = trim((string)($env['MOBILE_API_TOKEN'] ?? ''));

    if ($expectedToken === '') {
        error_log('Mobile API authentication error: MOBILE_API_TOKEN is not configured.');
        sendJsonResponse(
            ['status' => 'error', 'message' => 'Mobile API is not configured.'],
            500
        );
    }

    $providedToken = getBearerToken();

    if ($providedToken === '') {
        $providedToken = trim((string)($_SERVER['HTTP_X_IAS42_API_KEY'] ?? ''));
    }

    if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
        sendJsonResponse(
            ['status' => 'error', 'message' => 'Unauthorized.'],
            401
        );
    }
}

function getBearerToken(): string
{
    $authorization = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));

    if ($authorization === '') {
        return '';
    }

    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) !== 1) {
        return '';
    }

    return trim($matches[1]);
}

function sendJsonResponse(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}
