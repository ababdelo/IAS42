<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$pageTitle = 'history';
$extraCss = '<link rel="stylesheet" href="/assets/css/history.css">';

require_once __DIR__ . "/components/sideBar.php";
?>

<h1 class='main-title' data-i18n="pages.history.subtitle"></h1>

<?php
require_once __DIR__ . "/components/footer.php";
?>
