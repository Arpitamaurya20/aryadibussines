<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../authentication/auth_controller/authentication_controller.php');
    include('controller/city_controller.php');
    require_once('../includes/autoloader.inc.php');
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
$city = new City($conn);
$cities_array = $city->getAllCities();
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
                                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                                    <a href="#" onclick="ExportCityData()" class="btn btn-info"style="margin-right:20px;">Export Data</a>
                                    <?php } ?>
                                    <a href="add-city" class="btn btn-info" style="margin-right:20px;">Add</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-cities" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>City Name</th>
                                                    <th>Is Featured</th>
                                                    <th>Status</th>
                                                    <th>Edit</th>
                                                    <th>Delete</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($cities_array as $citydata) {
                                                    $CityId  = $citydata['CityId'];
                                                    $status  = $citydata['status'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo $i; ?></td>
                                                        <td><?php echo $citydata['CityName']; ?></td>
                                                        <td> <?php if ($citydata['featured'] == 1) { ?>
                                                                <i class="fa fa-check" aria-hidden="true" style="color: blue;"></i>
                                                            <?php } ?>
                                                        </td>

                                                        <td>
                                                            <?php if ($status == 0) { ?>
                                                                <a href="?action=deactive&ID=<?php echo $CityId; ?>" class="btn btn-dark btn-sm shadow-none waves-effect waves-dark" title="Click to active" data-toggle="tooltip">
                                                                    Deactive</a>
                                                                </a>
                                                            <?php } else { ?>
                                                                <a href="?action=active&ID=<?php echo $CityId; ?>" class="btn btn-success btn-sm shadow-none waves-effect waves-dark" title="Click to Deactive" data-toggle="tooltip">
                                                                    Active</a>
                                                                </a>
                                                            <?php } ?>
                                                        </td>
                                                        <td><span style='cursor:pointer;'><a href="update-city?Id=<?php echo $CityId; ?>"><i class='fal fa-edit'></i></a></span></td>
                                                        <td><a onclick="DeleteCity('<?php echo $citydata['CityId']; ?>','<?php echo $citydata['image']; ?>')"><i class="fal fa-trash" aria-hidden="true"></i></td>

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
            $("#nav_city").addClass("active");
            $("#nav_configuration").addClass("active");
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#view-cities').dataTable({
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

        function ExportCityData() {
          $.ajax({
              url: "action/export_city.php",
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