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

    ?>
    <title>
        <?=$ProductName;?> Daily Tracker
    </title>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
        .page-content .panel
        {
            margin-bottom: 0px;
        }
        .panel .panel-container .panel-content
        {
            padding: 0.5rem 0.5rem;
        }
        .table-bordered thead th, .table-bordered thead td
        {
            font-size: 0.9em;
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
    if($ProductName != "Aryadubusiness")
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
                                        
                                       
                                       
                                        <div class="frame-wrap p-0 border-0 m-0" id="daily_tracker_status_html">
                                            <table class="table m-0 table-bordered" id="daily_tracker_html">
                                                
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
    <!--script src="../js/statistics/chartjs/chartjs.bundle.js"></script-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src= "https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
   
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