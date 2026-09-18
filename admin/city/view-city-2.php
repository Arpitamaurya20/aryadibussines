<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/city_controller.php');
    $UserType = SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>
        View Cities
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
    $citydata = getAllCity($conn);
    $citydata = json_decode($citydata, true);
}
if (isset($_GET['action']) && $_GET['action'] == 'active' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery = "UPDATE citydata SET status=0 WHERE CityId=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery);
    header("location:view-city.php");
}
if (isset($_GET['action']) && $_GET['action'] == 'deactive' && isset($_GET['ID']) && !empty($_GET['ID'])) {
    $Cityquery1 = "UPDATE citydata SET status=1 WHERE CityId=" . $_GET['ID'] . "";
    $result = mysqli_query($conn, $Cityquery1);
    header("location:view-city.php");
}
$filter_param = "?UserType=".$UserType;

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
                        <li class="breadcrumb-item active">View Cities </li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Cities</span>
                                    </h2>
                                    <a href="add-city" class="btn btn-info" style="margin-right:20px;">Add City</a>
                                </div>
                                <?php include('./include/city-list-view.php'); ?>

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
            var i = 1;
            $('#view-city').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/city-list-post.php<?php echo $filter_param; ?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                "order": [
                    [1, 'asc']
                ],
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'CityName'
                    },
                    {
                        data: 'IsFeatured'
                    },
                    {
                        data: 'Status'
                    },
                    {
                        data: 'Edit'
                    },
                    {
                        data: 'Delete'
                    }


                ]


            });
        });


        $(document).ready(function() {
            $("#js-nav-menu").addClass("active");
            $("#js-nav-menu").addClass("open");
            $("#nav_city").addClass("active");
            $("#nav_configuration").addClass("active");
        });

        $(document).ready(function() {
            $("#nav_projects").addClass("active");
            // $('#view-projects').dataTable({
            //     responsive: true
            // });
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

        function DeleteCity(deleteID, image) {

            //alert(areaID);
            alertify.confirm('Aryadibusiness ', 'Do you really want to delete city', function() {
                    $.post("action/delete_city.php", {
                            deleteID: deleteID,
                            image: image
                        },
                        function(data, status) {
                            if (data == "success") {
                                alertify.alert('Aryadibusiness ', "City Deleted !");
                                setTimeout(function() {
                                    location.href = "view-city.php";
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