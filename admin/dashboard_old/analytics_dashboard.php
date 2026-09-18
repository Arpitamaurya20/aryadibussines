<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
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

    $BranchAccountManager = false;
    $sql_in_branch_account_string = "";
    if(CheckRole($_SESSION,"Branch Account Manager") == true )
    {
        $BranchAccountManager = true;
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
    $StateManager = false;
    if(CheckRole($_SESSION,"State Corporate Lead") == true )
    {
        $StateManager = true;
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
    

    $ProductName = "Aryadibusiness";
    if ($CorporateID == 183) 
    {
        $_product = "innov";
        $conf = new Config($conn);
        $product_configuration = $conf->GetConfigParametersfromURL($_product);
        $ProductName = $product_configuration['ProductName'];
    } 
    $logoImg = "tech-logo.jpg";
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
        .page-content .panel
        {
            margin-bottom: 0px;
        }
        .panel .panel-container .panel-content
        {
            padding: 0.5rem 0.5rem;
        }
        .analytics-button
        {
            font-size: 1.2em;
        }
        .select2-container
        {
            z-index: 1;
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

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
    <input type="hidden" id="UserType" value="<?php echo $UserType;?>" />
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
                       
                            
                    <div class="alert alert-primary">
                        <div class="d-table w-100">
                            <div class="d-table-cell align-top width-6">
                                <span class="icon-stack icon-stack-lg">
                                    <i class="base base-6 icon-stack-3x opacity-100 color-primary-500"></i>
                                    <i class="base base-10 icon-stack-2x opacity-100 color-primary-300 fa-flip-vertical"></i>
                                    <i class="fal fa-info icon-stack-1x opacity-100 color-white"></i>
                                </span>
                            </div>
                            <div class="d-table-cell pl-1">
                                <span class="h5">State Manager Dashboard</span>
                                <br> States Managed - 
                                <?php 
                                foreach($state_array_mapped as $i_state)
                                {
                                    ?>
                                   
                                    <span class="badge badge-info"><?php echo $i_state;?></span>
                                                    
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                        }
                    ?>
                    <input type="hidden" id="sql_in_state_string" value="<?php echo $sql_in_state_string;?>">
                    <input type="hidden" id="sql_in_branch_account_string" value="<?php echo $sql_in_branch_account_string;?>">
                    <div class="panel mb-2">  
                            <div class="panel-content p-3">
                                <div class="row">
                                <?php
                                if($CorporateID == -1)
                                {
                                ?>
                                    
                                    <div class="col-md-3 ">
                                       
                                        <select class="select2 form-control w-100" id="corporate_name" name="corporate_name" onchange="RefreshBranchAnalytics(this.value)">
                                            <option value="-1">Select Corporate</option>
                                            <?php
                                            foreach($corporate_array as $corporate)
                                            {
                                            ?>
                                                <option value="<?php echo $corporate['ID'];?>">
                                                    <?php echo $corporate['CompanyName'];?>
                                                </option>
                                            <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                            
                                <?php
                                }
                                ?>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">         
                                </div>
                                <div class="col-md-3" id="state_region_view">
                                            
                                </div>
                                <div class="col-md-3">
                                    <button type="button" onclick="GenerateDashboard(<?php echo $CorporateID;?>);" class="btn btn-sm btn-primary ml-3 waves-effect waves-themed">Search</button>
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