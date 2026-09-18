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
    require_once('../../employees-convenience/controller/convenience_controller.php');

    @session_start();

    if (!isset($_SESSION['pb_username'])) {
        sendConvenienceDatatableJson($empty_response);
    }

    $conn = _connectodb();
    $roles = $_SESSION['Roles'] ?? array();
    if (!$conn || !hasHrConvenienceApprovalAccess($roles)) {
        sendConvenienceDatatableJson($empty_response);
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

    $default_status = 'hr_actionable';
    $status_raw = isset($_GET['status']) ? trim((string) $_GET['status']) : $default_status;
    if ($status_raw === 'hr_actionable' && !function_exists('convenienceBuildStatusFilterSql')) {
        $status_raw = convenienceStatusSupervisorApproved();
    }

    $filters = array(
        'filter_date' => isset($_GET['filter_date']) ? $_GET['filter_date'] : '',
        'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
        'status' => $status_raw,
        'state' => isset($_GET['state']) ? $_GET['state'] : -1,
        'department' => isset($_GET['department']) ? $_GET['department'] : -1,
        'designation' => isset($_GET['designation']) ? $_GET['designation'] : -1,
        'ticket_id' => isset($_GET['ticket_id']) ? $_GET['ticket_id'] : -1,
        'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
        'search' => $searchValue,
    );

    if ($status_raw === '-1' || $status_raw === '') {
        unset($filters['status']);
    }

    $where = buildConvenienceListFilterSql($conn, $filters, array('scope' => 'admin'));
    $join = buildConvenienceListJoinSql();
    $sql_parts = getConvenienceApprovalSqlParts($conn);

    $sql_count = 'SELECT COUNT(*) AS row_count ' . $join . $where;
    $result_count = mysqli_query($conn, $sql_count);
    if (!$result_count) {
        convenienceLogDatatableSqlError('HR convenience count query failed', $conn, $sql_count);
        sendConvenienceDatatableJson($empty_response);
    }
    $row_count = (int) ($result_count->fetch_assoc()['row_count'] ?? 0);

    $sql = 'SELECT a.*, e.Name AS EmployeeName, e.EmployeeNumber, e.State, e.Department, e.Designation,
            ct.TicketID AS TicketCode
            ' . $sql_parts['supervisor_select'] . '
            ' . $sql_parts['hr_select'] . '
            ' . $sql_parts['finance_select'] . '
            ' . $join . $sql_parts['supervisor_join'] . $sql_parts['hr_join'] . $sql_parts['finance_join'] . $where . '
            ORDER BY a.ID DESC
            LIMIT ' . $row . ',' . $rowperpage;

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        convenienceLogDatatableSqlError('HR convenience list query failed', $conn, $sql);
        sendConvenienceDatatableJson($empty_response);
    }

    $data = array();
    while ($record = $result->fetch_assoc()) {
        $action = 'N.A.';
        $select = '';
        if (canHrApproveConvenience($conn, (int) $record['ID'], $roles)) {
            $action_label = convenienceIsSupervisorActionExpired($record) ? 'HR Action (Timeout)' : 'HR Action';
            $action = "<a href='javascript:void(0);' onclick='openConvenienceHrAction(" . (int) $record['ID'] . ", " . json_encode($record['ConvenienceAmount']) . ")'><span class='badge badge-primary cursor-pointer'>" . htmlspecialchars($action_label) . "</span></a>";
            $select = "<input type='checkbox' class='convenience-hr-select' value='" . (int) $record['ID'] . "' data-amount='" . htmlspecialchars((string) ($record['ConvenienceAmount'] ?? ''), ENT_QUOTES) . "'>";
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
            'Amount' => 'Rs. ' . htmlspecialchars($record['ConvenienceAmount'] ?? ''),
            'Remarks' => htmlspecialchars($record['Reference'] ?? ''),
            'RecordDate' => htmlspecialchars($record['ConvenienceDate'] ?? ''),
            'State' => htmlspecialchars($record['State'] ?? ''),
            'Department' => htmlspecialchars($department !== '' ? $department : '-'),
            'Designation' => htmlspecialchars($record['Designation'] ?? ''),
            'Status' => buildConvenienceStatusBadgeForRecord($record),
            'PaymentStatus' => buildConveniencePaymentStatusBadge($record),
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
} catch (Exception $e) {
    error_log('HR convenience datatable exception: ' . $e->getMessage());
    if (function_exists('sendConvenienceDatatableJson')) {
        sendConvenienceDatatableJson($empty_response);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($empty_response);
    exit;
}
