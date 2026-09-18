<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

            include('../controllers/common_controllers.php');
            include('controller/attendance_controller.php');
            include('../employees/controller/employee_controller.php');
            include('../includes/autoloader.inc.php');
            setNavigation($_SESSION['Roles']);
        ?>
        <meta charset="utf-8">
        <title>Employees Attendance Records</title>
        <meta name="description" content="View Schema">
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
        $currentYear = date('Y');
        $currentMonth = date('m');
        $UserType = SessionCheck();
        $core = new Core();
        $core->setTimeZone();
        $conn = _connectodb();
        $current_date = date("Y-m-d");
        $previous_date =  date('Y-m-d', strtotime('-7 days'));
        $date_range = $previous_date . " - " . $current_date;
        $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0');
        $filter_param = "?filter_date=".$date_range;

        // Get the colendar recourd
        $filter = " WHERE `year`= '".$currentYear."' AND `month` = '".$currentMonth."'";
        $sql_calender = "SELECT `date`, `is_weekend` FROM `calendar`".$filter;
        $calender = mysqli_query($conn, $sql_calender);

        $filters = " where b.IsActive = 1";
        $sql = "Select b.ID, b.Name, a.* from `employee_attendance` a LEFT JOIN employees b ON a.EmployeeID = b.ID".$filters;
        $result = mysqli_query($conn, $sql);

    ?>
    <body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
        <!-- DOC: script to save and load page settings -->
        <?php include('../js/theme_settings.js'); ?>
        <!-- BEGIN Page Wrapper -->
        <div class="page-wrapper">
            <div class="page-inner">
                <?php  include('../navigation/admin_navigation.php'); ?>
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
                            <li class="breadcrumb-item active">Attendance Details</li>
                        </ol>
                        <div class="row" id="today_attendance_stats"></div>
                        <div class="panel mb-2">  
                                <div class="panel-content p-3">
                                    <div class="row">
                                        <div class="col-md-3 ">
                                            <select class="select2 form-control w-100" id="employee_name" name="employee_name" onchange="RefreshAttendance()">
                                                <option value="-1">Select Employee</option>
                                                <?php  foreach($employees_array as $employee) { ?>
                                                    <option value="<?php echo $employee['ID'];?>"><?php echo $employee['Name'];?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="filter_date" placeholder="Select date" value="<?php echo $date_range; ?>">         
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" onclick="RefreshAttendance();" class="btn btn-sm btn-primary ml-3 waves-effect waves-themed">Search</button>
                                        <button type="button" onclick="ExportAttendanceData();" class="btn btn-sm btn-warning ml-3 waves-effect waves-themed">Export</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Page Container -->
                        <div class="row">
                            <div class="col-xl-12">
                                <div id="panel-1" class="panel">
                                    <div class="panel-hdr">
                                        <h2><span>Attendance Details</span></h2>
                                    </div>
                                    <div class="panel-container show">
                                        <div class="panel-content table-responsive">
                                            <!-- datatable start -->
                                            <table id="view-attendance-records" class="table table-bordered table-hover table-striped w-100">
                                                <thead>
                                                    <tr style="color:#fff; background:#184384; text-align:center;">
                                                        <th style="padding:.15rem; min-width:40px; " rowspan="2">S.N.</th>
					                                    <th class="fname" style="padding:.15rem; min-width:150px;" rowspan="2">Employee Name</th>
                                                        <?php 
                                                            if(!empty($calender)){
                                                                $date_arr=array();
                                                                    foreach ($calender as $key => $values){
                                                                    if($values['date']){
                                                                        $dd = date("d-m-Y", strtotime($values['date']));
                                                                        $wd = date("l", strtotime($values['date']));
                                                                        $date_count = $dd." (".$wd.")";
                                                                    }else{
                                                                        $date_count = "";
                                                                    }
                                                                    $date_arr[]= $date_count;
                                                            ?>
                                                        <th style="padding:.15rem;" colspan="6"><?php if($date_count){ echo $date_count; } ?></th>
                                                        <?php } ?>
                                                        <th style="padding:.15rem;" rowspan="2">No. of Present</th>
                                                        <th style="padding:.15rem;" rowspan="2">No. of Absent</th>
                                                        <th style="padding:.15rem;" rowspan="2">No. of HD+HDO</th>
                                                        <th style="padding:.15rem;" rowspan="2">Total Half Day</th>
                                                        <th style="padding:.15rem;" rowspan="2">Total Work Hours</th>
                                                        <th style="padding:.15rem;" rowspan="2">Short In Hours</th>
                                                        <th style="padding:.15rem;" rowspan="2">OT In Hours</th>
                                                        <th style="padding:.15rem;" rowspan="2">Final OT</th>
                                                        <th style="padding:.15rem;" rowspan="2">Total Leaves</th>
                                                        <th style="padding:.15rem;" rowspan="2">Total Worked Day</th>
                                                        <?php
                                                            echo '<tr style="color:#fff; background:#2866c3; text-align:center;">';
                                                            $counts = count($date_arr);
                                                            for($i=1; $i<= $counts; $i++){
                                                        ?>
                                                            <th style="padding:.15rem;">IN</th>
                                                            <th style="padding:.15rem;">OUT</th>
                                                            <th style="padding:.15rem;">TWH</th>
                                                            <th style="padding:.15rem;">OT</th>
                                                            <th style="padding:.15rem;">ST</th>
                                                            <th style="padding:.15rem;">STATUS</th>
                                                        <?php 
                                                            } 
                                                            echo "</tr>";
                                                        }
                                                        ?>
                                                    </tr>
                                                </thead>
                                                <tbody id="employee_attendance_table">
                                                <?php
                                                if(!empty($result)){
							                        $count = 0;
                                                    
                                                    foreach($result as $rows){
                                                        $count= $count+1;
                                                        
                                                ?>
                                                    <tr style="text-align:center;">
                                                        <td style="padding:.13rem; background:#184384; color:#fff;"><?php echo $count; ?></td>
                                                        <td class="fname" style="padding:.13rem;"><?php echo $rows['Name']; ?></td>
                                                        
                                                        <?php
                                                            if(!empty($calender)){


                                                            foreach ($calender as $key => $value){
                                                        ?>
                                                            <td style="padding:.13rem; background:#6d9ce2;"><?php echo $value['date']; ?></td>
                                                            <td style="padding:.13rem; background:#6d9ce2;">H</td>
                                                            <td style="padding:.13rem; background:#6d9ce2;">H</td>
                                                            <td style="padding:.13rem; background:#6d9ce2;">H</td>
                                                            <td style="padding:.13rem; background:#6d9ce2;">H</td>
                                                            <td style="padding:.13rem; background:#c0fbc0;">P</td>	
                                                        <?php 
                                                            }

                                                        } 
                                                        ?>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                        <td style="padding:.13rem;">00</td>
                                                    </tr>
                                                <?php 
                                                    }
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
        <script src="../js/modules/attendance-list.js"></script>
        <script src="../js/modules/employee.js"></script>
        <!--<script type="text/javascript">
            $(document).ready(function() 
            {
                var i = 1;
            $('#view-attendance-records').dataTable({
                responsive: true,
                'processing': true,
                'serverSide': true,
                'ordering': false,
                'serverMethod': 'post',
                'ajax': {
                    'url': 'action/view-attendance-sheet-post.php<?php echo $filter_param;?>'
                },
                'columnDefs': [{
                    "targets": [0],
                    "className": "text-center"
                }],
                
                'columns': [
                    {
                        data: 'S.N.',
                    },
                    {
                        data: 'EmployeeName'
                    },
                    {
                        data: 'RecordDate'
                    },
                    {
                        data: 'CheckInTime'
                    },
                    {
                        data: 'CheckOutTime'
                    },
                    {
                        data: 'Duration'
                    }
                ]


                });
                $('#filter_date').daterangepicker({
                    locale: {
                        format: 'YYYY-MM-DD'
                    }
                });
                $("#employee_name").select2();
                GenerateTodayAttendanceStats();
            });
        </script>-->
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