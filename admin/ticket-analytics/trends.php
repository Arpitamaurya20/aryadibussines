<?php
session_start();
include('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
SessionCheck();
setTimeZone();
setNavigation($_SESSION['Roles']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket Trends Analytics</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="mod-bg-1 mod-nav-link">
<div class="page-wrapper"><div class="page-inner">
<?php include('../includes/common_header.php'); ?>
<main id="js-page-content" role="main" class="page-content">
    <div class="d-flex justify-content-between mb-3">
        <div><h2 class="ta-page-title">Ticket Trends</h2><div class="ta-muted">Historical trend lens for leadership review</div></div>
        <a class="btn btn-outline-primary btn-sm" href="index.php">Back to CFO Dashboard</a>
    </div>
    <div class="row mb-2">
        <div class="col-md-2"><input id="start_date" type="date" class="form-control"></div>
        <div class="col-md-2"><input id="end_date" type="date" class="form-control"></div>
        <div class="col-md-2"><select id="ticket_type" class="form-control"><option value="ALL">All</option><option>PPM</option><option>R&amp;M</option><option>Projects</option><option>Supply</option><option>AMC Breakdown</option></select></div>
        <div class="col-md-2"><input id="status" class="form-control" value="ALL"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block" onclick="taLoadAll()">Refresh</button></div>
    </div>
    <div class="row">
        <div class="col-md-8"><div class="ta-chart-card"><h5>Monthly Opened vs Closed</h5><canvas id="trend_chart" height="95"></canvas></div></div>
        <div class="col-md-4"><div class="ta-chart-card"><h5>Type Breakdown</h5><canvas id="type_chart" height="95"></canvas></div></div>
    </div>
</main>
<?php include('../includes/common_footer.php'); ?>
</div></div>
<?php include('../includes/common_scripts.php'); ?>
<script src="assets/js/dashboard.js"></script>
<script>
const now = new Date();
const end = now.toISOString().slice(0, 10);
const startD = new Date(now); startD.setMonth(now.getMonth() - 6);
document.getElementById("start_date").value = startD.toISOString().slice(0, 10);
document.getElementById("end_date").value = end;
taLoadAll();
</script>
</body>
</html>
