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
    <title>Ticket Drilldown Analytics</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="mod-bg-1 mod-nav-link">
<div class="page-wrapper"><div class="page-inner">
<?php include('../includes/common_header.php'); ?>
<main id="js-page-content" role="main" class="page-content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h2 class="ta-page-title">Ticket Drilldown</h2><div class="ta-muted" id="drilldown_count">0 rows</div></div>
        <div><a class="btn btn-outline-primary btn-sm" href="index.php">Back to CFO Dashboard</a></div>
    </div>
    <div class="row mb-2">
        <div class="col-md-2"><input id="start_date" type="date" class="form-control"></div>
        <div class="col-md-2"><input id="end_date" type="date" class="form-control"></div>
        <div class="col-md-2"><select id="ticket_type" class="form-control"><option value="ALL">All</option><option>PPM</option><option>R&amp;M</option><option>Projects</option><option>Supply</option><option>AMC Breakdown</option></select></div>
        <div class="col-md-2"><input id="status" class="form-control" value="ALL"></div>
        <div class="col-md-2"><button class="btn btn-primary btn-block" onclick="taLoadAll()">Apply</button></div>
    </div>
    <div class="ta-table-wrap bg-white p-2 border rounded">
        <table class="table table-sm table-bordered table-striped">
            <thead>
                <tr>
                    <th>Ticket</th><th>Type</th><th>Status</th><th>Priority</th><th>Company</th><th>Branch</th><th>Assigned</th><th>Created</th><th>Due</th><th>Closed</th><th>Expense</th>
                </tr>
            </thead>
            <tbody id="drilldown_body"></tbody>
        </table>
    </div>
</main>
<?php include('../includes/common_footer.php'); ?>
</div></div>
<?php include('../includes/common_scripts.php'); ?>
<script src="assets/js/dashboard.js"></script>
<script>
const now = new Date();
const end = now.toISOString().slice(0, 10);
const startD = new Date(now); startD.setDate(now.getDate() - 90);
document.getElementById("start_date").value = startD.toISOString().slice(0, 10);
document.getElementById("end_date").value = end;
taLoadAll();
</script>
</body>
</html>
