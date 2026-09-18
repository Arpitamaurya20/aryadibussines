<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/wallboard_config.php';

if (!wallboard_check_access()) {
    http_response_code(403);
    echo 'Wallboard access denied. Set key in URL: live-screen.php?key=YOUR_SECRET';
    exit;
}

date_default_timezone_set(WALLBOARD_TIMEZONE);
$keyQs = (WALLBOARD_SECRET !== '' && isset($_GET['key'])) ? (string)$_GET['key'] : '';
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
    <title><?php echo htmlspecialchars(WALLBOARD_COMPANY_NAME, ENT_QUOTES, 'UTF-8'); ?> — Today Pan India</title>
    <link rel="stylesheet" href="assets/wallboard.css">
</head>
<body>
<div id="wb_error" class="wb-error" style="display:none;"></div>
<div class="wb-root">
    <header class="wb-header">
        <div class="wb-brand"><span><?php echo htmlspecialchars(WALLBOARD_COMPANY_NAME, ENT_QUOTES, 'UTF-8'); ?></span> Pan India — Today Only</div>
        <div class="wb-meta">
            <div><span class="wb-pulse"></span> LIVE · Date <strong id="wb_date">—</strong></div>
            <div>Updated <strong id="wb_updated">—</strong> · Next refresh <span class="wb-countdown" id="wb_countdown">5:00</span></div>
            <button type="button" id="wb_fullscreen_btn" class="wb-fullscreen-btn" title="Toggle fullscreen">Fullscreen</button>
        </div>
    </header>

    <section class="wb-kpi-row wb-kpi-row--7">
        <div class="wb-kpi wb-kpi--blue"><div class="wb-kpi-label">Opened today</div><div class="wb-kpi-value" id="k_opened_today">0</div></div>
        <div class="wb-kpi wb-kpi--green"><div class="wb-kpi-label">Closed today</div><div class="wb-kpi-value" id="k_closed_today">0</div></div>
        <div class="wb-kpi wb-kpi--amber"><div class="wb-kpi-label">Quote sent (approval)</div><div class="wb-kpi-value" id="k_quote_sent">0</div></div>
        <div class="wb-kpi wb-kpi--cyan"><div class="wb-kpi-label">Quote approved</div><div class="wb-kpi-value" id="k_quote_approved">0</div></div>
        <div class="wb-kpi wb-kpi--money"><div class="wb-kpi-label">Quote approved ₹ (today)</div><div class="wb-kpi-value wb-kpi-value--money" id="k_quote_amount">₹ 0</div></div>
        <div class="wb-kpi wb-kpi--purple"><div class="wb-kpi-label">Status updates</div><div class="wb-kpi-value" id="k_status_updates">0</div></div>
    </section>

    <section class="wb-main wb-main--today">
        <div class="wb-panel">
            <div class="wb-panel-title">Workflow today (status movements)</div>
            <div class="wb-panel-body"><div class="wb-status-grid" id="wb_workflow_today"></div></div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">All status updates today (Pan India)</div>
            <div class="wb-panel-body"><div class="wb-status-grid" id="wb_status_moves"></div></div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">Ticket type — opened vs closed (today)</div>
            <div class="wb-panel-body">
                <table class="wb-table" id="wb_type_matrix">
                    <thead><tr><th>Type</th><th>Opened</th><th>Closed</th></tr></thead>
                    <tbody id="wb_type_matrix_body"></tbody>
                </table>
            </div>
        </div>

        <div class="wb-panel">
            <div class="wb-panel-title">Opened today — by status</div>
            <div class="wb-panel-body"><div class="wb-status-grid" id="wb_opened_status"></div></div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">Closed today — by status</div>
            <div class="wb-panel-body"><div class="wb-status-grid" id="wb_closed_status"></div></div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">Pan India — states (today activity)</div>
            <div class="wb-panel-body"><ul class="wb-bar-list" id="wb_states_today"></ul></div>
        </div>

        <div class="wb-panel">
            <div class="wb-panel-title">Top employees (today)</div>
            <div class="wb-panel-body">
                <table class="wb-table">
                    <thead><tr><th></th><th>Name</th><th>Opened</th><th>Closed</th></tr></thead>
                    <tbody id="wb_employee_rows"></tbody>
                </table>
            </div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">Top companies (today)</div>
            <div class="wb-panel-body"><ul class="wb-bar-list" id="wb_company_bars"></ul></div>
        </div>
        <div class="wb-panel">
            <div class="wb-panel-title">Top branches (today)</div>
            <div class="wb-panel-body"><ul class="wb-bar-list" id="wb_branch_bars"></ul></div>
        </div>

        <div class="wb-panel wb-panel--feed">
            <div class="wb-panel-title">Live feed — opened, closed &amp; status changes (today)</div>
            <div class="wb-panel-body wb-ticker" id="wb_ticker"></div>
        </div>
        <div class="wb-panel wb-panel--side">
            <div class="wb-panel-title">Opened today by type</div>
            <div class="wb-panel-body"><ul class="wb-bar-list" id="wb_type_opened"></ul></div>
            <div class="wb-panel-title" style="font-size:11px;margin-top:8px">Closed today by type</div>
            <div class="wb-panel-body"><ul class="wb-bar-list" id="wb_type_closed"></ul></div>
        </div>
    </section>

    <footer class="wb-footer">Today only · Pan India · R&amp;M, PPM, Projects, Supply, AMC · Auto-refresh <?php echo (int)(WALLBOARD_REFRESH_SECONDS / 60); ?> min</footer>
</div>
<script>
window.WALLBOARD_CONFIG = {
    refreshSeconds: <?php echo (int)WALLBOARD_REFRESH_SECONDS; ?>,
    apiUrl: <?php echo json_encode($apiUrl); ?>,
    key: <?php echo json_encode($keyQs); ?>
};
</script>
<script src="assets/wallboard.js?v=7"></script>
</body>
</html>
