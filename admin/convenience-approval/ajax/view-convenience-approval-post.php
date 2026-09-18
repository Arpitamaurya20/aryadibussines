<?php
ob_start();
require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../../attendance-list/controller/attendance_controller.php');
require_once('../../employees-convenience/controller/convenience_controller.php');
ini_set('display_errors', '0');
error_reporting(0);

@session_start();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$empty_response = array(
    'draw' => $draw,
    'iTotalRecords' => 0,
    'iTotalDisplayRecords' => 0,
    'aaData' => array(),
);

if (!isset($_SESSION['pb_username'])) {
    sendConvenienceDatatableJson($empty_response);
}

$conn = _connectodb();
if (!$conn) {
    sendConvenienceDatatableJson($empty_response);
}

$roles = $_SESSION['Roles'] ?? array();
$supervisor_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
$supervised_ids = getConvenienceTeamEmployeeIds($conn, $supervisor_employee_id, false);
if (empty($supervised_ids)) {
    sendConvenienceDatatableJson($empty_response);
}

$row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
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
    'ticket_id' => isset($_GET['ticket_id']) ? $_GET['ticket_id'] : -1,
    'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
    'search' => $searchValue,
);

$employee_ids = convenienceParseMultiFilterValues($filters['employee_id']);
foreach ($employee_ids as $employee_id) {
    if (!in_array((int) $employee_id, $supervised_ids, true)) {
        sendConvenienceDatatableJson($empty_response);
    }
}

$where = buildConvenienceListFilterSql($conn, $filters, array(
    'scope' => 'supervisor',
    'supervisor_employee_id' => $supervisor_employee_id,
));
$join = buildConvenienceListJoinSql();
$sql_parts = getConvenienceApprovalSqlParts($conn);

$sql_count = 'SELECT COUNT(*) AS row_count ' . $join . $where;
$result_count = mysqli_query($conn, $sql_count);
if (!$result_count) {
    sendConvenienceDatatableJson($empty_response);
}
$row_count = (int) ($result_count->fetch_assoc()['row_count'] ?? 0);

$sql = 'SELECT a.*, e.Name AS EmployeeName, e.EmployeeNumber, e.State, e.Department, e.Designation,
        ct.TicketID AS TicketCode
        ' . $sql_parts['supervisor_select'] . '
        ' . $sql_parts['hr_select'] . '
        ' . $join . $sql_parts['supervisor_join'] . $sql_parts['hr_join'] . $where . '
        ORDER BY a.ID DESC
        LIMIT ' . $row . ',' . $rowperpage;

$result = mysqli_query($conn, $sql);
if (!$result) {
    sendConvenienceDatatableJson($empty_response);
}

$data = array();
while ($record = $result->fetch_assoc()) {
    $status = (string) ($record['Status'] ?? '');
    $action = 'N.A.';
    $select = '';
    if ($status === convenienceStatusPendingSupervisor() && canSupervisorApproveConvenience($conn, (int) $record['ID'], $supervisor_employee_id)) {
        $action = "<a href='javascript:void(0);' onclick='openConvenienceSupervisorAction(" . (int) $record['ID'] . ", " . json_encode($record['ConvenienceAmount']) . ")'><span class='badge badge-primary cursor-pointer'>Take Action</span></a>";
        $select = "<input type='checkbox' class='convenience-supervisor-select' value='" . (int) $record['ID'] . "'>";
    } elseif (convenienceIsSupervisorActionExpired($record)) {
        $action = '<span class="badge badge-secondary">Supervisor window expired (24h)</span>';
    }

    $journey_to = trim((string) ($record['ConvenienceTo'] ?? ''));
    if ($journey_to === '') {
        $journey_to = 'Not Set';
    }
    $ticket_label = ((int) ($record['TicketID'] ?? -1) > 0 && !empty($record['TicketCode'])) ? htmlspecialchars($record['TicketCode']) : '-';

    $department = trim((string) ($record['Department'] ?? ''));

    $data[] = array(
        'Select' => $select,
        'Employee' => htmlspecialchars($record['EmployeeName'] ?? '') . ' (' . (int) $record['EmployeeID'] . ')',
        'Ticket' => $ticket_label,
        'Journey' => htmlspecialchars($record['ConvenienceFrom'] ?? '') . '<br>' . htmlspecialchars($journey_to),
        'Amount' => '₹' . htmlspecialchars($record['ConvenienceAmount'] ?? ''),
        'Remarks' => htmlspecialchars($record['Reference'] ?? ''),
        'RecordDate' => htmlspecialchars($record['ConvenienceDate'] ?? ''),
        'State' => htmlspecialchars($record['State'] ?? ''),
        'Department' => htmlspecialchars($department !== '' ? $department : '-'),
        'Status' => buildConvenienceStatusBadgeForRecord($record),
        'ApprovalInfo' => buildConvenienceApprovalInfoHtml($record),
        'Action' => $action,
    );
}

sendConvenienceDatatableJson(array(
    'draw' => $draw,
    'iTotalRecords' => $row_count,
    'iTotalDisplayRecords' => $row_count,
    'aaData' => $data,
));
