<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
<head>
    <meta charset="utf-8">
    
    <meta name="description" content="TechXpert Analytics Dashboard">
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    require_once __DIR__ . '/inc/state_dashboard_scope.php';
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    
    $core->setTimeZone();
    $corporate_array = _getTableRecords($conn,'company','where 1');
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;

    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if(isset($_SESSION['Roles']['EmployeeID']))
    {
        $Employee_ID = $_SESSION['Roles']['EmployeeID'];
    }

    $BranchAccountManager = userHasBranchAccountManagerAccess($_SESSION);
    $sql_in_branch_account_string = "";
    if ($BranchAccountManager) {
        $branch_obj = new Branch($conn);
        $branches_array = $branch_obj->getMappedAccountBranchesofAccountBranchManager($Employee_ID);
        $branches_array_mapped = array();
        foreach($branches_array as $i_branch)
        {
            array_push($branches_array_mapped,$i_branch['ID']);
        }
        $sql_in_branch_account_string = "'" . implode("', '", $branches_array_mapped) . "'";
    }

    // STate Manager Login
    $StateManager = userHasStateCorporateLeadAccess($_SESSION);
    
    $state_object = new State($conn);
    $sql_in_state_string = "";
    
    if($StateManager)
    {
        $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
        $state_array_mapped = array();
        foreach($state_array as $state_mapped)
        {
            array_push($state_array_mapped,$state_mapped['StateName']);
        }
        $sql_in_state_string = "'" . implode("', '", $state_array_mapped) . "'";
        $sql_in_branch_account_string = "";
    }
    if($sql_in_state_string != "")
    {
        $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where BranchState IN (".$sql_in_state_string."))";
        $corporate_array = _getTableRecords($conn,'company',$where);
    }
    else
    {
        if($BranchAccountManager)
        {
            $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where ID IN (".$sql_in_branch_account_string."))";
            $corporate_array = _getTableRecords($conn,'company',$where);
        }
        else
        {
            $corporate_array = _getTableRecords($conn,'company','where IsActive = 1');
        }
    }
    

    $ProductName = "Aryadibussiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "techx-14(1).png";
    if(isset($product_configuration['logo']))
    {
        $logoImg = $product_configuration['logo'];
    }   
    ?>
    <title>
        <?=$ProductName;?> Analytics Dashboard
    </title>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
        /* ═══════════════════════════════════════════════════════════════
           ANALYTICS DASHBOARD — PREMIUM REDESIGN
           Brand Colors from Aryadi Business Logo:
           Deep Navy: #003f88 | Royal Blue: #045891 | Sky Blue: #5BB6E9
        ═══════════════════════════════════════════════════════════════ */
        :root {
            --ab-navy: #003f88;
            --ab-royal: #045891;
            --ab-sky: #5BB6E9;
            --ab-light: #E8F4FD;
            --ab-lighter: #F0F7FF;
            --ab-dark: #1E293B;
            --ab-slate: #64748B;
            --ab-success: #10B981;
            --ab-warning: #F59E0B;
            --ab-danger: #EF4444;
            --ab-teal: #0D9488;
            --ab-purple: #7C3AED;
            --ab-radius: 12px;
            --ab-shadow: 0 4px 24px rgba(0, 63, 136, 0.08);
            --ab-shadow-hover: 0 8px 32px rgba(0, 63, 136, 0.16);
        }

        /* Base Resets & Typography */
        .page-content {
            background-color: #F8FAFC !important;
            font-family: 'Inter', 'Poppins', sans-serif;
        }
        
        .page-content .panel {
            border: none;
            border-radius: var(--ab-radius);
            box-shadow: var(--ab-shadow);
            margin-bottom: 24px !important;
            background: #fff;
            overflow: hidden;
        }
        .page-content .panel-hdr {
            background: #fff;
            border-bottom: 1px solid #F1F5F9;
            padding: 16px 20px;
        }
        .page-content .panel-hdr h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ab-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .page-content .panel-hdr h2::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 18px;
            background: var(--ab-sky);
            border-radius: 2px;
        }

        /* Custom Alert Boxes */
        .alert-ab-primary {
            background: linear-gradient(135deg, var(--ab-navy) 0%, var(--ab-royal) 100%);
            border: none;
            color: #fff;
            border-radius: var(--ab-radius);
            box-shadow: var(--ab-shadow);
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        .alert-ab-primary::after {
            content: '\f05a';
            font-family: 'Font Awesome 5 Pro', 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 8rem;
            opacity: 0.1;
            color: #fff;
        }
        .alert-ab-primary .h5 {
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 4px;
            color: #fff;
        }
        .alert-ab-primary .text-muted {
            color: rgba(255,255,255,0.8) !important;
        }
        .btn-ab-white {
            background: rgba(255,255,255,0.2);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-ab-white:hover {
            background: #fff;
            color: var(--ab-navy);
        }

        /* Filter Card */
        .ab-filter-card {
            background: linear-gradient(135deg, var(--ab-lighter) 0%, #fff 100%);
            padding: 20px 24px;
        }
        .ab-filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--ab-slate);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }
        .ab-filter-group .form-control,
        .ab-filter-group select {
            border-radius: 8px;
            border: 1.5px solid #CBD5E1;
            padding: 8px 12px;
            font-size: 0.85rem;
            height: 38px;
            box-shadow: none;
        }
        .ab-filter-group .form-control:focus,
        .ab-filter-group select:focus {
            border-color: var(--ab-sky);
            box-shadow: 0 0 0 3px rgba(91, 182, 233, 0.15);
        }
        .btn-ab-search {
            background: linear-gradient(135deg, var(--ab-navy) 0%, var(--ab-royal) 100%);
            color: #fff !important;
            border: none;
            border-radius: 8px;
            padding: 8px 24px;
            font-size: 0.9rem;
            font-weight: 600;
            height: 38px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 63, 136, 0.2);
        }
        .btn-ab-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 63, 136, 0.3);
        }
        
        /* Modern Status Cards */
        .status-metric-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid rgba(0,0,0,0.04);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            height: 100%;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03), 0 1px 3px rgba(0,0,0,0.02);
            text-decoration: none !important;
            position: relative;
            overflow: hidden;
        }
        .status-metric-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.4) 0%, rgba(255,255,255,0) 100%);
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 0;
        }
        .status-metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.08), 0 4px 10px rgba(0,0,0,0.04);
            border-color: rgba(91, 182, 233, 0.4);
        }
        .status-metric-card:hover::before {
            opacity: 1;
        }
        .status-icon-box {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
        }
        .status-icon-box svg, .status-icon-box i {
            width: 26px;
            height: 26px;
            stroke-width: 2.2;
            font-size: 1.5rem; /* fallback for fontawesome */
        }
        .status-content {
            flex: 1;
            min-width: 0;
            position: relative;
            z-index: 1;
        }
        .status-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #1E293B;
            line-height: 1;
        }
        /* Override legacy badge styling from renderAnalyticsStatusCountBadge */
        .status-value .badge {
            background: transparent !important;
            color: inherit !important;
            font-size: 1.6rem !important;
            font-weight: 800 !important;
            padding: 0 !important;
            width: auto !important;
            min-width: auto !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            text-align: left !important;
            display: inline-block !important;
        }
        .status-value a:hover {
            opacity: 0.7;
            text-decoration: none !important;
        }
        .status-progress-wrap {
            margin-top: 8px;
        }
        .status-progress-bar {
            height: 6px;
            background: #F1F5F9;
            border-radius: 3px;
            overflow: hidden;
        }
        .status-progress-fill {
            height: 100%;
            border-radius: 3px;
        }
        .status-percent {
            font-size: 0.7rem;
            font-weight: 600;
            text-align: right;
            display: block;
            margin-top: 4px;
        }

        /* General Fixes */
        .select2-container {
            z-index: 1;
        }
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1.5px solid #CBD5E1 !important;
            border-radius: 8px !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
        }
    </style>
    <?php 
    if(isset($product_configuration['favicon']))
    {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness")
    {
        include("../css/client_generated_css.php");
    }
    ?>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" id="UserType" value="<?php echo $UserType;?>" />
    <input type="hidden" id="BranchID" value="<?php echo $BranchID;?>" />
    <input type="hidden" id="AnalyticsCorporateID" value="<?php echo $CorporateID;?>" />
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->
    <div class="page-wrapper">
        <div class="page-inner">
            <?php
            include('../navigation/admin_navigation.php');
            ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                include('../includes/common_header.php');
                ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics Dashboard</a></li>

                    </ol>
                    <?php 
                    if($StateManager)
                    {
                        ?>
                    <div class="alert alert-ab-primary mb-4">
                        <div class="d-flex align-items-center">
                            <div class="mr-4" style="font-size: 2.5rem; opacity: 0.9;">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div>
                                <h5 class="h5">My State Dashboard</h5>
                                <div class="mb-3">
                                    <?php
                                    $stateCount = count($state_array_mapped);
                                    if ($stateCount <= 4) {
                                        echo '<span style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">States managed: </span>';
                                        foreach ($state_array_mapped as $i_state) {
                                            echo '<span class="badge" style="background: rgba(255,255,255,0.2); margin-right: 4px;">' . htmlspecialchars($i_state, ENT_QUOTES, 'UTF-8') . '</span>';
                                        }
                                    } else {
                                        echo '<span style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">' . (int)$stateCount . ' states in your scope</span>';
                                    }
                                    ?>
                                </div>
                                <div>
                                    <a href="manager_dashboard" class="btn-ab-white">
                                        Open State Dashboard <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    if ($BranchAccountManager && !$StateManager)
                    {
                        ?>
                    <div class="alert alert-ab-primary mb-4">
                        <div class="d-flex align-items-center">
                            <div class="mr-4" style="font-size: 2.5rem; opacity: 0.9;">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <h5 class="h5">My Branch Dashboard</h5>
                                <div class="mb-3">
                                    <span style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">
                                        <?= (int)count($branches_array); ?> branch<?= count($branches_array) === 1 ? '' : 'es'; ?> assigned. Use filters below to select a branch.
                                    </span>
                                </div>
                                <div>
                                    <a href="manager_dashboard" class="btn-ab-white">
                                        Open Branch Dashboard <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                    <input type="hidden" id="sql_in_state_string" value="<?php echo $sql_in_state_string;?>">
                    <input type="hidden" id="sql_in_branch_account_string" value="<?php echo $sql_in_branch_account_string;?>">
                    <div class="panel mb-4">  
                        <div class="panel-content ab-filter-card">
                            <div class="row align-items-end">
                                <?php if($CorporateID == -1) { ?>
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0 ab-filter-group">
                                    <label>Select Corporate</label>
                                    <select class="select2 form-control w-100" id="corporate_name" name="corporate_name" onchange="RefreshBranchAnalytics(this.value)">
                                        <option value="-1">All Corporates</option>
                                        <?php foreach($corporate_array as $corporate) { ?>
                                            <option value="<?php echo $corporate['ID'];?>">
                                                <?php echo $corporate['CompanyName'];?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <?php } ?>
                                
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0 ab-filter-group">
                                    <label>Date Range</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white" style="border-right: none; border-color: #CBD5E1; color: var(--ab-slate);"><i class="fas fa-calendar-alt"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>" style="border-left: none;">         
                                    </div>
                                </div>
                                
                                <div class="col-lg-4 col-md-6 mb-3 mb-lg-0" id="state_region_view">
                                    <!-- Populated by AJAX -->
                                </div>
                                
                                <div class="col-lg-2 col-md-6 mb-3 mb-lg-0">
                                    <button type="button" onclick="GenerateDashboard(<?php echo $CorporateID;?>);" class="btn-ab-search w-100">
                                        <i class="fas fa-search mr-1"></i> Search
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="analytics_corporate_dashboard" >

                        <!-- Status buttons -->
                        <div class="row" id="status_buttons_div">
                            
                        </div>
                        <div class="spinner-grow rounded-0 text-danger mb-5" role="status" id="status_buttons_div_loader" style="margin-left: 48%;display:none;">
                            <span class="sr-only">Loading...</span>
                        </div>

                        <div class="row" id="quotation_dashboard_div">
                                    
                        </div>
                       
                            <div id="panel-rnm" class="row panel" style="margin-bottom:1% ;display:none;">
                                <div class="panel-hdr">
                                    <h2>
                                        Detailed Status
                                    </h2>
                                </div>

                                <div class="row" style="margin:0px;">
                                    <div class="col-lg-4 col-xl-4 panel" id="corporate_branch_panel">  
                                    </div>
                                    <div class="col-lg-8 col-xl-8" id="corporate_branch_rest_panel">
                                        <div class="row panel" id="corporate_name_ticket_count_fetch">
                                            
                                        </div>
                                        <div class="row border">
                                            
                                            <div class="col-lg-6 col-xl-6" id="type_status_view">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6 col-xl-6 panel" id="ticket_status_bar_graph" style="display: none;">
                                                <!-- <div class="panel-container show">
                                                    <div class="panel-content"> -->
                                                        <canvas id="ticket_status_graph_id" height="300px;"></canvas>
                                                    <!-- </div>
                                                </div> -->
                                            </div>
                                            <div class="col-lg-6 col-xl-6 panel" id="type_status_pie_region_graph" style="display:none;">
                                                <canvas id="region_status_graph_id" height="300px;"></canvas>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-xl-12" id="state_wise_ticket_count" style="display:none;">
                                                <canvas id="state_wise_ticket_graph_id" height="150px;"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                     
                    </div>
                </main>

                <div class="modal fade" id="analyticsStatusTicketsModal" tabindex="-1" role="dialog" aria-labelledby="analyticsStatusTicketsModalTitle" aria-hidden="true" style="z-index: 10000;">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="analyticsStatusTicketsModalTitle">Tickets</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p id="analyticsStatusTicketsSummary" class="text-muted mb-2"></p>
                                <div id="analyticsStatusTicketsLoader" class="text-center py-3" style="display:none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                                <div id="analyticsStatusTicketsError" class="alert alert-danger" style="display:none;"></div>
                                <div class="table-responsive" id="analyticsStatusTicketsTableWrap">
                                    <table class="table table-bordered table-sm m-0">
                                        <thead>
                                            <tr>
                                                <th>Ticket ID</th>
                                                <th>Branch Site</th>
                                            </tr>
                                        </thead>
                                        <tbody id="analyticsStatusTicketsTableBody"></tbody>
                                    </table>
                                </div>
                                <p id="analyticsStatusTicketsEmpty" class="text-muted text-center py-3" style="display:none;">No tickets found.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                include('../includes/common_footer.php')
                ?>
                <!-- END Page Footer -->
            </div>
        </div>
    </div>
    
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <!--script src="../js/statistics/chartjs/chartjs.bundle.js"></script-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src= "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
   
    <script src="../js/modules/analytics_dashboard.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/statistics/easypiechart/easypiechart.bundle.js"></script>
    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_analytics_dashboard").addClass("active");
        if($("#corporate_name").length)
        {
            $("#corporate_name").select2();
        }
        $('#filter_date').daterangepicker({
            locale: {
                format: 'YYYY-MM-DD'
            }
        });

     $('#ad_branch_name').on('select2:select', function (e) {
            $(this).select2('close');
        });
    }); 
    RefreshBranchAnalytics(<?php echo $CorporateID; ?>);
    
    </script>
    
</body>

</html>