<?php
ob_start();
require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../../attendance-list/controller/attendance_controller.php');
ini_set('display_errors', '0');
error_reporting(0);

@session_start();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$empty_response = array(
    'draw' => $draw,
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array()
);

if (!isset($_SESSION['pb_username'])) {
    sendAttendanceDatatableJson($empty_response);
}

$roles = isset($_SESSION['Roles']) ? $_SESSION['Roles'] : array();
if (!hasHrAttendanceApprovalAccess($roles)) {
    sendAttendanceDatatableJson($empty_response);
}

$conn = _connectodb();
if (!$conn) {
    sendAttendanceDatatableJson($empty_response);
}

$employee_obj = new Employee($conn);
$sql_parts = getAttendanceApprovalSqlParts($conn);

$row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$searchValue = '';
if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
    $searchValue = $_POST['search']['value'];
}

$default_status = 'hr_actionable';
$status_raw = isset($_GET['ApprovalStatus']) ? trim((string) $_GET['ApprovalStatus']) : $default_status;
if ($status_raw === 'hr_actionable' && !function_exists('attendanceBuildApprovalStatusFilterSql')) {
    $status_raw = 'SupervisorApproved';
}

$filters = array(
    'filter_date' => isset($_GET['filter_date']) ? $_GET['filter_date'] : '',
    'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
    'approval_status' => $status_raw,
    'state' => isset($_GET['state']) ? $_GET['state'] : -1,
    'department' => isset($_GET['department']) ? $_GET['department'] : -1,
    'designation' => isset($_GET['designation']) ? $_GET['designation'] : -1,
    'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
    'search' => $searchValue,
);

if ($status_raw === '-1' || $status_raw === '') {
    unset($filters['approval_status']);
}

$where = buildAttendanceListFilterSql($conn, $filters, array('scope' => 'admin'));

$from_join = " FROM `employee_attendance` a
        LEFT JOIN employees b ON a.EmployeeID = b.ID
        " . $sql_parts['supervisor_join'] . "
        " . $sql_parts['hr_join'] . " ";

$sql_count = "SELECT COUNT(*) as row_count " . $from_join . $where;
$result_Count = mysqli_query($conn, $sql_count);
if (!$result_Count) {
    sendAttendanceDatatableJson($empty_response);
}
$row_count = (int) ($result_Count->fetch_assoc()['row_count'] ?? 0);

$sql = "SELECT b.Name, b.State, b.Department, b.Designation, b.EmployeeNumber,
        " . $sql_parts['supervisor_select'] . "
        " . $sql_parts['hr_select'] . "
        a.*
        " . $from_join . $where . getAttendanceApprovalOrderSql() . "
        LIMIT " . $row . "," . $rowperpage;

$result = mysqli_query($conn, $sql);
if (!$result) {
    sendAttendanceDatatableJson($empty_response);
}

$data = array();
while ($record = $result->fetch_assoc()) {
    $data[] = formatAttendanceApprovalDatatableRow($conn, $record, $employee_obj, array(
        'scope' => 'hr',
        'roles' => $roles,
    ));
}

sendAttendanceDatatableJson(array(
    'draw' => $draw,
    'iTotalRecords' => $row_count,
    'iTotalDisplayRecords' => $row_count,
    'aaData' => $data
));
