<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/ppm_controller.php');
    include('../dynamic-ppm/controller/dynamic_ppm_controller.php');
    include('../corporate-tickets-status/controller/corporate_tickets_status_controller.php');
    include('../employees/controller/employee_controller.php');
    require_once('../includes/autoloader.inc.php');
    $conn = _connectodb();
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
        <?php

        $ID = "N.A.";
        if(!isset($_SESSION['TicketID']))
        {
        }
        else
        {
            $ID = $_SESSION['TicketID'];
        }
        $data_temp['TicketID'] = $ID;
        $corporate_ticket_data = getPPMTicketDetail($conn,$data_temp)['data'];
        $BranchAssetID = $corporate_ticket_data['BranchAssetID'];
        $core = new Core();
        $branch_asset_details = $core->_getTableDetails($conn,'branch_assets',' where ID = '.$BranchAssetID);
        $BranchAssetCategoryID = -1;
        if($branch_asset_details != null)
        {
            $BranchAssetCategoryID = $branch_asset_details['Category'];
        }
        $dynamicPpmFlow = getDynamicPPMTicketFlowInfo($conn, $ID);
        $dynamicPpmReportBundle = getDynamicPPMChecklistResponsesForTicket($conn, $ID);
        $useDynamicPpm = (!empty($dynamicPpmFlow['use_dynamic_ppm']) || !empty($dynamicPpmReportBundle['has_report'])) ? 1 : 0;
        $ppmticketobj = new Ppmtickets($conn);
        $TicketMediaData = $ppmticketobj->getPPMMediaTicketImage($ID);
        $CityLead = false;
        if(CheckRole($_SESSION,"City Lead") == true || CheckRole($_SESSION,"City Corporate Lead") == true)
        {
            $CityLead = true;
        }

        $Finance_Manager = false;
        if(CheckRole($_SESSION,"Finance") == true )
        {
            $Finance_Manager = true;
        }

        $Accounts_Manager = false;
        if(CheckRole($_SESSION,"Accounts") == true || CheckRole($_SESSION,"Branch Account Manager") == true)
        {
            $Accounts_Manager = true;
        }

        $Procurement_Manager = false;
        if(CheckRole($_SESSION,"Procurement") == true )
        {
            $Procurement_Manager = true;
        }

        $CFO = false;
        if(CheckRole($_SESSION,"CFO") == true )
        {
            $CFO = true;
        }

        $Account_Manager = false;
        if(CheckRole($_SESSION,"Account Manager") == true )
        {
            $Account_Manager = true;
        }

        $Branch_Account_Manager = false;
        if(CheckRole($_SESSION,"Branch Account Manager") == true )
        {
            $Branch_Account_Manager = true;
        }
        $TicketManager = false;
        if(CheckRole($_SESSION,"Ticket Manager") == true )
        {
            $TicketManager = true;
        }
        $CorporateID = -1;
        if($UserType == "Corporate Branch User" || $UserType == "Corporate Admin")
        {
            $corporate_user = true;
            $CorporateID = $_SESSION['Roles']['CorporateID'];
        }
        if(isset($_SESSION['Roles']['EmployeeRoles']))
        {
            $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
            foreach($EmployeeRoles as $E_Role)
            {
                if($E_Role == "Ticket Manager" || $UserType == "Admin" || $UserType == "Corporate Admin")
                {
                    $TicketManager = true;
                }
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
        View PPM Tickets Details - <?=$ProductName;?>
    </title>
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



<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
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
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard"><?=$ProductName;?></a></li>
                        <li class="breadcrumb-item"><a href="view-all-ppm-tickets.php">View PPM Tickets</a></li>
                        <li class="breadcrumb-item active">PPM Tickets Details</li>

                    </ol>
                    <input type="hidden" id="TicketManager_Access" value="<?php echo $TicketManager;?>">


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View PPM Tickets Details</span>
                                    </h2>

                                </div>


                                <div id="panel-2" class="panel mt-3">

                                    <div class="panel-container show">
                                        <div class="panel-content">
                                            <div class="demo-v-spacing">

                                                <ul class="nav nav-tabs" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active fs-lg px-4" data-toggle="tab"
                                                            href="#details " role="tab">
                                                            <i class="fas fa-server"></i>
                                                            <span class="hidden-sm-down ml-1">Details </span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#ppm_date_tab" role="tab">
                                                            <i class="fal fa-calendar-edit text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">PPM Date</span>
                                                        </a>
                                                    </li> 
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#assignment" role="tab">
                                                            <i class="fal fa-calendar-edit text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">Assignment & Status</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#service_report"
                                                            role="tab">
                                                            <i class="fas fa-list text-info"></i>
                                                            <span class="hidden-sm-down ml-1">Service Report</span>
                                                        </a>
                                                    </li>
                                                   
                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">

                                                        <?php
                                                              include('includes/ppm_ticket_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="ppm_date_tab" role="tabpanel">

                                                        <?php
                                                            include('includes/ppm_ticket_date_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="assignment" role="tabpanel">

                                                        <?php
                                                              include('includes/ppm_ticket_assignment_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="service_report" role="tabpanel">

                                                        <?php
                                                              include('includes/ppm_ticket_service_report_tab.php');
                                                         ?>

                                                    </div>
                                                    
                                                </div>

                                            </div>
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

    <!-- Button trigger modal -->


    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/ppm-ticket.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

    <script type="text/javascript">
         $("#booking_status").select2();
         $(document).ready(function() 
        {
             $("#nav_ppm_tickets").addClass("active");
        });
    </script>



</body>


</html>