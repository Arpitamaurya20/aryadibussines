<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    
    <meta name="description" content="Aryadibusiness Analytics Dashboard">
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
    $previous_date =  date('Y-m-d', strtotime('-30 days'));
    $data = array();
    $data['start_date'] = $previous_date;
    $data['end_date'] = $current_date;
    $date_range = $previous_date . " - " . $current_date;
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;

    $Employee_ID = -1;
    $StateManager = false;
    if(CheckRole($_SESSION,"State Corporate Lead") == true )
    {
        $StateManager = true;
    }
    if(isset($_SESSION['Roles']['EmployeeID']))
    {
        $Employee_ID = $_SESSION['Roles']['EmployeeID'];
    }
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
    }
    else
    {
        $state_array = $state_object->setStateArray('Active');
    }
    $corporate_ticket_obj = new Corporateticket($conn);
    $status_array = $corporate_ticket_obj->getCorporateTicketStatusArray("All");
    $types = array("R&M","PPM","Projects","AMC","Supply");
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
    $logoImg = "tech-logo.jpg";
    if(isset($product_configuration['logo']))
    {
        $logoImg = "innov_logo.png";
    }

    if (!function_exists('formatTrackerValue')) {
        function formatTrackerValue($count, $status) {
            if ($count == 0) {
                return "<span class='tracker-zero'>0</span>";
            }
            
            $status = strtolower($status);
            $textClass = 'tracker-val-blue'; // default
            
            if (strpos($status, 'close') !== false || strpos($status, 'approved') !== false) {
                $textClass = 'tracker-val-green'; // green
            } elseif (strpos($status, 'escalat') !== false || strpos($status, 'reject') !== false || strpos($status, 'cancel') !== false) {
                $textClass = 'tracker-val-red'; // red
            } elseif (strpos($status, 'hold') !== false || strpos($status, 'pending') !== false) {
                $textClass = 'tracker-val-orange'; // orange
            }
            
            return "<span class='tracker-val {$textClass}'>{$count}</span>";
        }
    }
    ?>
    <title>
        <?=$ProductName;?> Daily Tracker
    </title>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        #js-page-content {
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        .page-content .panel
        {
            margin-bottom: 0px;
            background-color: #003f88;
            color: #ffffff;
        }
        .panel .panel-container .panel-content
        {
            padding: 0.5rem 0.5rem;
        }
        
        /* Minimal Modern Frame Wrap */
        .frame-wrap-premium {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Table styles */
        table#daily_tracker_html {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0 !important;
        }

        table#daily_tracker_html thead tr th {
            background: #f8fafc !important;
            color: #475569 !important;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px !important;
            border-bottom: 2px solid #e2e8f0 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            white-space: nowrap;
            text-align: center;
            vertical-align: middle;
        }

        table#daily_tracker_html tbody tr {
            transition: background 0.15s ease;
            background-color: #fff;
        }

        table#daily_tracker_html tbody tr:nth-child(even) {
            background-color: #fcfdfe;
        }

        table#daily_tracker_html tbody tr:hover {
            background-color: #f1f5f9 !important;
        }

        table#daily_tracker_html tbody td {
            font-size: 13px;
            color: #334155;
            padding: 10px 10px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid #edf2f7 !important;
            border-top: none !important;
            border-left: none !important;
            border-right: none !important;
            text-align: center;
            font-weight: 500;
        }

        /* Sticky first column */
        table#daily_tracker_html tbody td:first-child {
            font-weight: 600;
            color: #1e293b;
            font-size: 13px;
            text-align: left;
            padding-left: 16px !important;
            border-right: 1px solid #edf2f7 !important;
            white-space: nowrap;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        table#daily_tracker_html tbody tr:nth-child(even) td:first-child {
            background-color: #fcfdfe;
        }

        table#daily_tracker_html tbody tr:nth-child(odd) td:first-child {
            background-color: #fff;
        }

        table#daily_tracker_html tbody tr:hover td:first-child {
            background-color: #f1f5f9 !important;
        }
        
        table#daily_tracker_html thead tr th:first-child {
            text-align: left;
            padding-left: 16px !important;
            position: sticky;
            left: 0;
            z-index: 11;
            background: #f8fafc !important;
            border-right: 1px solid #e2e8f0 !important;
        }

        /* Modern text value highlights instead of badges */
        .tracker-zero {
            color: #cbd5e1;
            font-weight: 400;
        }

        .tracker-val {
            font-weight: 700;
            font-size: 13px;
        }

        .tracker-val-green {
            color: #16a34a; /* success green */
        }

        .tracker-val-orange {
            color: #ea580c; /* pending orange */
        }

        .tracker-val-red {
            color: #dc2626; /* alert red */
        }

        .tracker-val-blue {
            color: #2563eb; /* info blue */
        }

        .tracker-total {
            font-weight: 700;
            color: #0f172a;
        }
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
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

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" name="UserType" id="UserType" value="<?php echo $UserType;?>">
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Accounts Dashboard</a></li>
                    </ol>
                    
                     <div class="panel mb-2">  
                            <input type="hidden" id="sql_in_state_string" value="<?php echo $sql_in_state_string;?>">
                            <div class="panel-content p-3">
                                <div class="row">
                                    <div class="col-md-3 ">
                                        
                                        <select class="form-control w-100" id="type" name="type" onchange="LoadAccountDashboard()">
                                            <option value="">Select Type</option>
                                            <?php
                                            foreach($types as $type)
                                            {
                                            ?>
                                                <option value="<?php echo $type;?>">
                                                    <?php echo $type;?>
                                                </option>
                                            <?php
                                            }

                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">         
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-control" name="stateName" id="stateName">
                                            <option value="">Select State Name</option>
                                            <?php
                                            foreach ($state_array as $state) 
                                            {
                                                $StateName = $state['StateName']
                                            ?>

                                                <option value="<?php echo $StateName ?>"> <?php echo $StateName ?></option>

                                            <?php 
                                            }  
                                            ?>
                                        </select>
                                    </div>
                                   
                                    <div class="col-md-2">
                                        <button class="btn btn-primary float-right waves-effect waves-themed" onclick="LoadAccountDashboard()">Search</button>
                                    </div>
                                </div>
                            </div>
              
                    </div>
                    
                 
                    <div class="row">
                            <div class="col-sm-12">
                                <div class="card mb-g">
                                    <h5 class="card-header bg-white">
                                        Accounts <span class="fw-300">Activity Tracker </span>
                                    </h5>
                                    <div class="card-body">
                                        
                                       
                                       
                                        <div class="frame-wrap-premium" id="daily_tracker_status_html">
                                            <table id="daily_tracker_html">
                                                
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                </main>
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
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/modules/account_dashboard.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script>
    $(document).ready(function() {
        $("#corporate_name").select2();
         $('#filter_date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
        if($("#stateName").length)
        {
            $("#stateName").select2();
        }
         $("#nav_account_dashboard").addClass("active");
    });
    </script>
    
</body>

</html>