<?php
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
require_once('../controller/attendance_controller.php');

@session_start();
$conn = _connectodb();
$employee_obj = new Employee($conn);
setTimeZone();

$output = "";

$filters = array(
    'filter_date' => isset($_POST['filter_date']) ? $_POST['filter_date'] : '',
    'employee_id' => isset($_POST['EmployeeID']) ? $_POST['EmployeeID'] : -1,
    'approval_status' => isset($_POST['ApprovalStatus']) ? $_POST['ApprovalStatus'] : -1,
    'state' => isset($_POST['state']) ? $_POST['state'] : -1,
    'department' => isset($_POST['department']) ? $_POST['department'] : -1,
    'designation' => isset($_POST['designation']) ? $_POST['designation'] : -1,
    'employee_number' => isset($_POST['employee_number']) ? $_POST['employee_number'] : '',
);

$status_raw = isset($filters['approval_status']) ? trim((string) $filters['approval_status']) : '-1';
if ($status_raw === '-1' || $status_raw === '') {
    unset($filters['approval_status']);
}

$where = buildAttendanceListFilterSql($conn, $filters, array('scope' => 'admin'));
$where .= ' AND b.IsActive = 1 AND b.Vendor = 0';

$sql = "SELECT b.Name, b.State, a.*
        FROM `employee_attendance` a
        LEFT JOIN employees b ON a.EmployeeID = b.ID" . $where . getAttendanceApprovalOrderSql();

$records = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($records, $row);
        }
    }
}

if (sizeof($records) > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Employee Name</th>
                         <th>State</th>                       
                         <th>Record Date</th>                       
                         <th>In Time</th>                       
                         <th>Out Time</th> 
                         <th>Duration</th>                       
                    </tr>
  ';
    foreach ($records as $record) {
        $duration = $employee_obj->calculatetimeDifference($record['InTime'], $record['OutTime']);
        $output .= '<tr>  
       <td>' . htmlspecialchars($record['Name']) . '</td>   
       <td>' . htmlspecialchars($record['State']) . '</td>   
       <td>' . htmlspecialchars($record['RecordDate']) . '</td>   
       <td>' . htmlspecialchars($record['InTime']) . '</td>   
       <td>' . htmlspecialchars($record['OutTime']) . '</td>  
       <td>' . htmlspecialchars($duration) . '</td>   
                    </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

$myfile = fopen("../report_attendance.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
echo $output;
