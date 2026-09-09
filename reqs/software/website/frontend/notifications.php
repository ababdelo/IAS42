<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$pageTitle = 'notifications';
$extraCss = '<link rel="stylesheet" href="/assets/css/notifications.css">';

require_once __DIR__ . "/components/sideBar.php";
?>

<h1 class='main-title' data-i18n="pages.notifications.subtitle"></h1>

<?php
require_once __DIR__ . "/components/footer.php";
?>
