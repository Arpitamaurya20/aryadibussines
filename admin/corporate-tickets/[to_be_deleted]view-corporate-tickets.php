<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/corporate_tickets_controller.php');
        include('../company/controller/company_controller.php');
        include('../branch/controller/branch_controller.php');
        include('../branch-assets/controller/branch_assets_controller.php');
        $UserType = SessionCheck();
		$conn = _connectodb();
        setNavigation($_SESSION['Roles']);
	?>
    <meta charset="utf-8">
    <title>
        Corporate Tickets
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">

    <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

	$UserType = SessionCheck();
    $CorporateID = -1;
    $BranchID = -1;
    $techx_admin = true;
    if($UserType == "Corporate Admin")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $techx_admin = false;
    }
    if($UserType == "Corporate Branch User")
    {
        $CorporateID = $_SESSION['Roles']['CorporateID'];
        $BranchID = $_SESSION['Roles']['BranchID'];
        $techx_admin = false;
    }
	$corporate_tickets_array = getAllCorporateTickets($conn,$CorporateID,$BranchID);

    $corporate_array = getAllCompanies($conn);
    $company_array_key = generateArraywithKey($corporate_array);

    //print_r($company_array_key);

    $branches = getAllBranches($conn,-1);
    $branch_array_key = generateArraywithKey($branches);

    $branch_assets = getAllBranchAssets($conn,-1);
    $branch_assets_array_key = generateArraywithKey($branch_assets);
    //print_r($branch_assets_array_key);
	?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Corporate Tickets</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Corporate Tickets</span>
                                    </h2>
                                    <a href="#" onclick="ExportCorporateTicketData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                </div>
                             <?php
                                    include('./includes/common_footer.php')
                                ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <!-- <div class="modal fade" id="addstatus" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="exampleModalLabel">Add Booking </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_booking_status">
                                        <div class="form-group">
                                            <label for="exampleInputEmail1">Booking Status</label>
                                            <input type="text" class="form-control" name="status_name"
                                                placeholder="Enter Booking Status">

                                        </div>

                                        <button type="submit" class="btn btn-primary"
                                            onclick="return AddBookingStatus()">Submit</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div> -->

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
    <!-- END Page Wrapper -->

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/corporate-tickets.js"></script>

    <script>
    function ViewBookingDetails(TicketID) {
        $.post(
            "../controllers/setSession.php", {
                TicketID: TicketID,
            },
            function(data, status) {
                BasicURLRouter("view-corporate-tickets-details.php");
            }
        );
    }
    </script>

</body>


</html>