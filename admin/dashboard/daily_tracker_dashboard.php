<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="description" content="Aryadibusiness Daily Tracker">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    require_once __DIR__ . '/inc/daily_tracker_helpers.php';
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    $core->setTimeZone();
    $corporate_array = _getTableRecords($conn,'company','where 1');
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-30 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;
    $CorporateID = -1;
    $BranchID = -1;
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    $corporate_ticket_obj = new Corporateticket($conn);
    $status_array = $corporate_ticket_obj->getCorporateTicketStatusArray("All");
    $data['CorporateID'] = $CorporateID;
    $daily_tracker_status = $corporate_ticket_obj->GetDailyTicketStatsbyStatus($data);

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183)
    {
        $_product = "innov";
        $dbh = new Dbh();
        $conn = $dbh->_connectodb();
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    }

    $grand_total = 0;
    $kpi_closed = 0;
    $kpi_open = 0;
    $kpi_hold = 0;
    $kpi_cancel = 0;
    $loopDate = new DateTime($current_date);
    $loopEnd = new DateTime($previous_date);
    while ($loopDate >= $loopEnd) {
        $date_in_process = $loopDate->format('Y-m-d');
        foreach ($status_array as $status) {
            $count = isset($daily_tracker_status[$date_in_process][$status['Status']]) ? (int)$daily_tracker_status[$date_in_process][$status['Status']] : 0;
            $grand_total += $count;
            $tone = trackerTone($status['Status']);
            if ($tone === 'ok') { $kpi_closed += $count; }
            elseif ($tone === 'wait') { $kpi_hold += $count; }
            elseif ($tone === 'bad') { $kpi_cancel += $count; }
            else { $kpi_open += $count; }
        }
        $loopDate->modify('-1 day');
    }
    $day_count = (new DateTime($previous_date))->diff(new DateTime($current_date))->days + 1;
    ?>
    <title><?=$ProductName;?> Daily Tracker</title>
    <style type="text/css">
        #js-page-content {
            font-family: Inter, 'Segoe UI', sans-serif;
            background: #EEF3F8;
        }
        .breadcrumb { display: none; }
        .dt-shell {
            background: #fff;
            border: 1px solid #DCE6F0;
            border-radius: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.07);
            overflow: hidden;
        }
        .dt-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            gap: 12px 16px;
            padding: 18px 20px 16px;
            background: linear-gradient(180deg, #F7FBFF 0%, #fff 100%);
            border-bottom: 1px solid #E8EEF4;
        }
        .dt-toolbar-title {
            flex: 1 1 220px;
            min-width: 200px;
        }
        .dt-toolbar-title h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }
        .dt-toolbar-title p {
            margin: 3px 0 0;
            color: #64748B;
            font-size: 12.5px;
        }
        .dt-field {
            flex: 1 1 200px;
            min-width: 180px;
            max-width: 280px;
        }
        .dt-field label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #64748B;
            margin-bottom: 6px;
        }
        .dt-field .form-control,
        .dt-field .select2-container .select2-selection--single {
            height: 40px !important;
            border: 1px solid #D7E2EE !important;
            border-radius: 10px !important;
            background: #fff;
        }
        .dt-field .select2-container { width: 100% !important; }
        .dt-search-btn {
            height: 40px;
            padding: 0 18px;
            background: #003f88;
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
        }
        .dt-search-btn:hover { background: #045891; color: #fff; }
        .dt-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0;
            border-bottom: 1px solid #E8EEF4;
        }
        .dt-kpi {
            padding: 14px 18px 16px;
            border-right: 1px solid #E8EEF4;
        }
        .dt-kpi:last-child { border-right: none; }
        .dt-kpi-label {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.55px;
            text-transform: uppercase;
            color: #64748B;
            margin-bottom: 4px;
        }
        .dt-kpi-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0F172A;
            line-height: 1;
        }
        .dt-kpi-hint {
            margin-top: 4px;
            font-size: 11px;
            color: #94A3B8;
        }
        .dt-kpi.is-ok .dt-kpi-value { color: #15803D; }
        .dt-kpi.is-info .dt-kpi-value { color: #1D4ED8; }
        .dt-kpi.is-wait .dt-kpi-value { color: #C2410C; }
        .dt-grid-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 20px;
            background: #FAFCFE;
        }
        .dt-grid-head strong {
            font-size: 13px;
            color: #0F172A;
        }
        .dt-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .dt-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #64748B;
            background: #fff;
            border: 1px solid #E8EEF4;
            border-radius: 999px;
            padding: 3px 10px;
        }
        .dt-legend b {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .frame-wrap-premium {
            position: relative;
            overflow: auto;
            max-height: calc(100vh - 340px);
            min-height: 280px;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff;
        }
        table#daily_tracker_html {
            width: 100%;
            min-width: 1080px;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0 !important;
        }
        table#daily_tracker_html thead th {
            position: sticky;
            top: 0;
            z-index: 12;
            background: #F4F7FB !important;
            color: #334155 !important;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
            text-align: center;
            vertical-align: bottom;
            padding: 12px 6px 10px !important;
            min-width: 92px;
            max-width: 118px;
            border: none !important;
            box-shadow: inset 0 -1px 0 #E2E8F0;
        }
        table#daily_tracker_html thead th.tone-ok { box-shadow: inset 0 3px 0 #22C55E, inset 0 -1px 0 #E2E8F0; }
        table#daily_tracker_html thead th.tone-info { box-shadow: inset 0 3px 0 #3B82F6, inset 0 -1px 0 #E2E8F0; }
        table#daily_tracker_html thead th.tone-wait { box-shadow: inset 0 3px 0 #F97316, inset 0 -1px 0 #E2E8F0; }
        table#daily_tracker_html thead th.tone-bad { box-shadow: inset 0 3px 0 #EF4444, inset 0 -1px 0 #E2E8F0; }
        table#daily_tracker_html tbody td {
            padding: 7px 6px !important;
            text-align: center;
            vertical-align: middle !important;
            border: none !important;
            border-bottom: 1px solid #F1F5F9 !important;
            background: #fff;
        }
        table#daily_tracker_html tbody tr.is-weekend td { background: #F8FAFC; }
        table#daily_tracker_html tbody tr.is-today td { background: #EFF6FF; }
        table#daily_tracker_html tbody tr:hover td { background: #F1F5F9 !important; }
        .dt-date-head,
        .dt-date-cell,
        table#daily_tracker_html thead th:first-child,
        table#daily_tracker_html tbody td:first-child,
        table#daily_tracker_html tfoot td:first-child {
            position: sticky;
            left: 0;
            z-index: 11;
            text-align: left !important;
            min-width: 92px !important;
            max-width: 92px;
            padding-left: 16px !important;
            background: #fff;
            box-shadow: 4px 0 12px rgba(15,23,42,0.04);
        }
        table#daily_tracker_html thead th:first-child {
            z-index: 14;
            background: #F4F7FB !important;
        }
        table#daily_tracker_html tbody tr.is-weekend td:first-child { background: #F8FAFC; }
        table#daily_tracker_html tbody tr.is-today td:first-child { background: #EFF6FF; }
        table#daily_tracker_html tbody tr:hover td:first-child { background: #F1F5F9 !important; }
        .dt-date-cell {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .dt-day {
            font-size: 15px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.1;
        }
        .dt-meta {
            font-size: 10px;
            font-weight: 700;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        table#daily_tracker_html tbody tr.is-today .dt-day { color: #1D4ED8; }
        .dt-cell {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 26px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
        }
        .dt-cell-empty {
            min-width: 8px;
            height: 8px;
            background: transparent;
            color: transparent;
        }
        .dt-cell-ok { background: #DCFCE7; color: #15803D; }
        .dt-cell-info { background: #DBEAFE; color: #1D4ED8; }
        .dt-cell-wait { background: #FFEDD5; color: #C2410C; }
        .dt-cell-bad { background: #FEE2E2; color: #B91C1C; }
        table#daily_tracker_html tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 10;
            background: #F8FAFC !important;
            padding: 10px 6px !important;
            text-align: center;
            border: none !important;
            box-shadow: inset 0 1px 0 #E2E8F0;
            font-weight: 800;
        }
        table#daily_tracker_html tfoot td:first-child {
            z-index: 13;
            color: #0F172A;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .dt-loading {
            display: none;
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.78);
            z-index: 30;
            align-items: center;
            justify-content: center;
            color: #003f88;
            font-weight: 700;
        }
        .frame-wrap-premium.is-loading .dt-loading { display: flex; }
        @media (max-width: 991px) {
            .dt-kpis { grid-template-columns: 1fr 1fr; }
            .dt-kpi:nth-child(2) { border-right: none; }
            .frame-wrap-premium { max-height: none; }
        }
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <?php
    if(isset($product_configuration['favicon']))
    {
        echo '<link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/'.$product_configuration['favicon'].'">';
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>
<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" name="UserType" id="UserType" value="<?php echo htmlspecialchars($UserType);?>">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <div class="dt-shell">
                        <div class="dt-toolbar">
                            <div class="dt-toolbar-title">
                                <h1>Daily Tracker</h1>
                                <p>Ticket movement by status, one row per day</p>
                            </div>
                            <?php if($CorporateID == -1) { ?>
                            <div class="dt-field">
                                <label for="corporate_name">Corporate</label>
                                <select class="form-control w-100" id="corporate_name" name="corporate_name" onchange="LoadDailyTracker(this.value)">
                                    <option value="-1">All corporates</option>
                                    <?php foreach($corporate_array as $corporate) { ?>
                                        <option value="<?php echo $corporate['ID'];?>"><?php echo htmlspecialchars($corporate['CompanyName']);?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php } ?>
                            <div class="dt-field">
                                <label for="filter_date">Date range</label>
                                <input type="text" class="form-control" id="filter_date" value="<?php echo htmlspecialchars($date_range); ?>">
                            </div>
                            <button class="btn dt-search-btn" type="button" onclick="LoadDailyTracker(<?php echo (int)$CorporateID;?>)">Apply</button>
                        </div>

                        <div class="dt-kpis">
                            <div class="dt-kpi">
                                <div class="dt-kpi-label">Total activity</div>
                                <div class="dt-kpi-value" id="dt_kpi_total"><?php echo number_format($grand_total); ?></div>
                                <div class="dt-kpi-hint" id="dt_kpi_days"><?php echo (int)$day_count; ?> days in range</div>
                            </div>
                            <div class="dt-kpi is-ok">
                                <div class="dt-kpi-label">Closed / approved</div>
                                <div class="dt-kpi-value" id="dt_kpi_closed"><?php echo number_format($kpi_closed); ?></div>
                                <div class="dt-kpi-hint">Completed</div>
                            </div>
                            <div class="dt-kpi is-info">
                                <div class="dt-kpi-label">Open / in flow</div>
                                <div class="dt-kpi-value" id="dt_kpi_open"><?php echo number_format($kpi_open); ?></div>
                                <div class="dt-kpi-hint">Active work</div>
                            </div>
                            <div class="dt-kpi is-wait">
                                <div class="dt-kpi-label">Hold / pending</div>
                                <div class="dt-kpi-value" id="dt_kpi_hold"><?php echo number_format($kpi_hold); ?></div>
                                <div class="dt-kpi-hint" id="dt_kpi_cancel"><?php echo $kpi_cancel ? number_format($kpi_cancel).' cancelled' : 'Waiting'; ?></div>
                            </div>
                        </div>

                        <div class="dt-grid-head">
                            <strong>Status heatmap</strong>
                            <div class="dt-legend">
                                <span><b style="background:#22C55E"></b>Closed</span>
                                <span><b style="background:#3B82F6"></b>In progress</span>
                                <span><b style="background:#F97316"></b>Hold</span>
                                <span><b style="background:#EF4444"></b>Cancel</span>
                            </div>
                        </div>
                        <div class="frame-wrap-premium" id="daily_tracker_status_html">
                            <div class="dt-loading">Updating…</div>
                            <?php echo renderDailyTrackerTable($status_array, $daily_tracker_status, $previous_date, $current_date); ?>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php') ?>
            </div>
        </div>
    </div>
    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
    ?>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/modules/daily_tracker.js?v=2"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script>
    $(document).ready(function() {
        if ($("#corporate_name").length) {
            $("#corporate_name").select2();
        }
        $('#filter_date').daterangepicker({
            locale: { format: 'YYYY-MM-DD' }
        });
        $("#nav_daily_tracker_dashboard").addClass("active");
    });
    </script>
</body>
</html>
