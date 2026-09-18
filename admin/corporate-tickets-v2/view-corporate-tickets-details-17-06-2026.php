<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/corporate_tickets_controller.php');
    include('../corporate-tickets-status/controller/corporate_tickets_status_controller.php');
    include('../employees/controller/employee_controller.php');
    include('../includes/autoloader.inc.php');
    include('../Services/controller/service_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
        View Corporate Tickets Details
    </title>
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
     <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');

    $ID = "N.A.";
    if(!isset($_SESSION['TicketID']))
    {
    }
    else
    {
        $ID = $_SESSION['TicketID'];
    }
    $data_temp['TicketID'] = $ID;
    $corporate_ticket_data = getCorporateTicketDetail($conn,$data_temp)['data'];

     $IsAmcTicket=$corporate_ticket_data['Type'];
    if($IsAmcTicket=="AMC")
    {    $cartID='-1';
         $cart=getAmcTicketCartID($conn,$ID);
         if(!empty($cart))
         {
            $cartID=$cart['CartID'];
         }
         $BranchAssetsID=getAmcBranchAssetsID($conn,$ID);
    }

    $TicketID = $corporate_ticket_data['TicketID'];
    $PrimaryID = $corporate_ticket_data['ID'];
    $customer_rating = getRatingByTicketID($conn,$ID);
    $TicketMediaData = getMediaTicketImage($conn,$PrimaryID);

    $Ticket_conversation_data = getTicketConversation($conn,$PrimaryID);

    $Ticket_finance_data = GetCorporateFinanceTicketID($conn,$PrimaryID);

    $Reject_data_array = GetAllRejectedQoutationByTicketID($conn,$PrimaryID);

    // var_dump($_SESSION);
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

        if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "Finance")
            {
                $Finance_Manager = true;
               
            }
        }
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

    $Procurement=false;
    if(CheckRole($_SESSION,"Procurement") == true )
    {
        $Procurement = true;
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


    $corporateticket_obj = new Corporateticket($conn);
    $finance_status_array = $corporateticket_obj->getCorporateTicketFinanceStatusArray();

    $finance_nav = 0;
    $navigation_li = "nav_corporate_tickets";
    if(isset($_GET['nav']))
    {
        $nav = $_GET['nav'];
        if($nav == "finance")
        {
            $finance_nav = 1;
            $navigation_li = "nav_corporate_tickets_finance";
        }
    }

    $categories_obj = new Categories($conn);
    $categories_array = $categories_obj->getAllCategories();

    $CorporateID = $corporate_ticket_data['CorporateID'];
    $branch_obj = new Branch($conn);
    $branch_array = $branch_obj->setBranchArrayByCorporateID($CorporateID,"Active");

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
                       
                        <?php
                        if($finance_nav)
                        {
                            ?>
                             <li class="breadcrumb-item"><a href="view-corporate-tickets-finance">View Corporate Tickets</a></li>
                            <?php

                        }
                        else
                        {
                            ?>
                             <li class="breadcrumb-item"><a href="view-corporate-tickets">View Corporate Tickets</a></li>
                            <?php
                        }
                        ?>
                        <li class="breadcrumb-item active">Corporate Tickets Details</li>

                    </ol>

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Corporate Tickets Details</span>
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
                                                    <!-- <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#services" role="tab">
                                                            <i class="fal fa-calendar-edit text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">Service</span>
                                                        </a>
                                                    </li> -->
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#assignment" role="tab">
                                                            <i class="fal fa-calendar-edit text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">Assignment & Status</span>
                                                        </a>
                                                    </li>

                                                    <?php if (1) {

                                                     if($UserType == "Admin" || $Finance_Manager || $CFO){?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#finance"
                                                            role="tab">
                                                            <i class="fas fa-landmark text-danger"></i>
                                                            <span class="hidden-sm-down ml-1">Finance</span>
                                                        </a>
                                                    </li>
                                                    <?php } }?>
                                                    <?php
                                                    if($UserType == "Corporate Admin")
                                                    {
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#ticket_cost"
                                                            role="tab">
                                                            <i class="fas fa-landmark text-danger"></i>
                                                            <span class="hidden-sm-down ml-1">Ticket Cost</span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    }
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#feedback" role="tab">
                                                            <i class="fas fa-comment text-success"></i>
                                                            <span class="hidden-sm-down ml-1">Feedback</span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    if($UserType == "Admin" || $TicketManager || $Accounts_Manager  || $Procurement || $UserType == "Corporate Branch User" || $UserType == "Corporate Branch User" || $CityLead || $CFO || $Finance_Manager)
                                                    {
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#quotation" role="tab">
                                                            <i class="fas fa-circle-info text-info"></i>
                                                            <span class="hidden-sm-down ml-1">Quotation</span>
                                                        </a>
                                                    </li>
                                                    <?php
                                                    }
                                                    ?>

                                                       <?php 
                                                    if(($Branch_Account_Manager) || (($corporate_ticket_data['Type'] == "AMC") && ( $UserType == "Admin" || $UserType == "Corporate Admin" || $CityLead || $CFO || $Finance_Manager || $TicketManager)))
                                                    {
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#spare-part"
                                                            role="tab">
                                                           <i class="fa fa-cog text-info"></i>
                                                            <span class="hidden-sm-down ml-1">Spare Part</span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    }
                                                    ?> 
                                                    
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#service_report"
                                                            role="tab">
                                                            <i class="fas fa-list text-info"></i>
                                                            <span class="hidden-sm-down ml-1">Service Report</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#history"
                                                            role="tab">
                                                            <i class="fas fa-history text-info"></i>
                                                            <span class="hidden-sm-down ml-1">History</span>
                                                        </a>
                                                    </li>

                                                     <?php 
                                                    if($corporate_ticket_data['Type'] == "Projects" || $corporate_ticket_data['Type'] == "Supply")
                                                    {
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#dpr"
                                                            role="tab">
                                                           <i class="fa fa-bar-chart text-info"></i>
                                                            <span class="hidden-sm-down ml-1">DPR</span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    }
                                                    ?>  


                                                     <!-- <?php 
                                                    if($corporate_ticket_data['Type'] == "AMC")
                                                    {
                                                    ?>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#spare-part"
                                                            role="tab">
                                                           <i class="fa fa-cog text-info"></i>
                                                            <span class="hidden-sm-down ml-1">Spare Part</span>
                                                        </a>
                                                    </li>
                                                    <?php 
                                                    }
                                                    ?>   -->

                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="services" role="tabpanel">

                                                        <?php
                                                            // include('includes/corporate_ticket_service_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="assignment" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_assignment_tab.php');
                                                         ?>

                                                    </div>

                                                    <?php
                                                     if (1) {

                                                     if($CityLead == 1 || $Account_Manager || $UserType == "Corporate Branch User" || $UserType == "Corporate Admin" || $UserType == "Admin" || $UserType == "Corporate User" || $Accounts_Manager || $CFO || $Finance_Manager || $TicketManager){?>
                                                    <div class="tab-pane fade" id="finance" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_finance_tab.php');
                                                         ?>

                                                    </div>
                                                     <?php } }?>
                                                     <?php
                                                     if($UserType == "Corporate Admin"){?>
                                                    <div class="tab-pane fade" id="ticket_cost" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_costing_tab.php');
                                                         ?>

                                                    </div>
                                                     <?php 
                                                        } 
                                                        ?>
                                                    <div class="tab-pane fade" id="quotation" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_quotation_tab.php');
                                                         ?>

                                                    </div>
                                                     <div class="tab-pane fade" id="service_report" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_service_report_tab.php');
                                                         ?>

                                                    </div>
                                                    <?php 
                                                    if($UserType == "Admin")
                                                    {
                                                    ?>
                                                    <div class="tab-pane fade" id="feedback" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_feedback_tab.php');
                                                         ?>

                                                    </div>
                                                    <?php 
                                                    }
                                                    ?>
                                                    <div class="tab-pane fade" id="history" role="tabpanel">

                                                        <?php
                                                              include('includes/corporate_ticket_history_tab.php');
                                                         ?>

                                                    </div>

                                                    <?php 
                                                    if($corporate_ticket_data['Type'] == "Projects" || $corporate_ticket_data['Type'] == "Supply")
                                                    {
                                                    ?>
                                                    <div class="tab-pane fade" id="dpr" role="tabpanel">

                                                        <?php
                                                                  include('includes/view_dpr_task.php');
                                                         ?>

                                                    </div>
                                                    <?php 
                                                    }
                                                    ?>


 <?php 
                                                    if(($Branch_Account_Manager) || (($corporate_ticket_data['Type'] == "AMC") && ( $UserType == "Admin" || $UserType == "Corporate Admin" || $CityLead || $CFO || $Finance_Manager || $TicketManager)))
                                                    {
                                                    ?>
                                                    <div class="tab-pane fade" id="spare-part" role="tabpanel">

                                                        <?php
                                                                  include('includes/view_spare_part.php');
                                                         ?>

                                                    </div>
                                                    <?php 
                                                    }
                                                    ?>
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

    
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
     <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/corporate-booking.js"></script>
    <script>
    $("#booking_status").select2();
    $(document).ready(function() {
      
      $("#<?php echo $navigation_li; ?>").addClass("active");

      // For Quotation Tab
      var QuotationID = document.getElementById("TicketQuotationID").value;
      if(QuotationID == -1)
      {
        document.getElementById("add_non_arc_button").style.display = "none";
      }
    });
    </script>
</body>
</html>