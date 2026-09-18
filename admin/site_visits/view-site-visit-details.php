<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    require_once('../includes/autoloader.inc.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        View Profile
    </title>
    <meta name="description" content="View Profile">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="./css/edit-modal.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

    <style>
    .employee_details {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .employee_details label {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 0px;
    }

    .employee_details span {
        font-size: 13px;
        font-weight: 500;
    }
    .modal-image {
      width: 400px;
      height: 400px;
      object-fit: cover;
    }
    </style>

</head>

<?php
$UserType = SessionCheck();
$SiteVisitID = "N.A.";
if(isset($_SESSION['SiteVisitID']))
{
    $SiteVisitID = $_SESSION['SiteVisitID'];
}
else
{
    die("Invalid Page");
}
$site_visits_obj = new Sitevisits($conn);


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
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item"><a href="../site_visits/view-all-site-visits">Site Visits</a></li>
                        <li class="breadcrumb-item active">Site Visit Details</li>

                    </ol>
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Site Visit
                                    </h2>
                                    <button type="button" onclick="DownloadSiteVisitReport(<?php echo $SiteVisitID;?>);" id="download_button" class="btn btn-sm btn-info ml-3 mr-3 waves-effect waves-themed">Download</button>
                                </div>
                                <div id="panel-employee" class="panel mt-3">
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
                                                        <a class="nav-link fs-lg px-4" data-toggle="tab" href="#attendance"
                                                            role="tab">
                                                            <i class="fal fa-user text-primary"></i>
                                                            <span class="hidden-sm-down ml-1">Observations</span>
                                                        </a>
                                                    </li>
                                                   
                                                    
                                                </ul>

                                                <div class="tab-content">
                                                    <div class="tab-pane fade show active" id="details" role="tabpanel">
                                                        <?php
                                                              include('includes/site_visit_details.php');
                                                         ?>
                                                    </div>
                                                    <div class="tab-pane fade" id="attendance" role="tabpanel">
                                                        <?php
                                                           include('includes/site_visit_observations.php');
                                                        ?>
                                                    </div>
 
                                                    
                                                </div> <!-- tab content -->
                                            </div> <!-- demo-v-spacing -->
                                        </div> <!-- panel-content -->
                                    </div> <!-- panel-container show -->
                                </div> <!-- panel-employee -->
                            </div> <!-- panel-1-->
                        </div> <!-- col-xl-12 -->
                    </div> <!-- row -->


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
    <script src="../js/modules/sitevisits.js"></script>
    <script>
    $(document).ready(function() {
         $("#nav_site_visits").addClass("active");
    });
    </script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
</body>


</html>