<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('controller/service_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        Services
    </title>
    <meta name="description" content="View Schema">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

$UserType = SessionCheck();
$Servicedata = getAllServices($conn);
$Servicedata = json_decode($Servicedata, true);

if (isset($_GET['action']) && $_GET['action'] == 'active' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery = "UPDATE services SET status=0 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery);
    header("location:view-services.php");
}
if (isset($_GET['action']) && $_GET['action'] == 'deactive' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery1 = "UPDATE services SET status=1 WHERE ID=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery1);
    header("location:view-services.php");
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
                        <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Services</li>
                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Services</span>
                                    </h2>
                                    <a href="add-services" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Service Name</th>
                                                    <th>Url</th>
                                                    <th>Featured</th>
                                                    <th>Display Priority</th>
                                                    <th>Status</th>
                                                    <th>Edit</th>
                                                    <!-- <th>Delete</th> -->

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($Servicedata as $Servicevalue) {

                                                    $ID  = $Servicevalue['ID'];
                                                    $status  = $Servicevalue['status'];

                                                    $service_img= $Servicevalue['service_img'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $i; ?></td>
                                                        <td><?php echo $Servicevalue['Name']; ?></td>

                                                        <td><?php echo $Servicevalue['ServiceUrl']; ?></td>
                                                        <td> <?php if ($Servicevalue['featured'] == 1) { ?>
                                                                <i class="fa fa-check" aria-hidden="true" style="color: blue;"></i>
                                                            <?php } ?>
                                                        </td>
                                                        <td><?php echo $Servicevalue['DisplayPriority']; ?></td>
                                                        <td>
                                                            <?php if ($status == 0) { ?>
                                                                <a href="?action=deactive&ID=<?php echo $ID; ?>" class="btn btn-dark btn-sm shadow-none waves-effect waves-dark" title="Click to active" data-toggle="tooltip">
                                                                    Deactive</a>
                                                                </a>
                                                            <?php } else { ?>
                                                                <a href="?action=active&ID=<?php echo $ID; ?>" class="btn btn-success btn-sm shadow-none waves-effect waves-dark" title="Click to Deactive" data-toggle="tooltip">
                                                                    Active</a>
                                                                </a>
                                                            <?php } ?>
                                                        </td>
                                                        <td><span style='cursor:pointer;'><a href="update-service?ID=<?php echo $ID; ?>"><i class='fal fa-edit'></i></a></span></td>
                                                        <!-- <td><a onclick="DeleteService('<?php echo $ID; ?>','<?php echo $service_img; ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td> -->
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
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div> <!-- END Page Content -->
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
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_services").addClass("active");
            $("#main_services").addClass("active");
        });
    </script>
    <script>
        $(document).ready(function() {

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
        function DeleteService(deleteID,serviceImg) {
            //alert(deleteID);
            alertify.confirm('Aryadibusiness ', 'Do you really want to delete service', function() {
                    $.post("action/get_service_location.php", {
                            serviceID: deleteID
                        },
                        function(data, status) {
                            // alert(data);
                            // alert(status);

                            if (data == 'SUCCESS') {
                                $.post("action/delete_service.php", {
                                        deleteID: deleteID,
                                        serviceImg: serviceImg
                                    },
                                    function(data, status) {
                                        // alert(data);
                                        // alert(status);
                                        status = status.trim()
                                        if (status == 'success') {
                                            alertify.alert('Aryadibusiness ', "Service has been Deleted");
                                            setTimeout(function() {
                                                location.href = "view-services";
                                            }, 2000);
                                            /*window.location.assign("user_dashboard.php");*/
                                        } else {
                                            alertify.alert(data);
                                        }
                                    });
                                /*window.location.assign("user_dashboard.php");*/
                            } else {
                                alertify.alert('Aryadibusiness ', "Delete Location Service First");
                            }
                        });

                },
                function() {
                    alertify.error('Deletion Cancelled')
                });
        }
    </script>

</body>


</html>