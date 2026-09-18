<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../../employees-convenience/controller/convenience_controller.php');

@session_start();
$core = new Core();
$core->SessionCheck();
$conn = _connectodb();

$draw = isset($_POST['draw']) ? (int) $_POST['draw'] : 0;
$row = isset($_POST['start']) ? (int) $_POST['start'] : 0;
$rowperpage = isset($_POST['length']) ? (int) $_POST['length'] : 10;
$searchValue = '';
if (isset($_POST['search']) && is_array($_POST['search']) && isset($_POST['search']['value'])) {
    $searchValue = $_POST['search']['value'];
}

$filters = array(
    'filter_date' => isset($_GET['filter_date']) ? $_GET['filter_date'] : '',
    'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
    'status' => isset($_GET['status']) && $_GET['status'] !== '-1' ? $_GET['status'] : '',
    'state' => isset($_GET['state']) ? $_GET['state'] : -1,
    'department' => isset($_GET['department']) ? $_GET['department'] : -1,
    'designation' => isset($_GET['designation']) ? $_GET['designation'] : -1,
    'ticket_id' => isset($_GET['ticket_id']) ? $_GET['ticket_id'] : -1,
    'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
    'search' => $searchValue,
);

$where = buildConvenienceListFilterSql($conn, $filters, array('scope' => 'admin'));
$join = buildConvenienceListJoinSql();
$sql_parts = getConvenienceApprovalSqlParts($conn);

$sql_count = 'SELECT COUNT(*) AS row_count ' . $join . $where;
$result_count = mysqli_query($conn, $sql_count);
$row_count = 0;
if ($result_count) {
    $row_count = (int) ($result_count->fetch_assoc()['row_count'] ?? 0);
}

$totalRecords = $core->_getTotalRows($conn, 'employee_convenience', ' where 1');

$sql = 'SELECT a.*, e.Name AS EmployeeName, e.EmployeeNumber, e.State, e.Department, e.Designation,
        ct.TicketID AS TicketCode
        ' . $sql_parts['supervisor_select'] . '
        ' . $sql_parts['hr_select'] . '
        ' . $sql_parts['finance_select'] . '
        ' . $join . $sql_parts['supervisor_join'] . $sql_parts['hr_join'] . $sql_parts['finance_join'] . $where . '
        ORDER BY a.ID DESC
        LIMIT ' . $row . ',' . $rowperpage;

$data = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($record = $result->fetch_assoc()) {
        $journey_to = trim((string) ($record['ConvenienceTo'] ?? ''));
        if ($journey_to === '') {
            $journey_to = 'Not Set';
        }
        $ticket_label = ((int) ($record['TicketID'] ?? -1) > 0 && !empty($record['TicketCode'])) ? htmlspecialchars($record['TicketCode']) : '-';
        $status = (string) ($record['Status'] ?? '');

        $data[] = array(
            'Employee' => htmlspecialchars($record['EmployeeName'] ?? ''),
            'EmployeeID' => (int) $record['EmployeeID'],
            'Ticket' => $ticket_label,
            'Journey' => htmlspecialchars($record['ConvenienceFrom'] ?? '') . '<br>' . htmlspecialchars($journey_to),
            'Amount' => '₹' . htmlspecialchars($record['ConvenienceAmount'] ?? ''),
            'Remarks' => htmlspecialchars($record['Reference'] ?? ''),
            'RecordDate' => htmlspecialchars($record['ConvenienceDate'] ?? ''),
            'State' => htmlspecialchars($record['State'] ?? ''),
            'Department' => htmlspecialchars($record['Department'] ?? ''),
            'Designation' => htmlspecialchars($record['Designation'] ?? ''),
            'Status' => buildConvenienceStatusBadgeForRecord($record),
            'PaymentStatus' => buildConveniencePaymentStatusBadge($record),
            'ApprovalInfo' => buildConvenienceApprovalInfoHtml($record),
        );
    }
}

echo json_encode(array(
    'draw' => $draw,
    'iTotalRecords' => $totalRecords,
    'iTotalDisplayRecords' => $row_count,
    'aaData' => $data,
));
