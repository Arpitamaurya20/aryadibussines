<?php session_start();?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Aryadibusiness Analytics Dashboard v2 - Advanced KPI & Performance Metrics">
    
    <?php
    include('../includes/common_head_content.php');
    include('../includes/autoloader.inc.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    $core = new Core();
    
    $core->setTimeZone();
    $corporate_array = _getTableRecords($conn,'company','where 1');
    $current_date = date("Y-m-d");
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;
    
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    
    if($UserType == "Corporate Branch User") {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    if($UserType == "Corporate Admin") {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if(isset($_SESSION['Roles']['EmployeeID'])) {
        $Employee_ID = $_SESSION['Roles']['EmployeeID'];
    }

    $BranchAccountManager = false;
    $sql_in_branch_account_string = "";
    if(CheckRole($_SESSION,"Branch Account Manager") == true) {
        $BranchAccountManager = true;
        $branch_obj = new Branch($conn);
        $branches_array = $branch_obj->getMappedAccountBranchesofAccountBranchManager($Employee_ID);
        $branches_array_mapped = array();
        foreach($branches_array as $i_branch) {
            array_push($branches_array_mapped,$i_branch['ID']);
        }
        $sql_in_branch_account_string = "'" . implode("', '", $branches_array_mapped) . "'";
    }

    $StateManager = false;
    if(CheckRole($_SESSION,"State Corporate Lead") == true) {
        $StateManager = true;
    }
    
    $state_object = new State($conn);
    $sql_in_state_string = "";
    
    if($StateManager) {
        $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
        $state_array_mapped = array();
        foreach($state_array as $state_mapped) {
            array_push($state_array_mapped,$state_mapped['StateName']);
        }
        $sql_in_state_string = "'" . implode("', '", $state_array_mapped) . "'";
        $sql_in_branch_account_string = "";
    }
    
    if($sql_in_state_string != "") {
        $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where BranchState IN (".$sql_in_state_string."))";
        $corporate_array = _getTableRecords($conn,'company',$where);
    } else {
        if($BranchAccountManager) {
            $where = " where IsActive = 1 and ID IN (Select CompanyID from branch where ID IN (".$sql_in_branch_account_string."))";
            $corporate_array = _getTableRecords($conn,'company',$where);
        } else {
            $corporate_array = _getTableRecords($conn,'company','where IsActive = 1');
        }
    }

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo'])) {
        $logoImg = $product_configuration['logo'];
    }   
    ?>
    
    <title><?=$ProductName;?> Analytics Dashboard v2</title>
    
    <!-- Enhanced CSS Framework -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    
    <style type="text/css">
        :root {
            --primary-color: #2b95d6;
            --secondary-color: #045891;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --gradient-primary: linear-gradient(135deg, #2b95d6 0%, #045891 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-danger: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --gradient-info: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .dashboard-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: var(--shadow-lg);
        }

        .dashboard-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .dashboard-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .kpi-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .kpi-card.success::before { background: var(--gradient-success); }
        .kpi-card.warning::before { background: var(--gradient-warning); }
        .kpi-card.danger::before { background: var(--gradient-danger); }
        .kpi-card.info::before { background: var(--gradient-info); }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
        }

        .kpi-icon.success { background: var(--gradient-success); }
        .kpi-icon.warning { background: var(--gradient-warning); }
        .kpi-icon.danger { background: var(--gradient-danger); }
        .kpi-icon.info { background: var(--gradient-info); }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .kpi-label {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .kpi-change {
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            display: inline-block;
        }

        .kpi-change.positive {
            background: #dcfce7;
            color: #166534;
        }

        .kpi-change.negative {
            background: #fef2f2;
            color: #dc2626;
        }

        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .chart-subtitle {
            font-size: 0.9rem;
            color: #6b7280;
            margin: 0;
        }

        .filter-panel {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .btn-primary-v2 {
            background: linear-gradient(135deg, #00d2ff 0%, #0066eb 100%) !important;
            color: white !important;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3) !important;
        }

        .btn-primary-v2:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 210, 255, 0.45) !important;
            filter: brightness(1.1);
        }

        .select2-container--default .select2-selection--single {
            border-radius: 0.75rem;
            border: 1px solid #d1d5db;
            height: 45px;
            padding: 0.5rem;
        }

        .form-control {
            border-radius: 0.75rem;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .state-manager-alert {
            background: var(--gradient-info);
            color: white;
            border: none;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }

        .badge-v2 {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
            font-size: 0.8rem;
            margin: 0.25rem;
            display: inline-block;
        }

        .loader-v2 {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .performance-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success-color);
            animation: pulse 2s infinite;
        }

        .performance-indicator.warning { background: var(--warning-color); }
        .performance-indicator.danger { background: var(--danger-color); }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
        }

        .trend-arrow {
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .trend-up { color: var(--success-color); }
        .trend-down { color: var(--danger-color); }

        .advanced-filter-panel {
            background: var(--gradient-primary);
            color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: end;
        }

        .filter-col {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            color: white;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 0.75rem;
            backdrop-filter: blur(10px);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
    </style>
    
    <?php 
    if(isset($product_configuration['favicon'])) {
        ?>
        <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/<?=$product_configuration['favicon'];?>">
        <?php
    }
    if($ProductName != "Aryadibusiness") {
        include("../css/client_generated_css.php");
    }
    ?>
</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" id="UserType" value="<?php echo $UserType;?>" />
    <input type="hidden" id="sql_in_state_string" value="<?php echo $sql_in_state_string;?>">
    <input type="hidden" id="sql_in_branch_account_string" value="<?php echo $sql_in_branch_account_string;?>">
    
    <?php include('../js/theme_settings.js'); ?>
    
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                
                <main id="js-page-content" role="main" class="page-content">
                    
                    <!-- Enhanced Dashboard Header -->
                    <div class="dashboard-header animate__animated animate__fadeInDown">
                        <div class="container-fluid">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h1 class="dashboard-title">
                                        <i class="fas fa-chart-line mr-3"></i>
                                        <?=$ProductName;?> Analytics v2
                                    </h1>
                                    <p class="dashboard-subtitle">Advanced Performance Metrics & Business Intelligence</p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div class="summary-stats">
                                        <div class="stat-item">
                                            <div class="stat-value" id="total-entities">--</div>
                                            <div class="stat-label">Total Entities</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value" id="active-sessions">--</div>
                                            <div class="stat-label">Active Sessions</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breadcrumb -->
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Analytics Dashboard v2</a></li>
                    </ol>

                    <?php if($StateManager) { ?>
                    <div class="state-manager-alert animate__animated animate__fadeInLeft">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <i class="fas fa-map-marked-alt fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-2">State Manager Dashboard</h5>
                                <p class="mb-2">Managing multiple states with comprehensive oversight</p>
                                <div>
                                    <?php foreach($state_array_mapped as $i_state) { ?>
                                        <span class="badge-v2" style="background: rgba(255,255,255,0.2);"><?php echo $i_state;?></span>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Advanced Filters Panel -->
                    <div class="advanced-filter-panel animate__animated animate__fadeInUp">
                        <h5 class="mb-3">
                            <i class="fas fa-filter mr-2"></i>
                            Advanced Analytics Filters
                        </h5>
                        <div class="filter-row">
                            <?php if($CorporateID == -1) { ?>
                            <div class="filter-col">
                                <label class="filter-label">Corporate Entity</label>
                                <select class="select2 form-control" id="corporate_name" name="corporate_name" onchange="RefreshBranchAnalyticsV2(this.value)">
                                    <option value="-1">All Corporates</option>
                                    <?php foreach($corporate_array as $corporate) { ?>
                                        <option value="<?php echo $corporate['ID'];?>">
                                            <?php echo $corporate['CompanyName'];?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <?php } ?>
                            
                            <div class="filter-col">
                                <label class="filter-label">Date Range</label>
                                <input type="text" class="form-control" id="filter_date" placeholder="Select date range" value="<?php echo $date_range; ?>">
                            </div>
                            
                            <div class="filter-col">
                                <label class="filter-label">Region/State</label>
                                <div id="state_region_view">
                                    <select class="form-control" id="region_filter">
                                        <option value="">All Regions</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filter-col">
                                <label class="filter-label">&nbsp;</label>
                                <button type="button" onclick="GenerateDashboardV2(<?php echo $CorporateID;?>);" class="btn btn-primary-v2 w-100">
                                    <i class="fas fa-search mr-2"></i>
                                    Generate Analytics
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Metrics Grid -->
                    <div id="kpi-metrics-section">
                        <h4 class="mb-4">
                            <i class="fas fa-tachometer-alt mr-2"></i>
                            Key Performance Indicators
                        </h4>
                        <div class="metric-grid" id="kpi-cards-container">
                            <!-- KPI Cards will be dynamically loaded here -->
                        </div>
                    </div>

                    <!-- Charts and Graphs Section -->
                    <div id="analytics_corporate_dashboard_v2">
                        
                        <!-- Performance Overview -->
                        <div class="chart-container animate__animated animate__fadeInUp">
                            <div class="chart-header">
                                <div>
                                    <h5 class="chart-title">Performance Trends</h5>
                                    <p class="chart-subtitle">Multi-metric performance analysis over time</p>
                                </div>
                                <div class="performance-indicator" id="performance-status"></div>
                            </div>
                            <canvas id="performance-trends-chart" height="100"></canvas>
                        </div>

                        <!-- Charts Grid -->
                        <div class="chart-grid">
                            <!-- Status Distribution -->
                            <div class="chart-container animate__animated animate__fadeInLeft">
                                <div class="chart-header">
                                    <div>
                                        <h5 class="chart-title">Status Distribution</h5>
                                        <p class="chart-subtitle">Current status breakdown</p>
                                    </div>
                                </div>
                                <canvas id="status-distribution-chart"></canvas>
                            </div>

                            <!-- Regional Performance -->
                            <div class="chart-container animate__animated animate__fadeInRight">
                                <div class="chart-header">
                                    <div>
                                        <h5 class="chart-title">Regional Performance</h5>
                                        <p class="chart-subtitle">Geographic performance metrics</p>
                                    </div>
                                </div>
                                <canvas id="regional-performance-chart"></canvas>
                            </div>

                            <!-- Trend Analysis -->
                            <div class="chart-container animate__animated animate__fadeInLeft">
                                <div class="chart-header">
                                    <div>
                                        <h5 class="chart-title">Monthly Trends</h5>
                                        <p class="chart-subtitle">Month-over-month analysis</p>
                                    </div>
                                </div>
                                <canvas id="monthly-trends-chart"></canvas>
                            </div>

                            <!-- Category Breakdown -->
                            <div class="chart-container animate__animated animate__fadeInRight">
                                <div class="chart-header">
                                    <div>
                                        <h5 class="chart-title">Category Analysis</h5>
                                        <p class="chart-subtitle">Performance by category</p>
                                    </div>
                                </div>
                                <canvas id="category-analysis-chart"></canvas>
                            </div>
                        </div>

                        <!-- Detailed Analytics Panel -->
                        <div class="chart-container" id="detailed-analytics-panel" style="display: none;">
                            <div class="chart-header">
                                <div>
                                    <h5 class="chart-title">Detailed Analytics</h5>
                                    <p class="chart-subtitle">Comprehensive performance breakdown</p>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-lg-4" id="branch-summary-panel">
                                    <!-- Branch summary content -->
                                </div>
                                <div class="col-lg-8">
                                    <div class="row">
                                        <div class="col-lg-6" id="performance-metrics-panel">
                                            <!-- Performance metrics -->
                                        </div>
                                        <div class="col-lg-6" id="efficiency-metrics-panel">
                                            <!-- Efficiency metrics -->
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-lg-12" id="state-wise-analysis">
                                            <canvas id="state-wise-detailed-chart" height="120"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Loading Indicators -->
                        <div class="text-center" id="dashboard-loader" style="display: none;">
                            <div class="loader-v2"></div>
                            <p class="mt-3 text-muted">Loading analytics data...</p>
                        </div>

                        <!-- Real-time Updates Panel -->
                        <div class="chart-container" id="realtime-updates-panel">
                            <div class="chart-header">
                                <div>
                                    <h5 class="chart-title">
                                        <i class="fas fa-broadcast-tower mr-2"></i>
                                        Real-time Updates
                                    </h5>
                                    <p class="chart-subtitle">Live system metrics and notifications</p>
                                </div>
                                <div class="performance-indicator" id="realtime-status"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <canvas id="realtime-activity-chart" height="150"></canvas>
                                </div>
                                <div class="col-md-6">
                                    <div id="recent-activities-feed" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Real-time feed content -->
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </main>
                
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    
    <!-- Enhanced Chart Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment@1.0.1/dist/chartjs-adapter-moment.bundle.min.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/statistics/easypiechart/easypiechart.bundle.js"></script>
    
    <!-- Custom Analytics v2 Script -->
    <script>
        // Global variables for analytics v2
        let analyticsChartsV2 = {};
        let realtimeUpdateInterval;
        let currentFilters = {
            corporateId: <?php echo $CorporateID; ?>,
            startDate: '<?php echo $previous_date; ?>',
            endDate: '<?php echo $current_date; ?>',
            region: '',
            branch: ''
        };

        $(document).ready(function() {
            initializeDashboardV2();
        });

        function initializeDashboardV2() {
            // Initialize navigation
            $("#js-nav-menu").addClass("active open");
            $("#nav_analytics_dashboard").addClass("active");
            
            // Initialize form controls
            if($("#corporate_name").length) {
                $("#corporate_name").select2({
                    placeholder: "Select Corporate Entity",
                    allowClear: true
                });
            }
            
            // Initialize date range picker with enhanced options
            $('#filter_date').daterangepicker({
                locale: { format: 'YYYY-MM-DD' },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 3 Months': [moment().subtract(3, 'months'), moment()],
                    'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                    'This Year': [moment().startOf('year'), moment().endOf('year')]
                },
                startDate: moment().subtract(365, 'days'),
                endDate: moment(),
                maxDate: moment()
            });

            // Initialize region filter
            $('#region_filter').select2({
                placeholder: "Select Region/State",
                allowClear: true
            });

            // Load initial dashboard
            GenerateDashboardV2(<?php echo $CorporateID; ?>);
            
            // Initialize real-time updates
            startRealtimeUpdates();
        }

        function GenerateDashboardV2(corporateId) {
            showLoader();
            
            // Update filters
            currentFilters.corporateId = corporateId || -1;
            const dateRange = $('#filter_date').val().split(' - ');
            currentFilters.startDate = dateRange[0];
            currentFilters.endDate = dateRange[1];
            currentFilters.region = $('#region_filter').val() || '';

            // Load KPI metrics
            loadKPIMetrics();
            
            // Load all charts
            loadPerformanceTrends();
            loadStatusDistribution();
            loadRegionalPerformance();
            loadMonthlyTrends();
            loadCategoryAnalysis();
            
            hideLoader();
        }

        function RefreshBranchAnalyticsV2(corporateId) {
            GenerateDashboardV2(corporateId);
        }

        function showLoader() {
            $('#dashboard-loader').show();
        }

        function hideLoader() {
            $('#dashboard-loader').hide();
        }

        function loadKPIMetrics() {
            // Sample KPI data - replace with actual AJAX calls
            const kpiData = [
                {
                    icon: 'fas fa-tickets-alt',
                    label: 'Total Tickets',
                    value: '12,847',
                    change: '+15.2%',
                    changeType: 'positive',
                    type: 'primary'
                },
                {
                    icon: 'fas fa-check-circle',
                    label: 'Resolved Tickets',
                    value: '10,234',
                    change: '+8.7%',
                    changeType: 'positive',
                    type: 'success'
                },
                {
                    icon: 'fas fa-clock',
                    label: 'Avg Resolution Time',
                    value: '4.2h',
                    change: '-12.3%',
                    changeType: 'positive',
                    type: 'info'
                },
                {
                    icon: 'fas fa-users',
                    label: 'Active Users',
                    value: '2,156',
                    change: '+23.1%',
                    changeType: 'positive',
                    type: 'warning'
                },
                {
                    icon: 'fas fa-star',
                    label: 'Satisfaction Score',
                    value: '4.8/5',
                    change: '+0.3',
                    changeType: 'positive',
                    type: 'success'
                },
                {
                    icon: 'fas fa-exclamation-triangle',
                    label: 'Critical Issues',
                    value: '23',
                    change: '-45.2%',
                    changeType: 'positive',
                    type: 'danger'
                }
            ];

            const container = $('#kpi-cards-container');
            container.empty();

            kpiData.forEach((kpi, index) => {
                const trendIcon = kpi.changeType === 'positive' ? 'fa-arrow-up trend-up' : 'fa-arrow-down trend-down';
                const cardHtml = `
                    <div class="kpi-card ${kpi.type} animate__animated animate__fadeInUp" style="animation-delay: ${index * 0.1}s;">
                        <div class="kpi-icon ${kpi.type}">
                            <i class="${kpi.icon}"></i>
                        </div>
                        <div class="kpi-value">${kpi.value}</div>
                        <div class="kpi-label">${kpi.label}</div>
                        <div class="kpi-change ${kpi.changeType}">
                            ${kpi.change}
                            <i class="fas ${trendIcon} trend-arrow"></i>
                        </div>
                    </div>
                `;
                container.append(cardHtml);
            });

            // Update summary stats
            $('#total-entities').text(kpiData[0].value);
            $('#active-sessions').text(kpiData[3].value);
        }

        function loadPerformanceTrends() {
            const ctx = document.getElementById('performance-trends-chart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (analyticsChartsV2.performanceTrends) {
                analyticsChartsV2.performanceTrends.destroy();
            }

            const data = {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Tickets Created',
                        data: [1200, 1450, 1100, 1800, 1600, 1750, 1900, 2100, 1800, 2200, 2000, 1950],
                        borderColor: 'rgba(43, 149, 214, 1)',
                        backgroundColor: 'rgba(43, 149, 214, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Tickets Resolved',
                        data: [1100, 1300, 1050, 1650, 1500, 1600, 1750, 1950, 1700, 2050, 1850, 1800],
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Customer Satisfaction',
                        data: [4.2, 4.3, 4.1, 4.5, 4.4, 4.6, 4.7, 4.8, 4.6, 4.9, 4.7, 4.8],
                        borderColor: 'rgba(245, 158, 11, 1)',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            };

            analyticsChartsV2.performanceTrends = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: 0,
                            max: 5,
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleColor: 'white',
                            bodyColor: 'white',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 1
                        }
                    }
                }
            });
        }

        function loadStatusDistribution() {
            const ctx = document.getElementById('status-distribution-chart');
            if (!ctx) return;

            if (analyticsChartsV2.statusDistribution) {
                analyticsChartsV2.statusDistribution.destroy();
            }

            const data = {
                labels: ['Open', 'In Progress', 'Resolved', 'Closed', 'Pending'],
                datasets: [{
                    data: [1847, 2156, 8234, 1876, 534],
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderColor: [
                        'rgba(239, 68, 68, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(156, 163, 175, 1)'
                    ],
                    borderWidth: 2
                }]
            };

            analyticsChartsV2.statusDistribution = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        datalabels: {
                            color: 'white',
                            font: {
                                weight: 'bold'
                            },
                            formatter: (value, ctx) => {
                                const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                return Math.round((value / sum) * 100) + '%';
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function loadRegionalPerformance() {
            const ctx = document.getElementById('regional-performance-chart');
            if (!ctx) return;

            if (analyticsChartsV2.regionalPerformance) {
                analyticsChartsV2.regionalPerformance.destroy();
            }

            const data = {
                labels: ['North', 'South', 'East', 'West', 'Central'],
                datasets: [
                    {
                        label: 'Performance Score',
                        data: [85, 92, 78, 88, 90],
                        backgroundColor: 'rgba(43, 149, 214, 0.8)',
                        borderColor: 'rgba(43, 149, 214, 1)',
                        borderWidth: 2,
                        borderRadius: 10,
                        borderSkipped: false,
                    }
                ]
            };

            analyticsChartsV2.regionalPerformance = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: 'rgba(43, 149, 214, 1)',
                            font: {
                                weight: 'bold'
                            },
                            formatter: (value) => value + '%'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function loadMonthlyTrends() {
            const ctx = document.getElementById('monthly-trends-chart');
            if (!ctx) return;

            if (analyticsChartsV2.monthlyTrends) {
                analyticsChartsV2.monthlyTrends.destroy();
            }

            const data = {
                labels: ['Q1', 'Q2', 'Q3', 'Q4'],
                datasets: [
                    {
                        label: 'Revenue (₹ Lakhs)',
                        data: [245, 312, 287, 398],
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 2
                    },
                    {
                        label: 'Costs (₹ Lakhs)',
                        data: [180, 220, 195, 250],
                        backgroundColor: 'rgba(239, 68, 68, 0.8)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 2
                    }
                ]
            };

            analyticsChartsV2.monthlyTrends = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function loadCategoryAnalysis() {
            const ctx = document.getElementById('category-analysis-chart');
            if (!ctx) return;

            if (analyticsChartsV2.categoryAnalysis) {
                analyticsChartsV2.categoryAnalysis.destroy();
            }

            const data = {
                labels: ['Technical', 'Billing', 'Support', 'Sales', 'Other'],
                datasets: [{
                    data: [3847, 2156, 1834, 1276, 534],
                    backgroundColor: [
                        'rgba(43, 149, 214, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(156, 163, 175, 0.8)'
                    ],
                    borderColor: [
                        'rgba(43, 149, 214, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(156, 163, 175, 1)'
                    ],
                    borderWidth: 2
                }]
            };

            analyticsChartsV2.categoryAnalysis = new Chart(ctx, {
                type: 'polarArea',
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        datalabels: {
                            color: 'white',
                            font: {
                                weight: 'bold'
                            },
                            formatter: (value, ctx) => {
                                const sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                return Math.round((value / sum) * 100) + '%';
                            }
                        }
                    },
                    scales: {
                        r: {
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        function startRealtimeUpdates() {
            // Initialize real-time activity chart
            const ctx = document.getElementById('realtime-activity-chart');
            if (ctx) {
                const data = {
                    labels: [],
                    datasets: [{
                        label: 'Live Activity',
                        data: [],
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                };

                analyticsChartsV2.realtimeActivity = new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: {
                        responsive: true,
                        animation: {
                            duration: 750
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // Start real-time updates
                realtimeUpdateInterval = setInterval(updateRealtimeData, 5000);
            }

            // Initialize recent activities feed
            updateRecentActivities();
        }

        function updateRealtimeData() {
            if (!analyticsChartsV2.realtimeActivity) return;

            const chart = analyticsChartsV2.realtimeActivity;
            const now = new Date();
            const timeLabel = now.getHours() + ':' + String(now.getMinutes()).padStart(2, '0');
            const newValue = Math.floor(Math.random() * 100) + 50;

            chart.data.labels.push(timeLabel);
            chart.data.datasets[0].data.push(newValue);

            // Keep only last 10 data points
            if (chart.data.labels.length > 10) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }

            chart.update('none');

            // Update performance indicator
            const indicator = document.getElementById('realtime-status');
            if (indicator) {
                indicator.className = 'performance-indicator ' + (newValue > 75 ? 'success' : newValue > 50 ? 'warning' : 'danger');
            }
        }

        function updateRecentActivities() {
            const activities = [
                { time: '2 min ago', text: 'New ticket created by Delhi Branch', type: 'info' },
                { time: '5 min ago', text: 'Critical issue resolved in Mumbai', type: 'success' },
                { time: '8 min ago', text: 'System maintenance completed', type: 'warning' },
                { time: '12 min ago', text: 'New user registration from Bangalore', type: 'info' },
                { time: '15 min ago', text: 'Monthly report generated', type: 'success' }
            ];

            const feedContainer = $('#recent-activities-feed');
            feedContainer.empty();

            activities.forEach(activity => {
                const iconClass = activity.type === 'success' ? 'fa-check-circle' : 
                                activity.type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
                const colorClass = activity.type === 'success' ? 'text-success' : 
                                 activity.type === 'warning' ? 'text-warning' : 'text-info';

                const activityHtml = `
                    <div class="d-flex align-items-center p-2 border-bottom">
                        <div class="mr-3">
                            <i class="fas ${iconClass} ${colorClass}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small font-weight-bold">${activity.text}</div>
                            <div class="small text-muted">${activity.time}</div>
                        </div>
                    </div>
                `;
                feedContainer.append(activityHtml);
            });
        }

        function exportDashboard() {
            // Export functionality
            const dashboardData = {
                kpis: getCurrentKPIs(),
                filters: currentFilters,
                timestamp: new Date().toISOString()
            };
            
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(dashboardData));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "analytics_dashboard_" + new Date().toISOString().split('T')[0] + ".json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }

        function getCurrentKPIs() {
            return {
                totalTickets: $('#total-entities').text(),
                activeSessions: $('#active-sessions').text(),
                filters: currentFilters
            };
        }

        function refreshDashboard() {
            GenerateDashboardV2(currentFilters.corporateId);
        }

        // Cleanup on page unload
        $(window).on('beforeunload', function() {
            if (realtimeUpdateInterval) {
                clearInterval(realtimeUpdateInterval);
            }
            
            // Destroy all charts
            Object.values(analyticsChartsV2).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
        });

        // Auto-refresh dashboard every 5 minutes
        setInterval(refreshDashboard, 300000);

        // Enhanced error handling
        window.addEventListener('error', function(e) {
            console.error('Dashboard Error:', e.error);
            // Optionally show user-friendly error message
        });

        // Advanced filter handling
        function applyAdvancedFilters() {
            const filters = {
                corporate: $('#corporate_name').val(),
                dateRange: $('#filter_date').val(),
                region: $('#region_filter').val()
            };
            
            // Apply filters and refresh dashboard
            GenerateDashboardV2(filters.corporate);
        }

        // Keyboard shortcuts
        $(document).keydown(function(e) {
            if (e.ctrlKey) {
                switch(e.which) {
                    case 82: // Ctrl+R
                        e.preventDefault();
                        refreshDashboard();
                        break;
                    case 69: // Ctrl+E
                        e.preventDefault();
                        exportDashboard();
                        break;
                }
            }
        });
    </script>
    
    <!-- Initialize Dashboard -->
    <script>
        $(document).ready(function() {
            // Show welcome animation
            setTimeout(function() {
                $('.animate__animated').each(function(index) {
                    $(this).css('animation-delay', (index * 0.1) + 's');
                });
            }, 100);
        });
    </script>
    
</body>
</html>