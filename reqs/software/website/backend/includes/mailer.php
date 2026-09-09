<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../../composer/vendor/autoload.php';
require_once __DIR__ . '/credLoader.php';

function resolveEmailCategory(string $templateName): array
{
    $categories = [
        'register'        => ['label' => 'Security', 'prefix' => '[Security]'],
        'request'         => ['label' => 'Security', 'prefix' => '[Security]'],
        'forgot'          => ['label' => 'Support',  'prefix' => '[Support]'],
        'reseted'         => ['label' => 'Security', 'prefix' => '[Security]'],
        'verified'        => ['label' => 'Updates',  'prefix' => '[Updates]'],
        'delete'          => ['label' => 'Security', 'prefix' => '[Security]'],
        'deleted'         => ['label' => 'Updates',  'prefix' => '[Updates]'],
        'oauth_verified'  => ['label' => 'Updates',  'prefix' => '[Updates]'],
        'contact'         => ['label' => 'Contact',  'prefix' => '[Contact]'],
    ];
    return $categories[$templateName] ?? ['label' => 'Other', 'prefix' => '[Other]'];
}

function buildCategorizedSubject(string $subject, string $prefix): string
{
    $subject = trim($subject);
    if ($prefix !== '' && !str_starts_with($subject, $prefix)) {
        return $prefix . ' ' . $subject;
    }
    return $subject;
}

function resolveMailEncryption(string $encryption): string
{
    $encryption = strtolower(trim($encryption));
    if ($encryption === 'ssl' || $encryption === 'smtps') {
        return PHPMailer::ENCRYPTION_SMTPS;
    }
    return PHPMailer::ENCRYPTION_STARTTLS;
}

/**
 * Load all translation modules for a given language and merge them.
 * Returns the 'email' sub‑array (the email-specific translations) nested under 'email'.
 */
function loadEmailTranslations(string $lang): array
{
    $modules = ['global', 'validation', 'auth', 'notifier', 'email'];
    $merged = [];
    foreach ($modules as $module) {
        $data = [];
        $file = __DIR__ . "/../../assets/lang/{$lang}/{$module}.json";
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                } else {
                    error_log("Invalid JSON in module file: $file");
                }
            }
        } else {
            // Fallback to English
            $fallback = __DIR__ . "/../../assets/lang/en/{$module}.json";
            if (file_exists($fallback)) {
                $content = file_get_contents($fallback);
                if ($content !== false) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        $data = $decoded;
                    } else {
                        error_log("Invalid JSON in fallback module: $fallback");
                    }
                }
            }
        }
        // Store the module data under its module name
        $merged[$module] = $data;
    }
    // Return the 'email' part, ensuring it's an array
    $emailPart = $merged['email'] ?? [];
    if (!is_array($emailPart)) {
        error_log("Unexpected type for 'email' translations: " . gettype($emailPart) . ". Expected array.");
        $emailPart = [];
    }
    return $emailPart;
}

function sendAppEmail(string $toEmail, string $toName, string $templateName, string $subject, array $dynamicData = [], string $lang = 'en'): bool
{
    try {
        $env = loadEnv();
        date_default_timezone_set($env['APP_TIMEZONE'] ?? 'Africa/Casablanca');

        $mailHost = $env['MAIL_HOST'] ?? '';
        $mailPort = (int) ($env['MAIL_PORT'] ?? 587);
        $mailUser = $env['PASS_APP_MAIL'] ?? '';
        $mailPass = $env['PASS_APP_KEY'] ?? '';
        $mailEncryption = $env['MAIL_ENCRYPTION'] ?? 'tls';
        $websiteName = $env['WEBSITE_NAME'] ?? 'IAS42';

        if ($mailHost === '' || $mailPort <= 0 || $mailUser === '' || $mailPass === '') {
            throw new Exception('Mail configuration incomplete.');
        }

        $emailTranslations = loadEmailTranslations($lang);
        $category = resolveEmailCategory($templateName);
        $categorizedSubject = buildCategorizedSubject($subject, $category['prefix']);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Host = $mailHost;
        $mail->Hostname = $env['APP_DOMAIN'] ?? 'localhost';
        $mail->Port = $mailPort;
        $mail->SMTPAuth = true;
        $mail->Username = $mailUser;
        $mail->Password = $mailPass;
        $mail->SMTPSecure = resolveMailEncryption($mailEncryption);

        $mail->setFrom($mailUser, $websiteName);
        $mail->addAddress($toEmail, $toName);

        $mail->Subject = $categorizedSubject;
        $mail->addCustomHeader('X-Email-Category', $category['label']);
        $mail->addCustomHeader('X-Email-Category-Prefix', $category['prefix']);

        $dir = ($lang === 'en' || $lang === 'fr' || $lang === 'es') ? 'ltr' : 'rtl';
        $textAlign = ($lang === 'en' || $lang === 'fr' || $lang === 'es') ? 'left' : 'right';

        $globalData = [
            'wbstname'          => $websiteName,
            'sprtmail'          => $mailUser,
            'date'              => date('Y'),
            'mailCategory'      => $category['label'],
            'mailCategoryLabel' => $category['prefix'],
            'dir'               => $dir,
            'textAlign'         => $textAlign,
            'lang'              => $lang,
        ];
        $templateData = array_merge($globalData, $dynamicData);

        $templatePath = __DIR__ . '/../../frontend/templates/mails/' . $templateName . '.html';
        if (!file_exists($templatePath)) {
            error_log("Email template '$templateName' not found at $templatePath");
            return false;
        }

        $htmlBody = file_get_contents($templatePath);

        // Replace translation placeholders (e.g., {{email.register.greeting}})
        if (!empty($emailTranslations)) {
            // Flatten the email translations under the 'email' prefix
            $translationMap = [];
            flattenTranslations($emailTranslations, $translationMap, 'email');
            foreach ($translationMap as $key => $value) {
                $htmlBody = str_replace('{{' . $key . '}}', $value, $htmlBody);
            }
        }

        // Replace dynamic placeholders (e.g., {{fstname}}, {{otpToken}})
        foreach ($templateData as $key => $value) {
            $formattedKey = str_starts_with($key, '{{') ? $key : '{{' . $key . '}}';
            $htmlBody = str_replace($formattedKey, (string) $value, $htmlBody);
        }

        $logoPath = __DIR__ . '/../../assets/imgs/logo/logo.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'logo', 'logo.png');
        }

        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $htmlBody));

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mail Error: ' . $e->getMessage());
        return false;
    }
}

function flattenTranslations(array $array, array &$result, string $prefix = ''): void
{
    foreach ($array as $key => $value) {
        $newKey = $prefix . '.' . $key;
        if (is_array($value)) {
            flattenTranslations($value, $result, $newKey);
        } else {
            $result[$newKey] = $value;
        }
    }
}
