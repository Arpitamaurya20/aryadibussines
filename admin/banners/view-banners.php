<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/banners_controller.php');
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        View banners
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
{
    $bannersdata = getAllbanners($conn);
    //print_r($bannersdata);
    $bannersdata = json_decode($bannersdata, true);
}

//$Villagedata = getSingleCFLVillage($conn,$getClfvalue['AssociatedBlock']);
//$Villagedata = json_decode($Villagedata,true);

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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">TechXpert</a></li>
                        <li class="breadcrumb-item active">View Banners </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Banners</span>
                                    </h2>
                                    <a href="add-banners" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-projects" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>

                                                    <th>Image</th>

                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($bannersdata as $bannersdata) {

                                                    $id  = $bannersdata['id'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $i; ?></td>

                                                        <td><a href="" onclick="banner('<?php echo $bannersdata['banner'] ?>')"><img src="../media/banners/<?php echo $bannersdata['banner']; ?>" width="60px" alt=""></a></td>


                                                        <td><a href="" onclick="Deletebanners('<?php echo $bannersdata['id']; ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td>

                                                    </tr>
                                                <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>

                                        </table>
                                        <!-- datatable end -->
                                        <script>
                                            function banner(banner) {
                                                var url = "../media/banners/" + banner;
                                                window.open(url, 'Documents', ['menubar=yes,scrollbars=yes,controlbox=yes', 'top=10,left=150,width=1050,height=650']);
                                                return;
                                            }
                                        </script>
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
            $("#nav_banners").addClass("active");
            $("#nav_site_setting").addClass("active");
        });
    </script>
    <script>
        $(document).ready(function() {
            $("#nav_projects").addClass("active");
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
        function Deletebanners(deleteID) {

            //alert(areaID);
            alertify.confirm('TechXpert ', 'Do you really want to delete Banner', function() {
                    $.post("action/delete_banners.php", {
                            deleteID: deleteID
                        },
                        function(data, status) {
                            if (data == "success") {
                                alertify.alert('TechXpert ', "Banner  has been deleted");
                                setTimeout(function() {
                                    location.href = "view-banners.php";
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
    </script>
</body>


</html>