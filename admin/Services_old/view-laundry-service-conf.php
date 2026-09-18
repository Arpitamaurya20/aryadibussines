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
<?php

$UserType = SessionCheck();
$ID = $_GET['ID'];
$GetLuaundryData = getAllLuandryServices($conn,$ID);

$where = " where 1";
$subservice_array_temp = _getTableRecords($conn,'subservice',$where);
$subservice_array = array();
foreach($subservice_array_temp as $Sub_service)
{
    $Sub_service_ID = $Sub_service['ID'];
    $subservice_array[$Sub_service_ID] = $Sub_service['title'];
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
                        <li class="breadcrumb-item"><a href="./view-services">Services</a></li>
                        <li class="breadcrumb-item"><a href="./update-service?ID=30">Laundry Service</a></li>
                        <li class="breadcrumb-item active">View Laundry Services Configuration</li>
                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Laundry Services Configuration</span>
                                    </h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view_sub_service_tabel"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Sub Service</th>
                                                    <th>Type Of Clothes</th>
                                                    <th>Price</th>
                                                    <th>Action</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i = 1;
                                                    foreach ($GetLuaundryData as $LuaundryData) {
                                                    ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>
                                                    <td><?php echo $subservice_array[$LuaundryData['SubServiceID']]; ?></td>
                                                    <td><?php echo $LuaundryData['TypeOfClothes']; ?></td>
                                                    <td><?php echo $LuaundryData['Price']; ?></td>
                                                    <td><span style='cursor:pointer;'><i class='fal fa-edit text-danger'
                                                                onclick="open_EditLaundryModal(<?php echo $LuaundryData['ID']; ?>)"></i></span>&nbsp;&nbsp;&nbsp;&nbsp;<span
                                                            style='cursor:pointer;'
                                                            onclick="DeleteLaundryService(<?php echo $LuaundryData['ID']; ?>)"><i
                                                                class='fal fa-trash text-danger'></i></span></td>

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

                <!-- Edit Modal  -->

                <div class="modal fade" id="editlaundryservice" tabindex="-1" aria-labelledby="luandryservicelLabel"
                    aria-hidden="true">
                    <div class="modal-dialog ">
                        <div class="modal-content">
                            <div class="modal-header modal_header">
                                <h5 class="modal-title" id="laundryservicelLabel">Edit Laundry Service Configuration
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form onsubmit="return false;" id="luandryservice_form">
                                    <div class="form-group">
                                        <div class="row">

                                            <div class="col-12 mt-3">
                                                <label>Type Of Clothes <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="Type_of_clothes"
                                                    id="Type_of_clothes" placeholder="Enter Clothes Name">
                                            </div>

                                            <div class="col-12 mt-3">
                                                <label>Price ( as per unit ) <span class="text-danger">*</span></label>
                                                <input onkeyup="validISNumber()" type="text" class="form-control" name="Clothe_Price"
                                                    id="Clothe_Price" placeholder="Enter Price">
                                            </div>

                                            <input type="hidden" name="form_action" value='Update'>
                                            <input type="hidden" name="laundry_form_id" id="laundry_form_id" value=''>

                                            <div class="col-12 mt-3 text-center">
                                                <button id="luandry_service_btn" class="btn btn-primary"
                                                    onclick="return AddLaundryServiceConfig()">Submit</button>
                                            </div>



                                        </div>
                                    </div>
                                </form>

                            </div>

                        </div>
                    </div>
                </div>

                <!-- Edit Modal  -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
    include('../includes/common_modules.php');
    include('../includes/common_scripts.php');
    ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/service-configuration.js"></script>
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

        $('#view_sub_service_tabel').dataTable({
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


</body>


</html>