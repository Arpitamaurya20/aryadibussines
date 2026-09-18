<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
require_once('../controller/convenience_controller.php');

$conn = _connectodb();
setTimeZone();

$filters = array(
    'filter_date' => isset($_POST['filter_date_export']) ? $_POST['filter_date_export'] : '',
    'employee_id' => isset($_POST['employee_filter_export']) ? $_POST['employee_filter_export'] : -1,
    'status' => isset($_POST['status_export']) && $_POST['status_export'] !== '' && $_POST['status_export'] !== '-1' ? $_POST['status_export'] : '',
    'state' => isset($_POST['state_export']) ? $_POST['state_export'] : -1,
    'department' => isset($_POST['department_export']) ? $_POST['department_export'] : -1,
    'designation' => isset($_POST['designation_export']) ? $_POST['designation_export'] : -1,
    'ticket_id' => isset($_POST['ticket_export']) ? $_POST['ticket_export'] : -1,
    'employee_number' => isset($_POST['employee_number_export']) ? $_POST['employee_number_export'] : '',
);

$scope = isset($_POST['scope_export']) ? $_POST['scope_export'] : 'admin';
$options = array('scope' => 'admin');
if ($scope === 'supervisor' && isset($_POST['Supervisor_EmployeeID'])) {
    $options = array(
        'scope' => 'supervisor',
        'supervisor_employee_id' => (int) $_POST['Supervisor_EmployeeID'],
    );
}

$where = buildConvenienceListFilterSql($conn, $filters, $options);
$join = buildConvenienceListJoinSql();
$sql = 'SELECT a.*, e.Name AS EmployeeName, e.EmployeeNumber, e.State, e.Department, e.Designation,
        ct.TicketID AS TicketCode
        ' . $join . $where . ' ORDER BY a.ID DESC';

$rows = _getSQLRecords($conn, $sql);
$output = '';
if (is_array($rows) && count($rows) > 0) {
    $output .= '<table class="table" border="1"><tr>
        <th>Employee</th><th>Employee ID</th><th>Ticket</th><th>From</th><th>To</th>
        <th>Amount</th><th>Date</th><th>State</th><th>Department</th><th>Designation</th><th>Status</th><th>Remarks</th>
    </tr>';
    foreach ($rows as $ec) {
        $status_label = getConvenienceStatusLabel($ec['Status'] ?? '');
        $ticket_label = ((int) ($ec['TicketID'] ?? -1) > 0 && !empty($ec['TicketCode'])) ? $ec['TicketCode'] : '-';
        $output .= '<tr>
            <td>' . htmlspecialchars($ec['EmployeeName'] ?? '') . '</td>
            <td>' . htmlspecialchars($ec['EmployeeID'] ?? '') . '</td>
            <td>' . htmlspecialchars($ticket_label) . '</td>
            <td>' . htmlspecialchars($ec['ConvenienceFrom'] ?? '') . '</td>
            <td>' . htmlspecialchars($ec['ConvenienceTo'] ?? '') . '</td>
            <td>' . htmlspecialchars($ec['ConvenienceAmount'] ?? '') . '</td>
            <td>' . htmlspecialchars($ec['ConvenienceDate'] ?? '') . '</td>
            <td>' . htmlspecialchars($ec['State'] ?? '') . '</td>
            <td>' . htmlspecialchars(trim((string) ($ec['Department'] ?? '')) !== '' ? $ec['Department'] : '-') . '</td>
            <td>' . htmlspecialchars($ec['Designation'] ?? '') . '</td>
            <td>' . htmlspecialchars($status_label) . '</td>
            <td>' . htmlspecialchars($ec['Reference'] ?? '') . '</td>
        </tr>';
    }
    $output .= '</table>';
} else {
    $output = '<table><tr><td>No Data Available</td></tr></table>';
}

$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
