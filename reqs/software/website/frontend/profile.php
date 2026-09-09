<?php
require_once __DIR__ . '/../backend/includes/init.php';
requireAuthentication();

$pageTitle = 'profile';
$extraCss = '<link rel="stylesheet" href="/assets/css/profile.css">';

require_once __DIR__ . "/components/sideBar.php";
?>

<h1 class='main-title' data-i18n="pages.profile.subtitle"></h1>

<?php
require_once __DIR__ . "/components/footer.php";
?>
