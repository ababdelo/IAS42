<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$pageTitle = 'analytics';
$extraCss = '<link rel="stylesheet" href="/assets/css/analytics.css">';

require_once __DIR__ . "/components/sideBar.php";
?>

<h1 class='main-title' data-i18n="pages.analytics.subtitle"></h1>

<?php
require_once __DIR__ . "/components/footer.php";
?>
