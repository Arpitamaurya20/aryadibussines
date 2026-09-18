<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/leave_configuration_controller.php');

        setNavigation($_SESSION['Roles']);
        $UserType = SessionCheck();
        $conn = _connectodb();
        //$total_projects = getTotatProjects($conn,$Enterpriseid);
        ?>
    <meta charset="utf-8">
    <title>
        Manage States - TechXpert
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Holidays</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>View Leave Configuration </h2>
                                    <?php if($UserType=="Admin"||"Sub Admin") { ?>
                                    <a href="#" onclick="ExportLeaveData()" class="btn btn-info"style="margin-right:20px;">Export Data</a>
                                    <?php } ?>
                                    <a href="#" onclick="OpenLeave_modal()" class="btn btn-info"
                                        style="margin-right:20px;">Add Leave</a>

                                </div>


                                <?php
                                 include('./include/holiday-list-view.php')
                                 ; ?>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    <div class="modal fade" id="add_edit_leave_modal"  role="dialog"
                        aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Add Leave </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form METHOD="POST" id="add_update_leave_form">
                                        <div class="form-group">
                                            <div class="row mt-3">
                                                <div class="col-12">
                                                    <label for="categories">Type Of Leave <span class="text-danger">*</span></label>
                                                    <select class="select2 form-control w-100" id="type_of_leave"
                                                    name="type_of_leave">
                                                    <option value="-1">Search & Select</option>
                                                    <?php
                                                        $leaveTypes = array(
                                                        'CL',
                                                        'SL'
                                                        );

                                                      foreach ($leaveTypes as $type) {
                                                        echo "<option value='$type'>$type</option>";
                                                      }
                                                    ?>
                                                    </select>
                                                </div>
                                            </div> 

                                            <div class="row mt-3">
                                               <div class="col-12">
                                                    <label for="spare_part">Number Of Leave<span class="text-danger">*</span></label>
                                                     <input type="number" class="form-control" name="number_of_leave"
                                                            id="number_of_leave" placeholder="Enter The number ">
                                                </div>      
                                            </div>
                                        </div>

                                        <input type="hidden" id="form_action" name="form_action" value="add" />
                                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                                        <button type="submit" id="submit" class="btn btn-primary"
                                            onclick="return AddUpdateLeave()">Submit</button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    </div>

                </main>

                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->

                <!-- BEGIN Page Footer -->
                <?php include('../includes/common_footer.php') ?>
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
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/conf-leave-configuration.js"></script>
    <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-leave-configurations').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/holiday-list-post.php<?php echo $filter_param; ?>'
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
                        data: 'TypeOfLeave'
                    },
                    {
                        data: 'NumberOfLeave'
                    },
                    {
                        data: 'Update'
                    },
                    {
                        data: 'Delete'
                    }
                ]

            });
        });

    </script>

</body>


</html>