<?php session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    ini_set('error_log', '/error_log');
    error_reporting(E_ALL);

    $UserType="";
    include('../controllers/common_controllers.php');
    include('controller/attendance_controller.php');
    include('../employees/controller/employee_controller.php');
    include('../includes/autoloader.inc.php');
    setNavigation($_SESSION['Roles']);

    $core = new Core();
    $core->setTimeZone();
    $conn = _connectodb();

    $currentYear = date('Y');
    $currentMonth = date('m');
    $current_date = date("Y-m-d");
    $previous_date = date('Y-m-d', strtotime('-7 days'));
    $date_range = "$previous_date - $current_date";

    // Fetch Employees
    $employees = [];
    $sql_emps = "SELECT ID, Name FROM employees WHERE IsActive = 1 AND Vendor = 0 ORDER BY Name ASC";
    $res_emps = mysqli_query($conn, $sql_emps);
    while ($row = mysqli_fetch_assoc($res_emps)) {
        $employees[] = $row;
    }
    $employeeIDs = array_column($employees, 'ID');

    // Fetch Calendar
    $calendar = [];
    $sql_calendar = "SELECT `date`, `is_weekend` FROM `calendar` WHERE `year`='$currentYear' AND `month`='$currentMonth'";
    $res_cal = mysqli_query($conn, $sql_calendar);
    while ($row = mysqli_fetch_assoc($res_cal)) {
        $calendar[] = $row;
    }
    $calendarDates = array_column($calendar, 'date');

    // Fetch Attendance
    $dates_str = "'" . implode("','", $calendarDates) . "'";
    $emp_id_str = implode(',', $employeeIDs);
    $sql_att = "SELECT * FROM employee_attendance WHERE RecordDate IN ($dates_str) AND EmployeeID IN ($emp_id_str)";
    $res_att = mysqli_query($conn, $sql_att);
    $att_data = [];
    while ($row = mysqli_fetch_assoc($res_att)) {
        $att_data[$row['EmployeeID']][$row['RecordDate']] = $row;
    }
    function AddPlayTime($arr){
        $total = 0;
        foreach ($arr as $time) {
            list($h, $m) = explode(':', $time);
            $total += $h * 60 + $m;
        }
        $hr = floor($total / 60);
        $min = $total % 60;
        return sprintf('%02d:%02d', $hr, $min);
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Employees Attendance Records</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">
    <link rel="stylesheet" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        .select2-container { z-index: 1; }
        .modal-image { width: 400px; height: 400px; object-fit: cover; }
        #preloader{display:none;}
        .table-bordered thead td, .table-bordered thead th{
		    min-width:60px;
	    }
        .table thead{
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .table thead .fname{
            position: sticky;
            left: 0;
            z-index: 2;
            background:#184384;
        }
        .table tbody .fname{
            position: sticky;
            left: 0;
            z-index: 1;
            background:#184384;
            color:#fff;
        }
        #view-attendance-records table th, .table td{
            padding:5px;
        }
    </style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
<?php include('../js/theme_settings.js'); ?>
<div class="page-wrapper">
    <div class="page-inner">
        <?php include('../navigation/admin_navigation.php'); ?>
        <div class="page-content-wrapper">
            <?php include('../includes/common_header.php'); ?>
            <main id="js-page-content" role="main" class="page-content">
                <ol class="breadcrumb page-breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                    <li class="breadcrumb-item active">Attendance Details</li>
                </ol>
                <div class="panel mb-2">
                    <div class="panel-content p-3">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="filter_date" value="<?= $date_range ?>">
                            </div>
                            <div class="col-md-3">
                                <button onclick="RefreshAttendance();" class="btn btn-sm btn-primary ml-3">Search</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="panel">
                            <div class="panel-hdr">
                                <h2><span>Attendance Details</span></h2>
                            </div>
                            <div class="panel-container show">
                                <div class="table-responsive" style="max-height: 650px;">
                                    <table id="view-attendance-records" class="table table-bordered table-hover w-100" style="font-size:12px;">
                                        <thead>
                                            <tr style="color:#fff; background:#184384; text-align:center;">
                                                <th rowspan="2">S.N.</th>
                                                <th class="fname" rowspan="2">Employee Name</th>
                                                <?php foreach ($calendar as $value): ?>
                                                    <?php $dd = date("d-m-Y", strtotime($value['date'])); $wd = date("l", strtotime($value['date'])); ?>
                                                    <th colspan="6"><?= "$dd ($wd)" ?></th>
                                                <?php endforeach; ?>
                                                <th rowspan="2">No. of Present</th>
                                                <th rowspan="2">No. of Absent</th>
                                                <th rowspan="2">No. of HD+HDO</th>
                                                <th rowspan="2">Total Half Day</th>
                                                <th rowspan="2">Total Work Hours</th>
                                                <th rowspan="2">Short In Hours</th>
                                                <th rowspan="2">OT In Hours</th>
                                                <th rowspan="2">Final OT</th>
                                                <th rowspan="2">Total Leaves</th>
                                                <th rowspan="2">Total Worked Day</th>
                                            </tr>
                                            <tr style="color:#fff; background:#2866c3; text-align:center;">
                                                <?php foreach ($calendar as $_): ?>
                                                    <th>IN</th><th>OUT</th><th>TWH</th><th>OT</th><th>ST</th><th>STATUS</th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                                $count = 1;
                                                foreach($employees as $emp): 
                                                if(!empty($emp['Name'])){
                                                    $emp_id = $emp['ID'];
                                                    $name = $emp['Name'];
                                                    $present = $absent = $half = 0;
                                                    $wh_arr = $ot_arr = $st_arr = array();
                                                    
                                            ?>
                                                <tr style="text-align:center;">
                                                    <td class='text-center' style="background:#184384; color:#fff;"><?= $count++ ?></td>
                                                    <td class='fname text-center' style="background:#184384; color:#fff;"><?= $name ?></td>
                                                        <?php foreach($calendar as $cal): 
                                                            $date = $cal['date'];
                                                            $rec = $att_data[$emp['ID']][$date] ?? null;
                                                            $inTime = $rec['InTime'] ?? null;
                                                            $outTime = $rec['OutTime'] ?? null;
                                                            $work_status="";


                                                            if(!empty($inTime) && !empty($outTime)){
                                                                $totalhours=$totalminutes=0;
                                                                $minus=0; $break='';
                                                                $st_stamp=strtotime($inTime);
                                                                $end_stamp=strtotime($outTime);
                                                                $dif0=$end_stamp-$st_stamp;
                                                                $diff=$dif0-$minus;
                                                                if(!$break==''){$break=' - '.$break;}
                                                                $hours=gmdate("H:i", $diff).$break;
                                                                $timearr=explode(':', $hours);
                                                                $totalhours+=$timearr[0];
                                                                $totalminutes+=$timearr[1];
                                                                if($totalminutes>=60){
                                                                    $minutestohours=$totalminutes/60;
                                                                    $newmin=fmod($totalminutes,60);
                                                                    $finalhours=floor($totalhours+$minutestohours);
                                                                    if(strlen($finalhours)==1){$finalhours='0'.$finalhours;}
                                                                    if(strlen($newmin)==1){$newmin='0'.$newmin;}  	
                                                                    $finaltime=$finalhours.':'.$newmin;
                                                                }else{
                                                                    if(strlen($totalhours)==1){$totalhours='0'.$totalhours;}
                                                                    if(strlen($totalminutes)==1){$totalminutes='0'.$totalminutes;}
                                                                    $finaltime=$totalhours.':'.$totalminutes;
                                                                }
                                                                if($finaltime < "02:00"){
                                                                    $work_status = "A";
                                                                    $absent = $absent+1;
                                                                    $othource = $finaltime;
                                                                    $shorthource = "00:00";
                                                                }elseif($finaltime < "04:00"){
                                                                    $fixdtime = "04:00";
                                                                    $st1 = strtotime($fixdtime);
                                                                    $st2 = strtotime($finaltime);
                                                                    $mtxx1 = $st1 - $st2;
                                                                    $otxx2 = gmdate("H:i", $mtxx1);
                                                                    $work_status = "HD";
                                                                    $half=$half+1;
                                                                    $shorthource = $otxx2;
                                                                    $othource = "00:00";
                                                                }elseif($finaltime < "07:30"){
                                                                    $fixdtime = "04:00";
                                                                    $st1 = strtotime($fixdtime);
                                                                    $st2 = strtotime($finaltime);
                                                                    $mtxx1 = $st2 - $st1;
                                                                    $otxx2 = gmdate("H:i", $mtxx1);
                                                                    $work_status = "HDO";
                                                                    $half=$half+1;
                                                                    $othource = $otxx2;
                                                                    $shorthource = "00:00";
                                                                }else{
                                                                    $work_status = "P";
                                                                    $present=$present+1;
                                                                    $fixdtime = "09:00";
                                                                    $st1 = strtotime($fixdtime);
                                                                    $st2 = strtotime($finaltime);
                                                                    if($finaltime < $fixdtime){
                                                                    $mtxx1 = $st1 - $st2;
                                                                    $otxx2 = gmdate("H:i", $mtxx1);
                                                                    $shorthource = $otxx2;
                                                                    $othource = "00:00";
                                                                    }else{
                                                                    $mtxx1 = $st2 - $st1;
                                                                    $otxx2 = gmdate("H:i", $mtxx1);
                                                                    $othource = $otxx2;
                                                                    $shorthource = "00:00";	
                                                                    }	
                                                                }
                                                                if($cal['is_weekend']=="1"){
                                                                    $absent = $present+1;
                                                                }
                                                            }else{
                                                                $work_status = "A";
                                                                if(date("Y-m-d") >= $date){
                                                                    if($cal['is_weekend']=="1"){
                                                                        $present = $present+1;
                                                                    }else{
                                                                        $absent = $absent+1; 
                                                                    }
                                                                }
                                                            }

                                                            if(!empty($finaltime)){ $wh_arr[] = $finaltime; }
                                                            if(!empty($othource)){ $ot_arr[] = $othource; }
                                                            if(!empty($shorthource)){ $st_arr[] = $shorthource; }

                                                            if($work_status == "A"){ $color = "#f1a4a4"; }elseif($work_status == "P"){ $color = "#c0fbc0"; }elseif($work_status == "NA"){ $color = "#c0fbc0"; }else{ $color = "#f3e47d"; }

                                                            if($cal['is_weekend']=="1"){
                                                                if(!empty($inTime) && !empty($outTime)){
                                                            ?>
                                                                <td class='text-center'><?php if(!empty($inTime)){ echo $inTime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($outTime)){ echo $outTime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($finaltime)){ echo $finaltime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($othource)){ echo $othource; } ?></td>
                                                                <td class='text-center'><?php if(!empty($shorthource)){ echo $shorthource; } ?></td>
                                                                <td class='text-center' style="background:<?php echo $color; ?>"><?php if(!empty($work_status)){ echo $work_status; } ?></td>
                                                            <?php
                                                                }else{
                                                            ?>
                                                                <td class='text-center'>H</td>
                                                                <td class='text-center'>H</td>
                                                                <td class='text-center'>H</td>
                                                                <td class='text-center'>H</td>
                                                                <td class='text-center'>H</td>
                                                                <td class='text-center' style="background:#c0fbc0;">P</td>
                                                            <?php
                                                                }
                                                            }else{
                                                                if(!empty($inTime) && !empty($outTime)){
                                                            ?>
                                                                <td class='text-center'><?php if(!empty($inTime)){ echo $inTime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($outTime)){ echo $outTime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($finaltime)){ echo $finaltime; } ?></td>
                                                                <td class='text-center'><?php if(!empty($othource)){ echo $othource; } ?></td>
                                                                <td class='text-center'><?php if(!empty($shorthource)){ echo $shorthource; } ?></td>
                                                                <td class='text-center' style="background:<?php echo $color; ?>"><?php if(!empty($work_status)){ echo $work_status; } ?></td>
                                                                <?php }else{ ?>
                                                                <?php if(!empty($inTime)){ ?>
                                                                <td class='text-center'><?php if(!empty($inTime)){ echo $inTime; } ?></td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center' style="background:<?php echo $color; ?>">A</td>
                                                                <?php }else{ ?>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center'>-</td>
                                                                <td class='text-center' style="background:<?php echo $color; ?>">A</td>
                                                            <?php 
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        <?php endforeach; ?>
                                                        <?php
                                                            $half_days = $half / 2;
                                                            $total_leaves = $absent + $half_days;
                                                            $worked_days = count($calendar) - $total_leaves;
                                                        ?>
                                                    <td><?php echo $present; ?></td>
                                                    <td><?php echo $absent; ?></td>
                                                    <td><?php echo $half; ?></td>
                                                    <td><?php echo $half_days; ?></td>
                                                    <td><?PHP echo AddPlayTime($wh_arr); ?></td>
                                                    <td><?php echo AddPlayTime($ot_arr); ?></td>
                                                    <td><?php echo AddPlayTime($st_arr); ?></td>
                                                    <td>00</td>
                                                    <td><?php echo $total_leaves; ?></td>
                                                    <td><?php echo $worked_days ?></td>
                                                </tr>
                                            <?php } ?>
                                            <?php endforeach; ?> 
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
            <?php include('../includes/common_footer.php'); ?>
        </div>
    </div>
</div>
<?php include('../includes/common_modules.php'); ?>
<script src="../js/datagrid/datatables/datatables.bundle.js"></script>
<script src="../js/dependency/moment/moment.js"></script>
<script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
<script src="../js/modules/attendance-list.js"></script>
<script src="../js/modules/employee.js"></script>
</body>
</html>
