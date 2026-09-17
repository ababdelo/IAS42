<?php
declare(strict_types=1);

$allowedCodes = [400, 401, 403, 404, 408, 413, 500, 503];

$rawCode = $_SERVER['REDIRECT_STATUS']
    ?? $_SERVER['REDIRECT_REDIRECT_STATUS']
    ?? $_SERVER['REQUEST_STATUS']
    ?? '';

$statusCode = filter_var($rawCode, FILTER_VALIDATE_INT);
$statusCode = in_array($statusCode, $allowedCodes, true) ? $statusCode : 404;

http_response_code($statusCode);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#94c80b">
    <title>IAS42 : Error</title>
    <link rel="shortcut icon" href="/assets/imgs/logo/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/assets/css/errors.css">
    <link rel="stylesheet" href="/assets/css/fontAwesome.css">
</head>
<body>
    <main class="error-page" aria-labelledby="error-title">
        <a class="back-link" href="/" data-i18n="errors.back_to_home" aria-label="Back to home page">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span class="back-link-text">Back to home page</span>
        </a>

        <section class="error-content">
            <div class="illustration" aria-hidden="true">
                <img src="/assets/imgs/svg/ghost.svg" class="ghost" alt="">
                <div class="shadow-frame">
                    <img src="/assets/imgs/svg/shadow.svg" class="shadow" alt="">
                </div>
            </div>

            <div class="error-heading">
                <span class="error-code" aria-hidden="true"><?= htmlspecialchars((string) $statusCode, ENT_QUOTES, 'UTF-8') ?></span>
                <h1 id="error-title" class="error-title">Unknown Error</h1>
            </div>

            <p id="error-description" class="error-description">An unknown error has occurred.</p>
        </section>
    </main>

    <script>
        window.IAS42ErrorCode = <?= (int) $statusCode ?>;
    </script>
    <script src="/assets/js/errors.js" defer></script>
</body>
</html>
