<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/jll_config.php';

if (!jll_check_access()) {
    http_response_code(403);
    echo 'JLL Wallboard access denied. Use: live-screen.php?key=YOUR_SECRET';
    exit;
}

date_default_timezone_set(JLL_TIMEZONE);
$keyQs = (JLL_WALLBOARD_SECRET !== '' && isset($_GET['key'])) ? (string)$_GET['key'] : '';
$apiUrl = 'api/live_data.php';
if ($keyQs !== '') {
    $apiUrl .= '?key=' . rawurlencode($keyQs);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(JLL_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?> — Live Operations Wallboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/jll-wallboard.css?v=3">
</head>
<body>
<div id="jll_error" class="jll-error" style="display:none;"></div>
<div class="jll-root">
    <header class="jll-header">
        <div class="jll-brand-block">
            <img src="<?php echo htmlspecialchars(JLL_TECHXPERT_LOGO, ENT_QUOTES, 'UTF-8'); ?>" alt="TechXpert" class="jll-tx-logo">
            <div class="jll-header-divider" aria-hidden="true"></div>
            <div class="jll-client-block">
                <span class="jll-client-badge">JLL</span>
                <div class="jll-client-text">
                    <h1 class="jll-title" id="jll_corp_name"><?php echo htmlspecialchars(JLL_BRAND_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="jll-subtitle"><?php echo htmlspecialchars(JLL_BRAND_SUBTITLE, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>
        <div class="jll-meta">
            <div class="jll-live"><span class="jll-pulse"></span> LIVE</div>
            <div>Date <strong id="jll_date">—</strong></div>
            <div>Updated <strong id="jll_updated">—</strong></div>
            <div>Next refresh <span class="jll-countdown" id="jll_countdown">2:00</span></div>
            <button type="button" id="jll_fullscreen_btn" class="jll-fs-btn">Fullscreen</button>
        </div>
    </header>

    <section class="jll-kpi-row">
        <div class="jll-kpi jll-kpi--navy"><div class="jll-kpi-label">Active backlog</div><div class="jll-kpi-value" id="k_active">0</div></div>
        <div class="jll-kpi jll-kpi--blue"><div class="jll-kpi-label">Opened today</div><div class="jll-kpi-value" id="k_opened">0</div></div>
        <div class="jll-kpi jll-kpi--green"><div class="jll-kpi-label">Closed today</div><div class="jll-kpi-value" id="k_closed">0</div></div>
        <div class="jll-kpi jll-kpi--teal"><div class="jll-kpi-label">Corporate active</div><div class="jll-kpi-value" id="k_corp_active">0</div></div>
        <div class="jll-kpi jll-kpi--cyan"><div class="jll-kpi-label">PPM active</div><div class="jll-kpi-value" id="k_ppm_active">0</div></div>
        <div class="jll-kpi jll-kpi--amber"><div class="jll-kpi-label">PPM overdue</div><div class="jll-kpi-value" id="k_ppm_overdue">0</div></div>
        <div class="jll-kpi jll-kpi--purple"><div class="jll-kpi-label">PPM compliance</div><div class="jll-kpi-value" id="k_ppm_compliance">0%</div></div>
        <div class="jll-kpi jll-kpi--indigo"><div class="jll-kpi-label">Status updates</div><div class="jll-kpi-value" id="k_status_updates">0</div></div>
    </section>

    <section class="jll-grid">
        <div class="jll-panel jll-panel--chart">
            <div class="jll-panel-head">7-day activity trend</div>
            <div class="jll-panel-body jll-chart-wrap"><canvas id="chart_trend"></canvas></div>
        </div>
        <div class="jll-panel jll-panel--chart">
            <div class="jll-panel-head">Open backlog by type</div>
            <div class="jll-panel-body jll-chart-wrap"><canvas id="chart_type_backlog"></canvas></div>
        </div>
        <div class="jll-panel jll-panel--chart">
            <div class="jll-panel-head">Corporate ticket status</div>
            <div class="jll-panel-body jll-chart-wrap"><canvas id="chart_corp_status"></canvas></div>
        </div>

        <div class="jll-panel jll-panel--chart">
            <div class="jll-panel-head">PPM status distribution</div>
            <div class="jll-panel-body jll-chart-wrap"><canvas id="chart_ppm_status"></canvas></div>
        </div>
        <div class="jll-panel">
            <div class="jll-panel-head">Today — opened vs closed by type</div>
            <div class="jll-panel-body">
                <table class="jll-table" id="jll_type_matrix">
                    <thead><tr><th>Type</th><th>Opened</th><th>Closed</th></tr></thead>
                    <tbody id="jll_type_matrix_body"></tbody>
                </table>
            </div>
        </div>
        <div class="jll-panel">
            <div class="jll-panel-head">Workflow movements (today)</div>
            <div class="jll-panel-body"><div class="jll-chips" id="jll_workflow"></div></div>
        </div>

        <div class="jll-panel jll-panel--branch-hub">
            <div class="jll-panel-head-row">
                <div class="jll-panel-head">Branch accounts</div>
                <div class="jll-tabs" id="jll_branch_tabs" role="tablist">
                    <button type="button" class="jll-tab active" data-tab="sites" role="tab" aria-selected="true">Top sites</button>
                    <button type="button" class="jll-tab" data-tab="contracts" role="tab" aria-selected="false">By contract</button>
                    <button type="button" class="jll-tab" data-tab="branches" role="tab" aria-selected="false">All branches</button>
                </div>
            </div>
            <div class="jll-tab-panes">
                <div class="jll-tab-pane active" data-tab="sites" role="tabpanel">
                    <div class="jll-panel-body jll-scroll" id="jll_top_sites"></div>
                </div>
                <div class="jll-tab-pane" data-tab="contracts" role="tabpanel">
                    <div class="jll-panel-body jll-scroll">
                        <table class="jll-table jll-table--branch">
                            <thead><tr><th>Contract account</th><th>Sites</th><th>Active</th><th>Corp</th><th>PPM</th><th>Opened</th><th>Closed</th></tr></thead>
                            <tbody id="jll_contract_branch_summary"></tbody>
                        </table>
                    </div>
                </div>
                <div class="jll-tab-pane" data-tab="branches" role="tabpanel">
                    <div class="jll-panel-body jll-scroll">
                        <table class="jll-table jll-table--branch">
                            <thead><tr><th>Contract</th><th>Branch / Site</th><th>Code</th><th>Location</th><th>Corp</th><th>PPM</th><th>Active</th><th>Today</th></tr></thead>
                            <tbody id="jll_branch_accounts"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="jll-panel">
            <div class="jll-panel-head">Top technicians (today)</div>
            <div class="jll-panel-body">
                <table class="jll-table">
                    <thead><tr><th></th><th>Name</th><th>Open</th><th>Close</th></tr></thead>
                    <tbody id="jll_employees"></tbody>
                </table>
            </div>
        </div>

        <div class="jll-panel jll-panel--wide">
            <div class="jll-panel-head">PPM schedule — next 14 days</div>
            <div class="jll-panel-body jll-scroll">
                <table class="jll-table jll-table--compact">
                    <thead><tr><th>Ticket</th><th>PPM Date</th><th>Status</th><th>Contract</th><th>Site</th><th>State</th><th>Assigned</th></tr></thead>
                    <tbody id="jll_ppm_upcoming"></tbody>
                </table>
            </div>
        </div>

        <div class="jll-panel jll-panel--feed">
            <div class="jll-panel-head">Live activity feed — opened, closed &amp; status changes</div>
            <div class="jll-panel-body jll-ticker" id="jll_ticker"></div>
        </div>
    </section>

    <footer class="jll-footer">
        <span id="jll_contracts_count">0 contracts</span> · <span id="jll_branches_count">0 branch accounts</span> · Corporate HQ ID <?php echo (int)JLL_CORPORATE_HQ_ID; ?>
        · R&amp;M, PPM, Projects, Supply, AMC · Auto-refresh <?php echo (int)(JLL_REFRESH_SECONDS / 60); ?> min
        · Powered by <strong>TechXpert</strong>
    </footer>
</div>
<script>
window.JLL_WALLBOARD = {
    refreshSeconds: <?php echo (int)JLL_REFRESH_SECONDS; ?>,
    apiUrl: <?php echo json_encode($apiUrl); ?>,
    key: <?php echo json_encode($keyQs); ?>
};
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="assets/jll-wallboard.js?v=3"></script>
</body>
</html>
