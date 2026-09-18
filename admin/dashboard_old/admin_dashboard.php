<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>
        Aryadibusiness Dashboard
    </title>
    <meta name="description" content="aryadibusiness Dashboard">
    <?php
    include('../includes/autoloader.inc.php');
    include('controller/dashboard_controller.php');
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    
    include('../includes/common_head_content.php');
    $conn = _connectodb();
    $CorporateID = -1;
    $BranchID = -1;
    if($UserType == "Corporate Admin")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
    }
    if($UserType == "Corporate Branch User")
    {
        $corporate_user = true;
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
    }
    //echo "Corporate ".$CorporateID." Branch".$BranchID;
    $city_lead = "no";
    $Employee_ID = -1;
    $sql_in_string = "";
    if(isset($_SESSION['Roles']['EmployeeRoles']))
    {
        $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
        foreach($EmployeeRoles as $E_Role)
        {
            if($E_Role == "City Corporate Lead")
            {
                $city_lead = "yes";
                if(isset($_SESSION['Roles']['EmployeeID']))
                {
                    $Employee_ID = $_SESSION['Roles']['EmployeeID'];
                    $city = new City($conn);
                    $cities_array_mapped_raw = $city->getMappedCitiesofCityLead($Employee_ID,'Corporate');
                    $cities_array_mapped = array();
                    foreach($cities_array_mapped_raw as $city_mapped)
                    {
                        array_push($cities_array_mapped,$city_mapped['CityName']);
                    }
                    $sql_in_string = "'" . implode("', '", $cities_array_mapped) . "'";
                }
            }
        }
    }
    $where = " where IsActive = 1";
    $status_array = _getTableRecords($conn,'corporate_tickets_status',$where);
    $dashboard = new Dashboard($conn);
    $TicketStatusArray = $dashboard->getTicketStatus($CorporateID,$BranchID,$sql_in_string);
    $ChartData = getChartData($conn,$status_array,$TicketStatusArray);
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/statistics/chartjs/chartjs.css">
    <style type="text/css">
    .nav-footer {
        height: unset !important;
    }

    .slimScrollDiv {
        background: #0581c1 !important;
    }

    .l-h-n {
        line-height: normal;
        padding: 10px;
        text-align: center;
        font-size: 20px;
    }

    .bg-primary-300 {
        background-color: #03A9F4 !important;
    }

    .bg-danger-200 {
        background-color: #d71771 !important;
    }

    .bg-success-200 {
        background-color: #007266 !important;
    }

    .bg-warning-200 {
        background-color: #edac20 !important;
    }
    </style>
</head>

<body class="mod-bg-1 header-function-fixed nav-function-fixed blur">
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Aryadibusinesss</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                    </ol>
                    <!-- Analytics -->
                        <div class="col-xl-12" style="flex:unset;">
                                <div class="row">
                                    <div class="col-xl-6">
                                        <div id="panel-6" class="panel">
                                            <div class="panel-hdr">
                                                <h2>
                                                    Status <span class="fw-300"><i>Analytics</i></span>
                                                </h2>
                                                
                                            </div>
                                            <div class="panel-container show">
                                                <div class="panel-content">
                                                    
                                                    <div id="pieChart">
                                                        <canvas style="width:100%; height:300px;"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-6">
                                        <div id="panel-4" class="panel">
                                            <div class="panel-hdr">
                                                <h2>
                                                    Ticket <span class="fw-300"><i>Status</i></span>
                                                </h2>
                                            </div>
                                            <div class="panel-container show">
                                                <div class="panel-content">
                                                    
                                                    <table class="table table-bordered m-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Status</th>
                                                                <th>Number</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            foreach($status_array as $status)
                                                            {
                                                                $Status = $status['Status'];
                                                                $td_id = $Status."-id";
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $Status; ?></td>
                                                                <td id="<?php echo $td_id;?>"><?php echo $TicketStatusArray[$Status]; ?></td>
                                                            </tr>
                                                            <?php
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div> <!-- table panel -->
                                    </div>
                                </div>
                            </div>
                        <!-- Analytics -->
                         <?php
                                if($_Nav_Corporate)
                                {
                                ?>
                    <div class="row text-white pt-2 mr-1 ml-1" style="background-color: #0581c1;">
                        <div class="col-md-8">
                            <h3>Corporate</h3>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                     <?php
                                }
                                ?>

                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="row">

                                <?php
                                if($_Nav_Corporate)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/company.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../company/view-company" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $company = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `company`"));
                                                ?>
                                                    <?php echo $company[0] ?>
                                                    <small class="m-0 l-h-n">Company</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <?php
                                }
                                if($_Nav_Corporate)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/branches.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../branch/view-branch?nav=1" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $branch = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `branch`"));
                                                ?>
                                                    <?php echo $branch[0] ?>
                                                    <small class="m-0 l-h-n">Company Branches</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <?php
                                }
                                if($_Nav_Corporate)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/tickets.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../corporate-tickets/view-corporate-tickets"
                                                class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $corporate_tickets = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `corporate_tickets`"));
                                                ?>
                                                    <?php echo $corporate_tickets[0] ?>
                                                    <small class="m-0 l-h-n">Corporate Tickets</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <?php
                                }
                                if($_Nav_Corporate)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/tickets.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../corporate-tickets/view-corporate-tickets"
                                                class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $corporate_tickets_status = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `corporate_tickets_status`"));
                                                ?>
                                                    <?php echo $corporate_tickets[0] ?>
                                                    <small class="m-0 l-h-n">Corporate Tickets Status</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                ?>
                            </div>

                        </div>
                    </div>

                           <?php

                                if($_Nav_Employees)
                                {
                                ?>
                    <div class="row text-white pt-2 mt-3 mr-1 ml-1" style="background-color: #0581c1;">
                        <div class="col-md-8">
                            <h3>Manpower </h3>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                    <?php

                            }
                                ?>

                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="row">

                                <?php

                                if($_Nav_Employees)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/employee.jpg) top center no-repeat;">
                                        <div class="">
                                            <a href="../employees/view-employees" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $employees = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `employees`"));
                                                ?>
                                                    <?php echo $employees[0] ?>
                                                    <small class="m-0 l-h-n">Employees</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                  if($_Nav_Employees)
                                    {
                                    ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/employee.jpg) top center no-repeat;">
                                        <div class="">
                                            <a href="../employees/view-employees" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                               $vendor = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM employees WHERE vendor = 1"));
                                                  ?>
                                                    <?php echo $vendor[0] ?>
                                                    <small class="m-0 l-h-n">Vendor</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>



                                <?php
                                }
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/enquiry.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../manage-site/view_site" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $site = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `site`"));
                                                ?>
                                                    <?php echo $site[0] ?>
                                                    <small class="m-0 l-h-n">Manage Sites</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/enquiry.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../department/view-department" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $department = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `department`"));
                                                ?>
                                                    <?php echo $department[0] ?>
                                                    <small class="m-0 l-h-n">Department</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                ?>
                            </div>

                        </div>
                    </div>

                        <?php
                                if($_Nav_Bookings)
                                {
                                ?>

                    <div class="row text-white pt-2 mt-3 mr-1 ml-1" style="background-color: #0581c1;">
                        <div class="col-md-8">
                            <h3>Home Care Services </h3>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                     <?php
                        }
                        ?>

                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="row">

                                <?php

                                if($_Nav_Bookings)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/employee.jpg) top center no-repeat;">
                                        <div class="">
                                            <a href="../booking/view_booking" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $booking = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `confirm_booking`"));
                                                ?>
                                                    <?php echo $booking[0] ?>
                                                    <small class="m-0 l-h-n">Booking</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <?php
                                }
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/enquiry.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../booking-status/view-booking-status" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $booking_status = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `booking_status`"));
                                                ?>
                                                    <?php echo $booking_status[0] ?>
                                                    <small class="m-0 l-h-n">Booking Status</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                ?>
                            </div>

                        </div>
                    </div>
                          <?php
                                if($_Nav_Configuration)
                                {
                                ?>
                    <div class="row text-white pt-2 mt-3 mr-1 ml-1" style="background-color: #0581c1;">
                        <div class="col-md-8">
                            <h3>Website </h3>
                        </div>
                        <div class="col-md-4"></div>
                    </div>

                        <?php
                                }
                                ?>

                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="row">

                                <?php
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/enquiry.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../region/view-region" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $employees = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `region`"));
                                                ?>
                                                    <?php echo $employees[0] ?>
                                                    <small class="m-0 l-h-n">Region</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-danger-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/state.jpg) center center no-repeat;">
                                        <div class="">
                                            <a href="../state/view-state" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $employees = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `state`"));
                                                ?>
                                                    <?php echo $employees[0] ?>
                                                    <small class="m-0 l-h-n">State</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>


                                <?php
                                }
                                if($_Nav_Configuration)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-primary-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(#b19dcebf, #b19dceb0) 0% 0% / cover, url(../img/city-1.jpg) bottom center no-repeat;">
                                        <a href="../city/view-city" class="text-white">
                                            <div class="">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $City = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `citydata`"));
                                                ?>
                                                    <?php echo $City[0] ?>
                                                    <small class="m-0 l-h-n">City</small>
                                                </h3>
                                            </div>
                                        </a>
                                        <!-- <i class="fal fa-file position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1"
                                            style="font-size:6rem"></i> -->
                                    </div>
                                </div>




                                <?php
                                }
                                if($_Nav_Services)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-warning-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/service-1.png) center center no-repeat;">
                                        <a href="../Services/view-services" class="text-white">
                                            <div class="">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $service = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `services`"));
                                                ?>
                                                    <?php echo $service[0] ?>
                                                    <small class="m-0 l-h-n">Services</small>
                                                </h3>
                                            </div>
                                        </a>
                                        <!-- <i class="fal fa-file position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1"
                                            style="font-size:6rem"></i> -->
                                    </div>
                                </div>
                                <?php
                                }
                                if($_Nav_Site_Setting)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-success-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/testimonal.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../testimonials/view-testimonials" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $Testimonials = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `testimonials`"));
                                                ?>
                                                    <?php echo $Testimonials[0] ?>
                                                    <small class="m-0 l-h-n">Testimonials</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                            </div>

                        </div>
                    </div>
                                <?php
                                if($_Nav_Contact)
                                {
                                ?>
                    <div class="row text-white pt-2 mt-3 mr-1 ml-1" style="background-color: #0581c1;">
                        <div class="col-md-8">
                            <h3>Enquiries </h3>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                     <?php
                             }
                                ?>

                    <div class="row mt-3">
                        <div class="col-xl-12">
                            <div class="row">
                                <?php
                                if($_Nav_Contact)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-secondary rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/service-2.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../banners/view-banners" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $Enquiries = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `get_quote`"));
                                                ?>
                                                    <?php echo $Enquiries[0] ?>
                                                    <small class="m-0 l-h-n">Contact Us</small>
                                                </h3>
                                            </a>
                                        </div>
                                        <!-- <i class="fal fa-file position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1"
                                            style="font-size:6rem"></i> -->
                                    </div>
                                </div>
                                <?php
                                }
                                if($_Nav_Contact)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-primary-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/resume.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../resume/view_resume" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $resume = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `resume`"));
                                                ?>
                                                    <?php echo $resume[0] ?>
                                                    <small class="m-0 l-h-n">Resumes</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                if($_Nav_Contact)
                                {
                                ?>
                                <div class="col-sm-4 col-xl-4">
                                    <div class="p-3 bg-primary-200 rounded overflow-hidden position-relative text-white mb-g"
                                        style="height:150px; background: linear-gradient(rgb(1 2 2 / 64%), rgb(1 2 2 / 64%)) 0% 0% / cover, url(../img/backgrounds/resume.png) center center no-repeat;">
                                        <div class="">
                                            <a href="../get-quote/view-get-quote-enquiry" class="text-white">
                                                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                    <?php
                                                $get_quote = mysqli_fetch_array(mysqli_query($conn, "SELECT COUNT(*) FROM `get_quote`"));
                                                ?>
                                                    <?php echo $get_quote[0] ?>
                                                    <small class="m-0 l-h-n">Quote Enquiry</small>
                                                </h3>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                }
                                ?>
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
    <script src="../js/statistics/chartjs/chartjs.bundle.js"></script>
    <script>
    var pieChart = function()
        {
            var config = {
                type: 'pie',
                data:
                {
                    datasets: [
                    {
                        data: <?php echo json_encode($ChartData['stat_data']); ?>,
                        backgroundColor: <?php echo json_encode($ChartData['bg_color']); ?>,
                        label: 'Status' // for legend
                    }],
                    labels: <?php echo json_encode($ChartData['stat_array']); ?>
                },
                options:
                {
                    responsive: true,
                    legend:
                    {
                        display: true,
                        position: 'right',
                    }
                }
            };
            new Chart($("#pieChart > canvas").get(0).getContext("2d"), config);
        }
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_dashboard").addClass("active");
        pieChart();
    });
    </script>
    
</body>

</html>