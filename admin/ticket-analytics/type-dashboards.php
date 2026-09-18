<?php
session_start();
include('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
require_once __DIR__ . '/inc/executive_dashboard_config.php';

$UserType = SessionCheck();
setTimeZone();
setNavigation($_SESSION['Roles']);
$ProductName = 'Aryadibusiness';
$taDashboardCatalog = ta_executive_dashboard_catalog();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket Type Dashboards</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .ta-hub-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:14px; }
        .ta-hub-card {
            border-radius:12px; padding:18px; color:#fff; min-height:130px;
            box-shadow:0 4px 14px rgba(15,23,42,.15); text-decoration:none; display:block;
        }
        .ta-hub-card:hover { transform:translateY(-2px); color:#fff; text-decoration:none; }
        .ta-hub-card h3 { font-size:20px; font-weight:700; margin:0 0 6px; }
        .ta-hub-card p { margin:0; font-size:13px; opacity:.92; }
    </style>
</head>
<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
<?php include('../js/theme_settings.js'); ?>
<div class="page-wrapper">
    <div class="page-inner">
        <?php include('../navigation/admin_navigation.php'); ?>
        <div class="page-content-wrapper">
            <?php include('../includes/common_header.php'); ?>
            <main id="js-page-content" role="main" class="page-content ta-analytics-page">
    <ol class="breadcrumb page-breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0);"><?php echo $ProductName; ?></a></li>
        <li class="breadcrumb-item active">Ticket dashboards</li>
    </ol>
    <section class="ta-topbar mb-3">
        <div>
            <h1 class="ta-page-title">Ticket type dashboards</h1>
            <div class="ta-muted">Same executive view as R&amp;M — one page per ticket type (March → today by default)</div>
        </div>
        <div class="ta-topbar-actions">
            <a class="btn btn-success btn-sm" href="wallboard/live-screen.php" target="_blank">Live TV screen</a>
            <a class="btn btn-outline-secondary btn-sm" href="index.php">Combined analytics</a>
            <a class="btn btn-outline-secondary btn-sm" href="ticket-dashboard.php">Legacy type picker</a>
        </div>
    </section>
    <div class="ta-hub-grid">
<?php foreach ($taDashboardCatalog as $item) {
    $accent = htmlspecialchars($item['accent'], ENT_QUOTES, 'UTF-8');
    $page = htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8');
    ?>
        <a class="ta-hub-card" href="<?php echo $page; ?>" style="background:linear-gradient(135deg, <?php echo $accent; ?> 0%, <?php echo $accent; ?>cc 100%);">
            <h3><?php echo $title; ?></h3>
            <p><?php echo $desc; ?></p>
        </a>
<?php } ?>
    </div>
</main>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_scripts.php'); ?>
</body>
</html>
