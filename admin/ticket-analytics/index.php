<?php
session_start();
include('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
$UserType = SessionCheck();
setTimeZone();
setNavigation($_SESSION['Roles']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>CFO Ticket Analytics</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="mod-bg-1 mod-nav-link">
<div class="page-wrapper">
    <div class="page-inner">
        <?php include('../includes/common_header.php'); ?>
        <main id="js-page-content" role="main" class="page-content ta-analytics-page">
            <section class="ta-topbar mb-3">
                <div>
                    <h1 class="ta-page-title">Ticket Intelligence</h1>
                    <div class="ta-muted">Zoho-style analytics view powered by your Aryadibusiness ticket data</div>
                </div>
                <div class="ta-topbar-actions">
                    <a class="btn btn-primary btn-sm" href="type-dashboards.php">Type dashboards</a>
                    <a class="btn btn-outline-primary btn-sm" href="rm-dashboard.php">R&amp;M dashboard</a>
                    <a class="btn btn-outline-primary btn-sm" href="trends.php">Open Trends</a>
                    <a class="btn btn-outline-secondary btn-sm" href="drilldown.php">Open Drilldown</a>
                </div>
            </section>

            <section class="ta-filter-box ta-filter-panel mb-3">
                <div class="ta-filter-head">
                    <div>
                        <div class="ta-panel-title">Global Filters</div>
                        <div class="ta-muted">Date range, status, and ticket-type segments</div>
                    </div>
                    <div class="ta-check-actions">
                        <button type="button" class="btn btn-xs btn-outline-primary" id="ta_select_all_types">Select All</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" id="ta_clear_all_types">Clear</button>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-2"><input id="start_date" type="date" class="form-control"></div>
                    <div class="col-md-2"><input id="end_date" type="date" class="form-control"></div>
                    <div class="col-md-3"><input id="status" class="form-control" placeholder="Status or ALL" value="ALL"></div>
                    <div class="col-md-2"><button class="btn btn-primary btn-block" onclick="taLoadAll()">Apply</button></div>
                </div>

                <div class="ta-check-group mt-3">
                    <label class="ta-check-item"><input type="checkbox" name="ticket_types" value="PPM" checked> <span>PPM</span></label>
                    <label class="ta-check-item"><input type="checkbox" name="ticket_types" value="R&M" checked> <span>R&amp;M</span></label>
                    <label class="ta-check-item"><input type="checkbox" name="ticket_types" value="Projects" checked> <span>Projects</span></label>
                    <label class="ta-check-item"><input type="checkbox" name="ticket_types" value="Supply" checked> <span>Supply</span></label>
                    <label class="ta-check-item"><input type="checkbox" name="ticket_types" value="AMC Breakdown" checked> <span>AMC Breakdown</span></label>
                </div>
            </section>

            <div class="row ta-kpi-grid">
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Total Tickets</div><div id="kpi_total" class="ta-kpi-value">0</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Open Tickets</div><div id="kpi_open" class="ta-kpi-value">0</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Closed Tickets</div><div id="kpi_closed" class="ta-kpi-value">0</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Closure Rate</div><div id="kpi_closure_rate" class="ta-kpi-value">0%</div></div></div>
            </div>
            <div class="row mt-2 ta-kpi-grid">
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Avg TAT</div><div id="kpi_tat" class="ta-kpi-value">0h</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">SLA Breach</div><div id="kpi_sla" class="ta-kpi-value">0</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Backlog</div><div id="kpi_backlog" class="ta-kpi-value">0</div></div></div>
                <div class="col-md-3"><div class="ta-kpi-card"><div class="ta-kpi-label">Expense</div><div id="kpi_expense" class="ta-kpi-value">Rs 0</div></div></div>
            </div>

            <div class="row">
                <div class="col-md-8"><div class="ta-chart-card"><h5 class="ta-chart-title">Opened vs Closed Trend</h5><canvas id="trend_chart" height="90"></canvas></div></div>
                <div class="col-md-4"><div class="ta-chart-card"><h5 class="ta-chart-title">Ticket Type Mix (Pie)</h5><canvas id="type_chart" height="90"></canvas><ul id="type_breakdown_list" class="ta-mini-list"></ul></div></div>
            </div>
            <div class="row">
                <div class="col-md-12"><div class="ta-chart-card"><h5 class="ta-chart-title">Status Distribution</h5><canvas id="status_chart" height="80"></canvas></div></div>
            </div>
        </main>
        <?php include('../includes/common_footer.php'); ?>
    </div>
</div>
<?php include('../includes/common_scripts.php'); ?>
<script src="assets/js/dashboard.js"></script>
<script>
const now = new Date();
const end = now.toISOString().slice(0, 10);
const startD = new Date(now); startD.setDate(now.getDate() - 29);
document.getElementById("start_date").value = startD.toISOString().slice(0, 10);
document.getElementById("end_date").value = end;
taLoadAll();
</script>
</body>
</html>
