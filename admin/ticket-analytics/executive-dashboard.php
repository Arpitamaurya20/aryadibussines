<?php
/**
 * Shared executive ticket dashboard (R&M, PPM, Projects, Supply, AMC Breakdown).
 * Set $taExecutiveType before include, or pass ?type= in the URL.
 */
session_start();
include('../controllers/common_controllers.php');
require_once('../includes/autoloader.inc.php');
require_once __DIR__ . '/inc/filters.php';
require_once __DIR__ . '/inc/executive_dashboard_config.php';

$UserType = SessionCheck();
setTimeZone();
setNavigation($_SESSION['Roles']);
$ProductName = 'Aryadibusiness';

$taExecutiveType = isset($taExecutiveType)
    ? ta_normalize_ticket_type((string)$taExecutiveType)
    : ta_normalize_ticket_type((string)($_GET['type'] ?? 'R&M'));

$taExecutiveMeta = ta_executive_dashboard_by_type($taExecutiveType);
if ($taExecutiveMeta === null) {
    http_response_code(404);
    echo 'Unknown ticket type dashboard.';
    exit;
}

$taExecutiveTitle = $taExecutiveMeta['title'];
$taExecutiveRange = ta_last_march_to_today_range();
$taDashboardCatalog = ta_executive_dashboard_catalog();
$taTitleSafe = htmlspecialchars($taExecutiveTitle, ENT_QUOTES, 'UTF-8');
$taShowServiceCharts = ($taExecutiveType !== 'PPM');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $taTitleSafe; ?> Executive Dashboard</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .ta-type-nav { display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
        .ta-type-nav a {
            display:inline-block; padding:6px 12px; border-radius:999px; font-size:12px; font-weight:600;
            text-decoration:none; border:1px solid #d9e3f5; color:#334155; background:#fff;
        }
        .ta-type-nav a.active { color:#fff; border-color:transparent; }
    </style>
</head>
<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
<?php include('../js/theme_settings.js'); ?>
<div class="page-wrapper">
    <div class="page-inner">
        <?php include('../navigation/admin_navigation.php'); ?>
        <div class="page-content-wrapper">
            <?php include('../includes/common_header.php'); ?>
            <main id="js-page-content" role="main" class="page-content ta-analytics-page ta-executive-dashboard ta-rm-dashboard">
    <ol class="breadcrumb page-breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0);"><?php echo $ProductName; ?></a></li>
        <li class="breadcrumb-item"><a href="type-dashboards.php">Ticket Dashboards</a></li>
        <li class="breadcrumb-item active"><?php echo $taTitleSafe; ?></li>
    </ol>
    <section class="ta-topbar mb-3">
        <div>
            <h1 class="ta-page-title"><?php echo $taTitleSafe; ?> Executive Dashboard</h1>
            <div class="ta-muted"><?php echo htmlspecialchars($taExecutiveMeta['description'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="ta-type-nav">
<?php foreach ($taDashboardCatalog as $item) {
    $active = ($item['type'] === $taExecutiveType);
    $style = $active ? ' style="background:' . htmlspecialchars($item['accent'], ENT_QUOTES, 'UTF-8') . ';"' : '';
    ?>
                <a href="<?php echo htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $active ? 'active' : ''; ?>"<?php echo $style; ?>><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></a>
<?php } ?>
            </div>
        </div>
        <div class="ta-topbar-actions">
            <a class="btn btn-outline-secondary btn-sm" href="type-dashboards.php">All dashboards</a>
            <a class="btn btn-outline-secondary btn-sm" href="index.php">Combined analytics</a>
        </div>
    </section>

    <section class="ta-filter-box ta-filter-panel mb-3">
        <div class="ta-panel-title">Date Filter</div>
        <div class="row mt-2">
            <div class="col-md-2"><input id="start_date" type="date" class="form-control"></div>
            <div class="col-md-2"><input id="end_date" type="date" class="form-control"></div>
            <div class="col-md-2"><button type="button" class="btn btn-primary btn-block" onclick="loadExecutiveDashboard()">Apply</button></div>
        </div>
        <div class="ta-muted mt-2">Default: 1 March (current period) through today.</div>
        <div id="exec_debug_counts" class="ta-muted mt-1" style="font-size:12px;"></div>
    </section>

    <div id="exec_empty_msg" class="alert alert-warning" style="display:none;">
        No <?php echo $taTitleSafe; ?> tickets found for the selected date range.
    </div>

    <div class="row ta-kpi-grid">
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--total"><div class="ta-kpi-label">Total</div><div id="k_total" class="ta-kpi-value">0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--open"><div class="ta-kpi-label">Open</div><div id="k_open" class="ta-kpi-value">0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--closed"><div class="ta-kpi-label">Closed</div><div id="k_closed" class="ta-kpi-value">0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--closure"><div class="ta-kpi-label">Closure %</div><div id="k_closure" class="ta-kpi-value">0%</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--tat"><div class="ta-kpi-label">Avg TAT</div><div id="k_tat" class="ta-kpi-value">0h</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--sla"><div class="ta-kpi-label">SLA Breach</div><div id="k_sla" class="ta-kpi-value">0</div></div></div>
    </div>
    <div class="row ta-kpi-grid mt-2">
        <div class="col-md-3"><div class="ta-kpi-card ta-kpi--backlog"><div class="ta-kpi-label">Backlog</div><div id="k_backlog" class="ta-kpi-value">0</div></div></div>
        <div class="col-md-3"><div class="ta-kpi-card ta-kpi--expense"><div class="ta-kpi-label">Expense</div><div id="k_expense" class="ta-kpi-value">Rs 0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--customer"><div class="ta-kpi-label">Customer Value</div><div id="k_customer" class="ta-kpi-value">Rs 0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--margin"><div class="ta-kpi-label">Margin</div><div id="k_margin" class="ta-kpi-value">Rs 0</div></div></div>
        <div class="col-md-2"><div class="ta-kpi-card ta-kpi--cpt"><div class="ta-kpi-label">Cost/Ticket</div><div id="k_cpt" class="ta-kpi-value">Rs 0</div></div></div>
    </div>

    <div class="row">
        <div class="col-md-8"><div class="ta-chart-card"><h5 class="ta-chart-title">Opened vs Closed Trend (<?php echo $taTitleSafe; ?>)</h5><div class="ta-chart-box"><canvas id="exec_trend_chart"></canvas></div></div></div>
        <div class="col-md-4"><div class="ta-chart-card"><h5 class="ta-chart-title">Status Mix</h5><div class="ta-chart-box"><canvas id="exec_status_chart"></canvas></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-4"><div class="ta-chart-card"><h5 class="ta-chart-title">Priority</h5><div class="ta-chart-box"><canvas id="exec_priority_chart"></canvas></div></div></div>
        <div class="col-md-4"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Companies</h5><div class="ta-chart-box"><canvas id="exec_company_chart"></canvas></div></div></div>
        <div class="col-md-4"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Branches</h5><div class="ta-chart-box"><canvas id="exec_branch_chart"></canvas></div></div></div>
    </div>
    <div class="row">
        <div class="col-md-6"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Assignees</h5><div class="ta-chart-box"><canvas id="exec_assignee_chart"></canvas></div></div></div>
<?php if ($taShowServiceCharts) { ?>
        <div class="col-md-6"><div class="ta-chart-card"><h5 class="ta-chart-title">Top Services</h5><div class="ta-chart-box"><canvas id="exec_service_chart"></canvas></div></div></div>
<?php } else { ?>
        <div class="col-md-6"><div class="ta-chart-card"><h5 class="ta-chart-title">Ticket volume</h5><div class="ta-chart-box ta-chart-box--muted d-flex align-items-center justify-content-center text-muted">Service breakdown applies to corporate ticket types.</div></div></div>
<?php } ?>
    </div>

    <div class="ta-chart-card">
        <div class="d-flex justify-content-between"><h5 class="ta-chart-title"><?php echo $taTitleSafe; ?> Drilldown</h5><div id="exec_rows" class="ta-muted">0 rows</div></div>
        <div class="ta-table-wrap">
            <table class="table table-sm table-bordered table-striped">
                <thead><tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Service</th><th>Subservice</th><th>Company</th><th>Branch</th><th>Assigned</th><th>Created</th><th>Due</th><th>Closed</th><th>Expense</th><th>Customer</th></tr></thead>
                <tbody id="exec_table_body"></tbody>
            </table>
        </div>
    </div>
</main>
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_scripts.php'); ?>
<script>
window.TA_EXECUTIVE_TYPE = <?php echo json_encode($taExecutiveType); ?>;
window.TA_EXECUTIVE_LABEL = <?php echo json_encode($taExecutiveTitle); ?>;
</script>
<script src="assets/js/executive-dashboard.js?v=20260529_types"></script>
<script>
document.getElementById("start_date").value = <?php echo json_encode($taExecutiveRange['start_date']); ?>;
document.getElementById("end_date").value = <?php echo json_encode($taExecutiveRange['end_date']); ?>;
loadExecutiveDashboard();
</script>
</body>
</html>
