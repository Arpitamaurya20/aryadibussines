<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');

        setNavigation($_SESSION['Roles']);
        $UserType = SessionCheck();
        $conn = _connectodb();
        //$total_projects = getTotatProjects($conn,$Enterpriseid);
        ?>
    <meta charset="utf-8">
    <title>
        Manage TAT Groups - Aryadibusiness
    </title>
    <meta name="description" content="Manage TAT Groups">
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
<?php

    $UserType = SessionCheck();

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
                        <li class="breadcrumb-item active">Uom</li>

                    </ol>


                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        Manage TAT Groups</span>
                                    </h2>

                                    <a href="#" onclick="AddTATGroup()" class="btn btn-info"
                                        style="margin-right:20px;">Add</a>

                                </div>

                               <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-tat-groups" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>TAT Group </th>
                                                    <th>Default TAT Days</th>
                                                    <th>Default TAT Hours</th>
                                                    <th>Last Updated At</th>
                                                    <th>Last Updated By</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                    
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
    <script src="../js/modules/conf-tat-group.js"></script>

    <script>
        $(document).ready(function() {
            var i = 1;
            $('#view-tat-groups').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'include/tat-group-list-post.php'
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
                        data: 'TATGroup'
                    },
                    {
                        data: 'DefaultTATDays'
                    },
                    {
                        data: 'DefaultTATHours'
                    },
                    {
                        data: 'UpdatedAt'
                    },
                    {
                        data: 'UpdatedBy'
                    },
                    {
                        data: 'Action'
                    }
                ]

            });
        });

    </script>

</body>


</html>

<div class="modal fade" id="add_tat_group_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title" id="add_tat_group_modal_heading">Add TAT Group </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form METHOD="POST" id="add_tat_group_form">
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" class="form-control" name="tat_group_name" id="tat_group_name" placeholder="Enter TAT Group Name">
                    </div>
                    <div class="form-group">
                        <label>Default TAT Days</label>
                        <input type="text" class="form-control" name="tat_group_default_days" id="tat_group_default_days" placeholder="Enter TAT Default Days">
                    </div>
                    <div class="form-group">
                        <label>Default TAT Hours</label>
                        <input type="text" class="form-control" name="tat_group_default_hours" id="tat_group_default_hours" placeholder="Enter TAT Default Hours">
                    </div>
                    <button type="submit" class="btn btn-primary" onclick="return update_tat_group()">Save</button>
                    <input type="hidden" name="form_action" id="form_action" value="add" />
                    <input type="hidden" name="form_id" id="form_id" value="-1" />
                </form>
            </div>

        </div>
    </div>
</div>