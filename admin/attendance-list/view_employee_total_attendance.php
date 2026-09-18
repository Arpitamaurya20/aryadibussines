<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');
        include('controller/attendance_controller.php');
        include('../employees/controller/employee_controller.php');

        $UserType = SessionCheck();
        $conn = _connectodb();
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>
    Attendance Details
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
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

    $UserType = SessionCheck();
      date_default_timezone_set('Asia/Kolkata');

$EmployeesID = "N.A.";
if(!isset($_SESSION['EmployeeID']))
{
}
else
{
    $EmployeesID = $_SESSION['EmployeeID'];
}

    $EmployeesData = GetAllEmployeeInArray($conn);
    $employee_array_key = generateArraywithKey($EmployeesData);
    if(isset($_POST['submit'])) {
        $FromDate = $_POST['startdate'];
        $EndDate = $_POST['finishdate'];
        $attendance_details_array = getAttendanceDataInEmployeeWithDateFilter($conn,$FromDate,$EndDate,$EmployeesID);
    }
    elseif (isset($_POST['all_record'])) {
           $attendance_details_array = getAttendanceDataWithEmployeeID($conn,$EmployeesID);
    }
    else {
           $attendance_details_array = getAttendanceDataWithEmployeeID($conn,$EmployeesID);
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
                        // $EmpID = '';
                    ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->


                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">Attendance Details</li>

                    </ol>

                    <div class="row mb-3">
                        <div class="col-md-3 col-xs-12"></div>
                        <div class="col-md-6 col-xs-12">
                            <form method="post">
                                <div id='filters' style='text-align:center;'>
                                    <div style='background-color:#eee;padding:3px;border:1px solid #bbb'>

                                        <b>Search Records - Time Based</b><br>
                                       
                                        <label id="date">
                                            From : <input type="text" name="startdate" id="startdate" placeholder="Start date">
                                            ~
                                            To : <input type="text" name="finishdate" id="finishdate" placeholder="Finish date">
                                        </label>
                                        &nbsp;
                                        <button type="submit" name="submit" class="btn btn-default btn-primary"> Go </button>
                                        <button type="submit" name="all_record" class="btn btn-default btn-primary"> All Record</button>
                                    </div>

                                </div> <!-- Filters -->
                            </form>
                        </div>
                    </div>   
                    

                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                    Attendance Details</span>
                                    </h2>
                                    <?php 
                                    if($UserType == "Admin"||$UserType == "Sub Admin"){ 
                                    ?>
                                    <a href="#" onclick="ExportAttendanceData('<?php echo $EmployeesID;?>')" class="btn btn-info"
                                        style="margin-right:20px;">Export Attendance</a>
                                        <?php } ?> 

                                </div>

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                        <table id="view-attendance-list" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Employee Name</th>
                                                    <th>Record Date</th>
                                                    <th>In Time</th>
                                                    <th>Out Time</th>
                                                    <th>Location </th>
                                                    <th>Hours </th>
                                                    <th>Details </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    $i=1;
                                                    foreach($attendance_details_array as $attendance_record)
                                                    {

                                                    $id  = $attendance_record['ID'];
                                                    $AttendanceEmployeeID  = $attendance_record['EmployeeID']; 
                                                ?>
                                                <tr>
                                                    <td><?php echo $i; ?></td>

                                                    <td><?php 
                                                        if($attendance_record['EmployeeID'] == -1) {
                                                            echo "Not Set" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                        $employee_details = $employee_array_key[$attendance_record['EmployeeID']];
                                                            echo $employee_details['Name'];
                                                        } 
                                                    ?></td>


                                                    <td><?php
                                                      if($attendance_record['RecordDate'] == '') {
                                                            echo "NA" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                            echo $attendance_record['RecordDate'];
                                                        }
                                                     ?></td>
                                                    <td><?php
                                                      if($attendance_record['InTime'] == '') {
                                                            echo "NA" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                            echo $attendance_record['InTime'];
                                                        }
                                                     ?></td>
                                                    <td><?php
                                                       if($attendance_record['OutTime'] == '') {
                                                            echo "NA" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                            echo $attendance_record['OutTime'];
                                                        }
                                                     ?></td>
                                                    <td><?php
                                                        if($attendance_record['Location'] == '') {
                                                            echo "NA" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                            $attendance_record['Location'];
                                                        }
                                                    ?></td>
                                                    <td><?php
                                                    if($attendance_record['Hours'] == '') {
                                                            echo "NA" ;
                                                        }
                                                        
                                                        else
                                                        {
                                                            $attendance_record['Hours'];
                                                        }
                                                    ?></td>
                                                    <td>
                                                        <a onclick="ViewEmployeeDetail(<?php echo $AttendanceEmployeeID; ?>)">
                                                            <span class="badge badge-primary cursor-pointer">Employee Detail</span>
                                                        </a>
                                                    </td>
                                                      
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
    <script src="../js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
    <script src="../js/modules/attendance-list.js"></script>

</body>


</html>