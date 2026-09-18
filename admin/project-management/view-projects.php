<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include('../controllers/common_controllers.php');
    include('../includes/autoloader.inc.php');
    $core = new Core();
    $dbh = new Dbh();
    $core->SessionCheck();
    $UserType = $core->SessionCheck();
    setNavigation($_SESSION['Roles']);
    $conn = $dbh->_connectodb();

    $employee = new Employee($conn);
    $employees_array = $employee->setEmployeeArray("Active");
    ?>
    <meta charset="utf-8">
    <title>
        View Projects
    </title>
    <meta name="description" content="View Projects">
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
                            <li class="breadcrumb-item"><a href="../dashboard/analytics_dashboard">Aryadibusiness</a></li>
                            <li class="breadcrumb-item active">View Projects</li>
                        </ol>
                    </div>
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                        View Projects</span>
                                    </h2>
                                   
                                    <a href="#" onclick="ExportProjectsData()" class="btn btn-info" style="margin-right:20px;">Export Data</a>
                                   
                                    <a onclick="OpenProjectModal('add')" class="btn btn-info text-white" style="margin-right:20px;">Add Project</a>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        
                                        <!-- datatable start -->
                                        <table id="view-projects"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Project Name</th>
                                                    <th>Project Manager</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Ticket Reference</th>
                                                    <th>View Tasks</th>
                                                    <th>Add Task</th>
                                                    <th>Status</th>
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
            $('#view-projects').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'ajax/projects-list-post.php'
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
                        data: 'ProjectManager'
                    },
                    {
                        data: 'StartDate'
                    },
                    {
                        data: 'EndDate'
                    },
                    {
                        data: 'TicketReference'
                    },
                    {
                        data: 'ViewTasks'
                    },
                    {
                        data: 'AddTasks'
                    },
                    {
                        data: 'Status'
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

        $('#ticket_reference').select2({
        ajax: {
          url: '../corporate-tickets/ajax/load-all-tickets.php', // Replace with your actual backend endpoint
          dataType: 'json',
          delay: 250,
          data: function(params) {
            return {
              q: params.term, // Search term entered by the user
              page: params.page
            };
          },
          processResults: function(data, params) {
            // Assuming your backend returns data in the format { results: [...] }
            return {
              results: data.results,
              pagination: {
                more: (params.page * 30) < data.total_count
              }
            };
          },
          cache: true
        },
        placeholder: 'Search for Ticket number (minimum 3 characters)',
        minimumInputLength: 3// Minimum number of characters before triggering a query
      });

    });
    </script>
</body>

<!-- Modals -->
<!-- Add Project Modal -->
<div class="modal fade" id="add_project_modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title" id="company_modal_title"> Create/Update Project </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_update_project_form">
                        <div class="form-group">
                            <div class="row">
                                <div class="col-12">
                                    <label>Project Name <span class="text-danger">*</span> </label>
                                    <input type="text" class="form-control" name="project_name" id="project_name" placeholder="Enter Project Name">
                                </div>


                                
                                <div class="col-12 mt-3">
                                    <label> Start Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="project_start_date" id="project_start_date" placeholder="Enter Project Start Date">
                                </div>

                                <div class="col-12 mt-3">
                                    <label> End Date <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="project_end_date" id="project_end_date" placeholder="Enter Project End Date">
                                </div>

                                <div class="col-12 mt-3">
                                    <label> Project Manager <span class="text-danger">*</span></label>
                                    <select class="select2 form-control w-100" id="project_manager"
                                        name="project_manager">
                                        <option value="-1">Search & Select</option>
                                        <?php
                                        foreach($employees_array as $EmployeeID=>$employee)
                                        {
                                        ?>
                                            <option value="<?php echo $EmployeeID;?>">
                                                <?php echo $employee['Name'];?>
                                            </option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-3" id="add_ticket_number">
                                    <label> Ticket Reference</label>
                                    <select id="ticket_reference" class="select2 form-control w-100" name="ticket_reference"></select>
                                </div>
                                <div class="col-12 mt-3" id="edit_ticket_number" style="display:none;">
                                    <label> Ticket Reference</label>
                                    <span id="ticket_reference_number_view"></span>
                                    <span class="badge badge-primary cursor-pointer" onclick="ShowTicketChangeBox()">Edit</span>
                                    <input type="hidden" name="ticket_reference_edit" id="ticket_reference_edit" value="" />
                                </div>

                                <div class="col-12 mt-3">
                                    <label> Status <span class="text-danger">*</span></label>
                                    <select class="select2 form-control w-100" id="project_status" name="project_status">
                                        <option value="Active">Active</option>
                                        <option value="Holded">Holded</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>


                            </div>
                           

                        </div>
                        <input type="hidden" id="form_action" name="form_action" value="add" />
                        <input type="hidden" id="form_id" name="form_id" value="-1" />
                        <button class="btn btn-primary" id="project_modal_btn" onclick="return SaveProject()">Save</button>
                        <?php
                        if($UserType == "Admin")
                        {
                            ?>
                            <button class="btn btn-danger" id="project_modal_btn_delete" onclick="return DeleteProject()">Delete</button>
                            <?php
                        }
                        ?>
                    </form>
                </div>

            </div>
        </div>
    </div>


    <!-- Add Task Modal -->
    <div class="modal fade" id="add_task_modal" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title"> Create Task </h5>
                    <a onclick="AddMoreTask()" class="text-white ml-3">
                      <i class="fas fa-plus"></i>
                    </a> 
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form METHOD="POST" id="add_task_form">
                        <div class="form-group" id="task_div_ui">
                            <?php include('ajax/task_form.php'); ?>
                        </div>
                        <input type="hidden" id="project_id" name="project_id" value="-1" />
                        <button class="btn btn-primary" id="project_task_modal_btn" onclick="return SaveTask()">Save</button>
                        <input type="hidden" name="task_counter" id="task_counter" value="1" />
                    </form>
                </div>

            </div>
        </div>
    </div>

</html>