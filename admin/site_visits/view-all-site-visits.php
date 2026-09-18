<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
        include('../includes/autoloader.inc.php');

        setNavigation($_SESSION['Roles']);
	?>
    <meta charset="utf-8">
    <title>
    View All Site Visits
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
    .select2-container
    {
        z-index: 1;
    }
    .modal-image {
      width: 400px;
      height: 400px;
      object-fit: cover;
    }
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
</head>
<?php

	$UserType = SessionCheck();
    $core = new Core();
    $core->setTimeZone();
    $conn = _connectodb();
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . " - " . $current_date;
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0');
    $filter_param = "?filter_date=".$date_range;

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
                        // $EmpID = '';
                    ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->

    


                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Site Visit Reports</li>

                    </ol>

                    <div class="panel mb-2">  
                            <div class="panel-content p-3">
                                <div class="row">
                                
                                    
                                    <div class="col-md-3 ">
                                       
                                       
                                    </div>
                                            
                                
                                <div class="col-md-3">
                                    <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">         
                                </div>
                                
                                <div class="col-md-3">
                                    <button type="button" onclick="SearchSiteVisits();" class="btn btn-sm btn-primary ml-3 waves-effect waves-themed">Search</button>
                                    <!--button type="button" onclick="ExportAttendanceData();" class="btn btn-sm btn-warning ml-3 waves-effect waves-themed">Export</button-->
                                </div>
                            </div>
                        </div>
                    </div>

                    
                   
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                    Site Visits</span>
                                    </h2>
                                    
                                </div>

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                         <table id="view-site-visits"
                                            class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                     <th>#</th>
                                                    <th>Corporate</th>
                                                    <th>Branch</th>
                                                    <th>Visit Title</th>
                                                    <th>Status</th>
                                                   
                                                    <th>Contact Person</th>
                                                    <th>Created On</th>
                                                    <th>Completed On</th>
                                                    <th>Details</th>
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
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/sitevisits.js"></script>
    <script type="text/javascript">
        $(document).ready(function() 
        {
            var i = 1;
        $('#view-site-visits').dataTable({
            responsive: true,
            'processing': true,
            'serverSide': true,
            'ordering': false,
            'serverMethod': 'post',
            'ajax': {
                'url': 'action/view-all-site-visits-post.php<?php echo $filter_param;?>'
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
                    data: 'Corporate'
                },
                {
                    data: 'Branch'
                },
                {
                    data: 'VisitTitle'
                },
                {
                    data: 'Status'
                },
                
                {
                    data: 'ContactPerson'
                },
                {
                    data: 'CreatedOn'
                },
                {
                    data: 'CompletedOn'
                },
                {
                    data: 'Details'
                }
            ]


            });
            $('#filter_date').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
            $("#nav_site_visits").addClass("active");

        });
    
    </script>

</body>


</html>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
      </div>
      <div class="modal-body d-flex justify-content-center">
        <!-- Display Image with Fixed Size -->
        <img id="modalImage" alt="Preview" class="modal-image">
      </div>
    </div>
  </div>
</div>