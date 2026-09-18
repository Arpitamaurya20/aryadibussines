<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/join_us_controller.php');

    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        Join Us
    </title>
    <meta name="description" content="View Villages">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
</head>
<?php

$UserType = SessionCheck();
$APPA = false;
if ($UserType == "Admin") {
    $JoinUsData = getAllJoinUsData($conn);
    $JoinUsData = json_decode($JoinUsData, true);
}

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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">View Join Us </li>

                    </ol>

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Join Us</span>
                                    </h2>
                                    <?php if($UserType=="Admin" || $UserType=="Sub Admin"){ ?>
                                    <a href="#" onclick="ExportJoinUsData()" class="btn btn-info"
                                        style="margin-right:20px;">Export Data</a>
                                    <?php } ?> 
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Phone</th>
                                                    <th>Email</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($JoinUsData as $JoinUsData) {

                                                    $id  = $JoinUsData['ID'];
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>

                                                    <td><?php echo $JoinUsData['Name']; ?></td>
                                                    <td><?php echo $JoinUsData['Phone']; ?></td>
                                                    <td><?php echo $JoinUsData['Email']; ?></td>
                                                    <td><?php echo $JoinUsData['Message']; ?></td>
                                                </tr>
                                                <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>

                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->

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
    <script>
    $(document).ready(function() {
        $("#nav_contact").addClass("active");
        $("#nav_contact").addClass("open");
        $("#nav_join_us_enquiries").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {
        $("#nav_join_us_enquiries").addClass("active");
        $('#view-projects').dataTable({
            responsive: true
        });
        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });
        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });
    });
    </script>
    <script type="text/javascript">
    function Deleteenquiry(deleteID) {

        //alert(areaID);
        alertify.confirm('TechXpert ', 'Do you really want to delete Join Us Details', function() {
                $.post("action/delete_join_us.php", {
                        deleteID: deleteID
                    },
                    function(data, status) {
                        if (data == "success") {
                            alertify.alert('TechXpert ', "Join Us Data has been Deleted");
                            setTimeout(function() {
                                location.href = "view_join_us_enquiry";
                            }, 2000);
                            /*window.location.assign("user_dashboard.php");*/
                        } else {
                            alertify.alert(data);
                        }
                    });
            },
            function() {
                alertify.error('Deletion Cancelled')
            });
    }
    function ExportJoinUsData() {
        $.ajax({
           url: "action/export_join_us.php",
           type: "POST",
           data: $("#import_form").serialize(),
           success: function (data) {
               window.location.href = "report.xls";
           },
        });
      return false;
    }
    </script>
</body>


</html>