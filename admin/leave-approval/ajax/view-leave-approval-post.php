<?php
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$empty_response = array(
    'draw' => $draw,
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array(),
);

try {
    require_once('../../includes/autoloader.inc.php');
    require_once('../../controllers/common_controllers.php');
    require_once('../../attendance-list/controller/attendance_controller.php');
    require_once('../../employees/controller/employee_controller.php');

    @session_start();

    if (!isset($_SESSION['pb_username'])) {
        sendLeaveDatatableJson($empty_response);
    }

    $conn = _connectodb();
    $roles = $_SESSION['Roles'] ?? array();
    $supervisor_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
    $supervised_ids = getSupervisedEmployeeIdsForLeave($conn, $supervisor_employee_id);
    if (!$conn || $supervisor_employee_id <= 0 || empty($supervised_ids)) {
        sendLeaveDatatableJson($empty_response);
    }

    $row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
    $rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
    if ($rowperpage < 0) {
        $rowperpage = 10;
    }

    $searchValue = '';
    if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
        $searchValue = $_POST['search']['value'];
    }

    $filters = array(
        'filter_date' => isset($_GET['filter_date']) ? $_GET['filter_date'] : '',
        'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
        'status' => isset($_GET['status']) ? $_GET['status'] : -1,
        'state' => isset($_GET['state']) ? $_GET['state'] : -1,
        'department' => isset($_GET['department']) ? $_GET['department'] : -1,
        'designation' => isset($_GET['designation']) ? $_GET['designation'] : -1,
        'leave_type' => isset($_GET['leave_type']) ? $_GET['leave_type'] : -1,
        'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
        'search' => $searchValue,
    );

    $employee_ids = leaveParseMultiFilterValues($filters['employee_id']);
    foreach ($employee_ids as $employee_id) {
        if (!in_array((int) $employee_id, $supervised_ids, true)) {
            sendLeaveDatatableJson($empty_response);
        }
    }

    $where = buildLeaveListFilterSql($conn, $filters, array(
        'scope' => 'supervisor',
        'supervisor_employee_id' => $supervisor_employee_id,
    ));
    $join = buildLeaveListJoinSql();
    $sql_parts = getLeaveApprovalSqlParts($conn);

    $sql_count = 'SELECT COUNT(*) AS row_count ' . $join . $where;
    $result_count = mysqli_query($conn, $sql_count);
    if (!$result_count) {
        sendLeaveDatatableJson($empty_response);
    }
    $row_count = (int) ($result_count->fetch_assoc()['row_count'] ?? 0);

    $sql = 'SELECT el.*, e.Name AS EmployeeName, e.EmployeeNumber, e.State, e.Department, e.Designation
            ' . $sql_parts['supervisor_select'] . '
            ' . $sql_parts['hr_select'] . '
            ' . $join . $sql_parts['supervisor_join'] . $sql_parts['hr_join'] . $where . '
            ORDER BY el.ID DESC
            LIMIT ' . $row . ',' . $rowperpage;

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        sendLeaveDatatableJson($empty_response);
    }

    $data = array();
    while ($record = $result->fetch_assoc()) {
        $action = 'N.A.';
        $select = '';
        if (canSupervisorApproveLeave($conn, (int) $record['ID'], $supervisor_employee_id)) {
            $action = "<a href='javascript:void(0);' onclick='openLeaveSupervisorAction(" . (int) $record['ID'] . ")'><span class='badge badge-primary cursor-pointer'>Take Action</span></a>";
            $select = "<input type='checkbox' class='leave-supervisor-select' value='" . (int) $record['ID'] . "'>";
        }

        $requested_on = trim((string) ($record['CreatedDate'] ?? '') . ' ' . (string) ($record['CreatedTime'] ?? ''));
        $department = trim((string) ($record['Department'] ?? ''));

        $data[] = array(
            'Select' => $select,
            'Employee' => htmlspecialchars($record['EmployeeName'] ?? '') . ' (' . (int) $record['EmployeeID'] . ')',
            'TypeOfLeave' => htmlspecialchars($record['TypeOfLeave'] ?? ''),
            'Reason' => htmlspecialchars($record['ReasonOfLeave'] ?? ''),
            'FromDate' => htmlspecialchars($record['FromDate'] ?? ''),
            'ToDate' => htmlspecialchars($record['ToDate'] ?? ''),
            'Duration' => htmlspecialchars($record['Duration'] ?? ''),
            'RequestedOn' => htmlspecialchars($requested_on),
            'State' => htmlspecialchars($record['State'] ?? ''),
            'Department' => htmlspecialchars($department !== '' ? $department : '-'),
            'Status' => buildEmployeeLeaveStatusBadge($record['Status'] ?? ''),
            'ApprovalInfo' => buildLeaveApprovalInfoHtml($record),
            'Action' => $action,
        );
    }

    sendLeaveDatatableJson(array(
        'draw' => $draw,
        'iTotalRecords' => $row_count,
        'iTotalDisplayRecords' => $row_count,
        'aaData' => $data,
    ));
} catch (Exception $e) {
    error_log('Supervisor leave datatable exception: ' . $e->getMessage());
    if (function_exists('sendLeaveDatatableJson')) {
        sendLeaveDatatableJson($empty_response);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($empty_response);
    exit;
}
