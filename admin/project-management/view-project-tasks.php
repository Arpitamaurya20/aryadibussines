<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../includes/autoloader.inc.php');
    $core = new Core();
    $dbh = new Dbh();
    $UserType = $core->SessionCheck();

    setNavigation($_SESSION['Roles']);
    $conn = $dbh->_connectodb();

    $projects = new Projects($conn);

    $ProjectID = "N.A.";
    if(!isset($_SESSION['ProjectID']))
    {
        // Redirect to Project Screen
    }
    else
    {
        $ProjectID = $_SESSION['ProjectID'];
    }
    $project_details = $projects->GetProjectDetails($ProjectID);
    $ProjectName = $project_details['ProjectName'];
    ?>
    <meta charset="utf-8">
    <title>
        View Project Tasks
    </title>
    <meta name="description" content="View Project Tasks">
    <?php
    include('../includes/common_head_content.php');
    ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
     <style>
    .modal_header {
        background-color: #003f88;
        color: #fff;
    }

    .modal_header button {
        opacity: 1;
        color: #fff;
    }

    .edit_header {
        background-color: #027dc1;
        color: #fff;
    }

    .form_submit {
        background-color: #2196f3;
        color: #fff;
        border: none;
        border-radius: 4px;
    }

    .edit_header .close {
        opacity: 1 !important;
        color: #fff;

    }

    .tab_modal_heading h2 {
        font-size: 18px;
        text-align: center;
        color: #fff;
        font-weight: 500;
        margin-bottom: 20px;
    }

    </style>
</head>
<?php

?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
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
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <ol class="breadcrumb page-breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                            <li class="breadcrumb-item"><a href="view-projects">View Projects</a></li>
                            <li class="breadcrumb-item"><?php echo $ProjectName; ?></li>
                            <li class="breadcrumb-item active">View Tasks</li>
                        </ol>
                    </div>
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Project Tasks</span>
                                    </h2>
                                   <!-- 
                                    <a href="#" onclick="ExportProjectsData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                   
                                    <a onclick="OpenProjectModal('add')" class="btn btn-info text-white" style="margin-right:20px;">Add Project</a> -->
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        
                                        <!-- datatable start -->
                                        <table id="view-project-tasks"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Project Name</th>
                                                    <th>Task Title</th>
                                                    <th>Start Date</th>
                                                    <th>Expected End Date</th>
                                                    <th>Actual End Date</th>
                                                    <th>Progress</th>
                                                    <th>Cost</th>
                                                    <th>Status</th>
                                                    <th>Edit</th>
                                                    <!-- <th>Delete</th> -->
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
    <script src="../js/modules/projects.js"></script>
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script>

        $(document).ready(function() {
            var i = 1;
            $('#view-project-tasks').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/project-tasks-list-post.php'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                
                'columns': [{
                        "data": "id",
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'ProjectName'
                    },
                    {
                        data: 'TaskTitle'
                    },
                    {
                        data: 'StartDate'
                    },
                    {
                        data: 'ExpectedEndDate'
                    },
                    {
                        data: 'ActualEndDate'
                    },
                    {
                        data: 'Progress'
                    },
                    {
                        data: 'Cost'
                    },
                    {
                        data: 'Status'
                    },
                    {
                        data: 'Edit'
                    }
                ]


            });
        });



    $(document).ready(function() {
        $("#js-nav-menu").addClass("active");
        $("#js-nav-menu").addClass("open");
        $("#nav_projects").addClass("active");
    });
    </script>
    <script>
    $(document).ready(function() {
        // $('#view-employees').dataTable({
        //     responsive: true,
        //     "ordering": false
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
    </script>
</body>

<!-- Modals -->
<div class="modal fade" id="task_status_modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form METHOD="POST" id="task_daily_progress_form">
                    <div class="modal-header modal_header">
                        <h5 class="modal-title"> Edit Tasks </h5>
                        <!--a onclick="AddMoreTaskDates()" class="text-white ml-3">
                          <i class="fas fa-plus"></i>
                        </a--> 
                        <div class="ml-3">
                            <!-- <label>Procject Status</label> <br> -->
                            <select class="form-control" name="project_task_status" id="project_task_status">
                                <option value="">Select Task Status</option>
                                <option value="To Start">To Start</option>
                                <option value="In Progress">In Progress</option>
                                <option value="On Hold">On Hold</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>    
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        
                            <div class="form-group" id="task_div_ui">
                            </div>
                            <input type="hidden" id="task_id" name="task_id" value="-1" />
                            <button class="btn btn-primary" id="project_edit_task_modal_btn" onclick="return SaveTaskProgress()">Save</button>
                            <?php
                                if($UserType == "Admin")
                                {
                                    ?>
                                    <button class="btn btn-danger" id="project_modal_btn" onclick="return DeleteProjectTask()">Delete</button>
                                    <?php
                                }
                            ?>
                    </div>
                </form>
            </div>
        </div>
    </div>


    

</html>