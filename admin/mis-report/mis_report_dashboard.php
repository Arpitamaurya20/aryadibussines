<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MIS Report">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');

    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);

    $conn = _connectodb();
    $core = new Core();
    $core->setTimeZone();

    require_once __DIR__ . '/inc/mis_report_scope.php';

    $adminScope = mis_report_enforce_page_access($conn, $_SESSION);

    $stateNames = $adminScope['state_names'] ?? [];
    $fyRange = state_dashboard_current_fy_range();
    $ticketTypes = ['', 'R&M', 'PPM', 'Projects', 'AMC Breakdown', 'Supply'];

    $ProductName = 'Aryadibusiness';
    ?>
    <title><?= htmlspecialchars($ProductName, ENT_QUOTES, 'UTF-8'); ?> — MIS Report</title>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <link rel="stylesheet" href="../dashboard/assets/state_dashboard.css?v=6">
    <link rel="stylesheet" href="assets/mis_report.css?v=1">
    <link rel="stylesheet" href="assets/mis_report_print.css?v=1">
</head>
<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?= htmlspecialchars($ProductName, ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <li class="breadcrumb-item active">MIS Report</li>
                    </ol>

                    <div id="mis_print_header" aria-hidden="true"></div>

                    <div class="smd-root">
                        <div class="smd-hero">
                            <h1>MIS Report</h1>
                            <p>Select a state first — then load tickets, quotations, workforce, convenience &amp; salary for that state.</p>
                            <div class="smd-hero-meta">
                                <?php mis_report_render_scope_hero(); ?>
                                <div class="smd-period-meta">
                                    <div>Period: <strong id="smd_period_label"><?= htmlspecialchars($fyRange['fy_label'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                    <div><span id="smd_period_range"><?= htmlspecialchars($fyRange['start'] . ' — ' . $fyRange['end'], ENT_QUOTES, 'UTF-8'); ?></span> · Loaded <strong id="smd_updated">—</strong></div>
                                </div>
                            </div>
                        </div>

                        <div class="smd-filters panel mb-3">
                            <div class="panel-content p-3">
                                <div class="row align-items-end">
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <label class="smd-filter-label">Period</label>
                                        <select id="smd_preset" class="select2 smd-select2 form-control form-control-sm w-100">
                                            <option value="current_fy" selected>Current financial year</option>
                                            <option value="previous_fy">Previous financial year</option>
                                            <option value="this_month">This month</option>
                                            <option value="custom">Custom date range</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2 smd-custom-dates" style="display:none;">
                                        <label class="smd-filter-label">Date range</label>
                                        <input type="text" id="smd_date_range" class="form-control form-control-sm" value="<?= htmlspecialchars($fyRange['start'] . ' - ' . $fyRange['end'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <label class="smd-filter-label">State <span class="text-danger">*</span></label>
                                        <select id="smd_state" class="select2 smd-select2 form-control form-control-sm w-100" required>
                                            <option value="">— Select state —</option>
                                            <?php foreach ($stateNames as $stateName): ?>
                                                <option value="<?= htmlspecialchars($stateName, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($stateName, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <label class="smd-filter-label">Company</label>
                                        <select id="smd_corporate" class="select2 smd-select2 form-control form-control-sm w-100" disabled>
                                            <option value="0">Select state first</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <label class="smd-filter-label">Branch</label>
                                        <select id="smd_branch" class="select2 smd-select2 form-control form-control-sm w-100" disabled>
                                            <option value="0">Select state first</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <label class="smd-filter-label">Ticket type</label>
                                        <select id="smd_ticket_type" class="select2 smd-select2 form-control form-control-sm w-100">
                                            <option value="">All types</option>
                                            <?php foreach (array_filter($ticketTypes) as $tt): ?>
                                                <option value="<?= htmlspecialchars($tt, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($tt, ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <button type="button" id="smd_apply_btn" class="btn btn-primary btn-sm btn-block">Load report</button>
                                    </div>
                                    <div class="col-md-2 col-sm-6 mb-2">
                                        <button type="button" id="smd_print_btn" class="btn btn-outline-secondary btn-sm btn-block">
                                            <i class="fa-solid fa-print"></i> Print report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="smd_error" class="smd-error"></div>

                        <div id="smd_placeholder" class="mis-placeholder panel mb-3">
                            <div class="panel-content p-4 text-center text-muted">
                                <i class="fa-solid fa-map-location-dot fa-2x mb-2 d-block"></i>
                                <strong>Select a state and click Load report</strong>
                                <p class="mb-0 small">Workforce, convenience, salary, tickets, quotations and branch analytics will appear here.</p>
                            </div>
                        </div>

                        <div id="smd_loading" class="smd-loading" style="display:none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div>Loading MIS report…</div>
                        </div>

                        <div id="smd_content" style="display:none;">
                            <section class="mis-workforce-section mb-3">
                                <div class="smd-quote-section-title">Workforce &amp; payroll (selected state)</div>
                                <div class="smd-kpi-grid mis-workforce-kpis">
                                    <div class="smd-kpi smd-kpi--slate">
                                        <div class="smd-kpi-label">Employees</div>
                                        <div class="smd-kpi-value" id="wf_employees">0</div>
                                    </div>
                                    <div class="smd-kpi smd-kpi--purple">
                                        <div class="smd-kpi-label">Technicians</div>
                                        <div class="smd-kpi-value" id="wf_technicians">0</div>
                                    </div>
                                    <div class="smd-kpi smd-kpi--amber">
                                        <div class="smd-kpi-label">Convenience claims</div>
                                        <div class="smd-kpi-value" id="wf_conv_count">0</div>
                                        <div class="smd-kpi-sub" id="wf_conv_amount">₹ 0</div>
                                    </div>
                                    <div class="smd-kpi smd-kpi--green">
                                        <div class="smd-kpi-label">Convenience paid</div>
                                        <div class="smd-kpi-value" id="wf_conv_paid_count">0</div>
                                        <div class="smd-kpi-sub" id="wf_conv_paid_amount">₹ 0</div>
                                    </div>
                                </div>
                                <div class="smd-panel smd-panel--full mt-3">
                                    <div class="smd-panel-title">Employees &amp; technicians — tickets, convenience &amp; salary</div>
                                    <div class="smd-panel-body" style="overflow-x:auto;">
                                        <table class="smd-table mis-emp-table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Role</th>
                                                    <th>Designation</th>
                                                    <th>Assigned</th>
                                                    <th>Closed</th>
                                                    <th>WIP</th>
                                                    <th>Convenience</th>
                                                    <th>Salary (₹)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="mis_employee_rows">
                                                <tr><td colspan="8" class="smd-empty">No employees in this state</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </section>

                            <div class="smd-quote-section-title">Tickets, quotations &amp; branches</div>
                            <div id="mis_tickets_loading" class="mis-section-loading" style="display:none;">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span>Loading ticket &amp; quotation analytics…</span>
                            </div>
                            <div id="mis_tickets_body">
                            <section class="smd-kpi-grid">
                                <div class="smd-kpi smd-kpi--blue">
                                    <div class="smd-kpi-label">Tickets opened</div>
                                    <div class="smd-kpi-value" id="k_opened">0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--green">
                                    <div class="smd-kpi-label">Tickets closed</div>
                                    <div class="smd-kpi-value" id="k_closed">0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--amber">
                                    <div class="smd-kpi-label">Quote sent (approval)</div>
                                    <div class="smd-kpi-value" id="k_quote_sent">0</div>
                                    <div class="smd-kpi-sub" id="k_quote_sent_amt">₹ 0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--cyan">
                                    <div class="smd-kpi-label">Quote approved</div>
                                    <div class="smd-kpi-value" id="k_quote_approved">0</div>
                                    <div class="smd-kpi-sub" id="k_quote_approved_amt">₹ 0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--slate">
                                    <div class="smd-kpi-label">Quote pending</div>
                                    <div class="smd-kpi-value" id="k_quote_pending">0</div>
                                    <div class="smd-kpi-sub" id="k_quote_pending_amt">₹ 0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--red">
                                    <div class="smd-kpi-label">Quote rejected</div>
                                    <div class="smd-kpi-value" id="k_quote_rejected">0</div>
                                    <div class="smd-kpi-sub" id="k_quote_rejected_amt">₹ 0</div>
                                </div>
                                <div class="smd-kpi smd-kpi--purple">
                                    <div class="smd-kpi-label">Status updates</div>
                                    <div class="smd-kpi-value" id="k_status_updates">0</div>
                                </div>
                            </section>

                            <section class="smd-quote-section">
                                <div class="smd-quote-section-title">Quotation value summary</div>
                                <div class="smd-quote-cards">
                                    <div class="smd-quote-card smd-quote-card--pending">
                                        <div class="smd-quote-card-icon"><i class="fa-solid fa-clock"></i></div>
                                        <div class="smd-quote-card-body">
                                            <div class="smd-quote-card-label">Sent for approval</div>
                                            <div class="smd-quote-card-count"><span id="qc_sent_count">0</span> quotes</div>
                                            <div class="smd-quote-card-value" id="qc_sent_amount">₹ 0</div>
                                        </div>
                                    </div>
                                    <div class="smd-quote-card smd-quote-card--approved">
                                        <div class="smd-quote-card-icon"><i class="fa-solid fa-circle-check"></i></div>
                                        <div class="smd-quote-card-body">
                                            <div class="smd-quote-card-label">Approved</div>
                                            <div class="smd-quote-card-count"><span id="qc_approved_count">0</span> quotes</div>
                                            <div class="smd-quote-card-value" id="qc_approved_amount">₹ 0</div>
                                        </div>
                                    </div>
                                    <div class="smd-quote-card smd-quote-card--waiting">
                                        <div class="smd-quote-card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                                        <div class="smd-quote-card-body">
                                            <div class="smd-quote-card-label">Pending (in pipeline)</div>
                                            <div class="smd-quote-card-count"><span id="qc_pending_count">0</span> quotes</div>
                                            <div class="smd-quote-card-value" id="qc_pending_amount">₹ 0</div>
                                        </div>
                                    </div>
                                    <div class="smd-quote-card smd-quote-card--rejected">
                                        <div class="smd-quote-card-icon"><i class="fa-solid fa-circle-xmark"></i></div>
                                        <div class="smd-quote-card-body">
                                            <div class="smd-quote-card-label">Rejected</div>
                                            <div class="smd-quote-card-count"><span id="qc_rejected_count">0</span> quotes</div>
                                            <div class="smd-quote-card-value" id="qc_rejected_amount">₹ 0</div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <div class="smd-context-row">
                                <div class="smd-context-pill">Branches in scope: <strong id="ctx_branches">0</strong></div>
                                <div class="smd-context-pill">Companies in scope: <strong id="ctx_companies">0</strong></div>
                            </div>

                            <section class="smd-charts-section">
                                <div class="smd-quote-section-title">Analytics &amp; trends</div>
                                <div class="smd-charts-grid">
                                    <div class="smd-chart-panel smd-chart-panel--wide">
                                        <div class="smd-panel-title">Monthly ticket trend — opened vs closed</div>
                                        <div class="smd-chart-wrap"><canvas id="chart_monthly_trend"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel smd-chart-panel--wide">
                                        <div class="smd-panel-title">Monthly quotation value trend (₹)</div>
                                        <div class="smd-chart-wrap"><canvas id="chart_monthly_quotes"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel">
                                        <div class="smd-panel-title">Tickets by type (opened)</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--pie"><canvas id="chart_ticket_type_pie"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel">
                                        <div class="smd-panel-title">Quotation value by status (₹)</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--pie"><canvas id="chart_quote_value_pie"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel">
                                        <div class="smd-panel-title">Opened tickets by status</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--pie"><canvas id="chart_opened_status_pie"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel smd-chart-panel--wide">
                                        <div class="smd-panel-title">Ticket type — opened vs closed</div>
                                        <div class="smd-chart-wrap"><canvas id="chart_type_comparison"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel">
                                        <div class="smd-panel-title">Top branches (activity)</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--bar-h"><canvas id="chart_top_branches"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel">
                                        <div class="smd-panel-title">Top companies (activity)</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--bar-h"><canvas id="chart_top_companies"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel smd-chart-panel--wide">
                                        <div class="smd-panel-title">Top technicians — opened vs closed</div>
                                        <div class="smd-chart-wrap smd-chart-wrap--bar-h"><canvas id="chart_top_technicians"></canvas></div>
                                    </div>
                                    <div class="smd-chart-panel smd-chart-panel--wide">
                                        <div class="smd-panel-title">Workflow status movements</div>
                                        <div class="smd-chart-wrap"><canvas id="chart_workflow_bar"></canvas></div>
                                    </div>
                                </div>
                            </section>

                            <section class="smd-grid">
                                <div class="smd-panel">
                                    <div class="smd-panel-title">Workflow (status movements)</div>
                                    <div class="smd-panel-body"><div class="smd-chip-grid" id="smd_workflow"></div></div>
                                </div>
                                <div class="smd-panel">
                                    <div class="smd-panel-title">All status updates</div>
                                    <div class="smd-panel-body"><div class="smd-chip-grid" id="smd_status_moves"></div></div>
                                </div>
                                <div class="smd-panel">
                                    <div class="smd-panel-title">Ticket type — opened vs closed</div>
                                    <div class="smd-panel-body">
                                        <table class="smd-table">
                                            <thead><tr><th>Type</th><th>Opened</th><th>Closed</th></tr></thead>
                                            <tbody id="smd_type_matrix_body"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="smd-panel">
                                    <div class="smd-panel-title">Opened — by status</div>
                                    <div class="smd-panel-body"><div class="smd-chip-grid" id="smd_opened_status"></div></div>
                                </div>
                                <div class="smd-panel">
                                    <div class="smd-panel-title">Closed — by status</div>
                                    <div class="smd-panel-body"><div class="smd-chip-grid" id="smd_closed_status"></div></div>
                                </div>
                                <div class="smd-panel">
                                    <div class="smd-panel-title">Top technicians</div>
                                    <div class="smd-panel-body">
                                        <table class="smd-table">
                                            <thead><tr><th></th><th>Name</th><th>Opened</th><th>Closed</th></tr></thead>
                                            <tbody id="smd_employee_rows"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="smd-panel smd-panel--wide">
                                    <div class="smd-panel-title">Top companies</div>
                                    <div class="smd-panel-body"><ul class="smd-bar-list" id="smd_company_bars"></ul></div>
                                </div>
                                <div class="smd-panel">
                                    <div class="smd-panel-title">Opened / closed by type</div>
                                    <div class="smd-panel-body">
                                        <div class="smd-mini-label">Opened</div>
                                        <ul class="smd-bar-list" id="smd_type_opened"></ul>
                                        <div class="smd-mini-label">Closed</div>
                                        <ul class="smd-bar-list" id="smd_type_closed"></ul>
                                    </div>
                                </div>

                                <div class="smd-panel smd-panel--wide">
                                    <div class="smd-panel-title">Top branches</div>
                                    <div class="smd-panel-body"><ul class="smd-bar-list" id="smd_branch_bars"></ul></div>
                                </div>

                                <div class="smd-panel smd-panel--full">
                                    <div class="smd-panel-title">Recent activity — opened, closed &amp; status changes</div>
                                    <div class="smd-panel-body smd-feed" id="smd_feed"></div>
                                </div>
                            </section>
                            </div><!-- #mis_tickets_body -->
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script>
    window.STATE_DASHBOARD_CONFIG = {
        workforceApiUrl: 'ajax/get_mis_workforce_data.php',
        ticketsApiUrl: 'ajax/get_mis_tickets_data.php',
        filterOptionsUrl: 'ajax/get_mis_filter_options.php',
        defaultPreset: 'current_fy'
    };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../dashboard/assets/state_dashboard_charts.js?v=2"></script>
    <script src="assets/mis_report_dashboard.js?v=7"></script>
    <script src="assets/mis_report.js?v=2"></script>
</body>
</html>
