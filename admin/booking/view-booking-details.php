<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/booking_controller.php');
    include('../employees/controller/employee_controller.php');
    $UserType = SessionCheck();
    $conn = _connectodb();
    setNavigation($_SESSION['Roles']);

    //$total_projects = getTotatProjects($conn,$Enterpriseid);
    ?>
    <meta charset="utf-8">
    <title>
        View Booking Detail
    </title>
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
</head>

<?php
$UserType = SessionCheck();
$ID = "N.A.";
if(!isset($_SESSION['BookingID']))
{
}
else
{
    $ID = $_SESSION['BookingID'];
}
$bookingdata = getOneBookingData($conn, $ID);
$BookingID = $bookingdata['BookingID'];
$customer_rating = getRatingByBookingID($conn,$BookingID);

?>

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="view_booking">View Bookings</a></li>
                        <li class="breadcrumb-item active">Booking Details</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Booking Details</span>
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
                                                            href="#assignment" role="tab">
                                                            <i class="fal fa-calendar-edit text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">Assignment & Status</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#service_details" role="tab">
                                                            <i class="fa-solid fa-screwdriver-wrench text-danger"></i>
                                                            <span class="hidden-sm-down ml-1">Service Details</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#hourly_service_details" role="tab">
                                                            <i class="fa-solid fa-screwdriver-wrench text-danger"></i>
                                                            <span class="hidden-sm-down ml-1">Hourly Service
                                                                Details</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#finance"
                                                            role="tab">
                                                            <i class="fas fa-landmark text-danger"></i>
                                                            <span class="hidden-sm-down ml-1">Finance</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab"
                                                            href="#feedback" role="tab">
                                                            <i class="fas fa-comment text-success"></i>
                                                            <span class="hidden-sm-down ml-1">Feedback</span>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#history"
                                                            role="tab">
                                                            <i class="fas fa-history text-info"></i>
                                                            <span class="hidden-sm-down ml-1">History</span>
                                                        </a>
                                                    </li>
                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">

                                                        <?php
                                                              include('includes/booking_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="assignment" role="tabpanel">

                                                        <?php
                                                              include('includes/booking_assignment_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="service_details" role="tabpanel">

                                                        <?php
                                                              include('includes/service_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="hourly_service_details"
                                                        role="tabpanel">

                                                        <?php
                                                              include('includes/hourly_service_details_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="finance" role="tabpanel">

                                                        <?php
                                                              include('includes/booking_finance_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="feedback" role="tabpanel">

                                                        <?php
                                                              include('includes/booking_feedback_tab.php');
                                                         ?>

                                                    </div>
                                                    <div class="tab-pane fade" id="history" role="tabpanel">

                                                        <?php
                                                              include('includes/booking_history_tab.php');
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
    <script src="../js/modules/booking.js"></script>

    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>

    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>

    <script>
    $("#assignemployee_dropdown").select2();
    $("#booking_status").select2();
    </script>

    <script>
    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_bookings").addClass("active");
    });
    $(document).ready(function() {
        $('#start_time').timepicker({});
        $('#end_time').timepicker({});
    });
    </script>

</body>


</html>