<?php
session_start();
include('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
$UserType = SessionCheck();
setTimeZone();
setNavigation($_SESSION['Roles']);
$ProductName = "Aryadibusiness";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ticket Type Dashboard</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .ta-dashboard-wrap { width: 100%; max-width: 100%; overflow-x: hidden; }
        .ta-dashboard-wrap .row { margin-left: -6px; margin-right: -6px; }
        .ta-dashboard-wrap .row > [class*='col-'] { padding-left: 6px; padding-right: 6px; margin-bottom: 12px; }
        .ta-status-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:10px; }
        .ta-status-card { color:#fff; border-radius:10px; padding:10px; min-height:80px; box-shadow:0 2px 6px rgba(0,0,0,.15);}
        .ta-status-count { font-size:30px; font-weight:700; line-height:1; }
        .ta-status-label { font-size:12px; margin-top:4px; min-height:28px; }
        .ta-status-pct { font-size:12px; opacity:.95; }
        .ta-chart-card { height: 100%; }
        .ta-chart-box { position: relative; height: 260px; width: 100%; }
        @media (max-width:1200px){ .ta-chart-box { height: 240px; } }
        @media (max-width:768px){
            .ta-status-count { font-size:24px; }
            .ta-chart-box { height: 220px; }
        }
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
        <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics Dashboard</a></li>
        <li class="breadcrumb-item active">Ticket Type Dashboard</li>
    </ol>
    <div class="ta-dashboard-wrap">
    <section class="ta-topbar mb-3">
        <div><h1 class="ta-page-title"><span id="dashboard_title_type">R&M</span> Dashboard</h1><div class="ta-muted">Zoho-style per-ticket-type analytics using your real data</div></div>
        <div class="ta-topbar-actions"><a class="btn btn-outline-secondary btn-sm" href="index.php">Back Main Dashboard</a></div>
    </section>

    <section class="ta-filter-box ta-filter-panel mb-3">
        <div class="row">
        <div class="col-lg-3 col-md-4 col-sm-12">
                <select id="ticket_type" class="form-control">
                    <option>R&M</option><option>PPM</option><option>Projects</option><option>Supply</option><option>AMC Breakdown</option>
                </select>
            </div>
        <div class="col-lg-2 col-md-3 col-sm-6"><input id="start_date" type="date" class="form-control"></div>
        <div class="col-lg-2 col-md-3 col-sm-6"><input id="end_date" type="date" class="form-control"></div>
        <div class="col-lg-2 col-md-2 col-sm-12"><button class="btn btn-primary btn-block" onclick="loadTypeDashboard()">Search</button></div>
        </div>
    </section>

    <div class="row ta-kpi-grid">
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">Total</div><div id="k_total" class="ta-kpi-value">0</div></div></div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">Open</div><div id="k_open" class="ta-kpi-value">0</div></div></div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">Closed</div><div id="k_closed" class="ta-kpi-value">0</div></div></div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">Closure %</div><div id="k_closure" class="ta-kpi-value">0%</div></div></div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">Avg TAT</div><div id="k_tat" class="ta-kpi-value">0h</div></div></div>
        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6"><div class="ta-kpi-card"><div class="ta-kpi-label">SLA Breach</div><div id="k_sla" class="ta-kpi-value">0</div></div></div>
    </div>

    <div class="ta-chart-card">
        <h5 class="ta-chart-title"><span id="dashboard_title_type">R&M</span> Status Summary</h5>
        <div id="status_cards_wrap" class="ta-status-grid"></div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Opened vs Closed Trend</h5><div class="ta-chart-box"><canvas id="trend_chart"></canvas></div></div></div>
        <div class="col-xl-6 col-lg-6 col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Status Distribution</h5><div class="ta-chart-box"><canvas id="status_chart"></canvas></div></div></div>
    </div>
    <div class="row">
        <div class="col-xl-4 col-lg-4 col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Regional Distribution</h5><div class="ta-chart-box"><canvas id="regional_chart"></canvas></div></div></div>
        <div class="col-xl-4 col-lg-4 col-md-6"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Companies</h5><div class="ta-chart-box"><canvas id="companies_chart"></canvas></div></div></div>
        <div class="col-xl-4 col-lg-4 col-md-6"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Branches</h5><div class="ta-chart-box"><canvas id="branches_chart"></canvas></div></div></div>
    </div>
    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Assignees</h5><div class="ta-chart-box"><canvas id="assignees_chart"></canvas></div></div></div>
        <div class="col-xl-6 col-lg-6 col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Priority</h5><div class="ta-chart-box"><canvas id="priority_chart"></canvas></div></div></div>
    </div>

    <div class="ta-chart-card">
        <div class="d-flex justify-content-between"><h5 class="ta-chart-title">Latest Tickets</h5><div id="table_rows" class="ta-muted">0 rows</div></div>
        <div class="ta-table-wrap">
            <table class="table table-sm table-bordered table-striped">
                <thead><tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Company</th><th>Branch</th><th>Assigned</th><th>Created</th><th>Due</th><th>Closed</th></tr></thead>
                <tbody id="type_table_body"></tbody>
            </table>
        </div>
    </div>
    </div>
</main>
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_scripts.php'); ?>
<script src="assets/js/type-dashboard.js"></script>
<script>
const now = new Date();
const end = now.toISOString().slice(0, 10);
const start = new Date(now); start.setFullYear(now.getFullYear() - 1);
document.getElementById("start_date").value = start.toISOString().slice(0, 10);
document.getElementById("end_date").value = end;
const urlType = new URLSearchParams(window.location.search).get("type");
if (urlType) document.getElementById("ticket_type").value = urlType;
loadTypeDashboard();
</script>
</body>
</html>
