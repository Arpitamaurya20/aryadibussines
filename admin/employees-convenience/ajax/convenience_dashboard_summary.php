<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../controller/convenience_controller.php');

$response = array('error' => true, 'message' => 'Unauthorized', 'summary' => convenienceEmptyDashboardSummary());

if (!isset($_SESSION['pb_username'])) {
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$roles = $_SESSION['Roles'] ?? array();
$scope = isset($_GET['scope']) ? trim((string) $_GET['scope']) : 'admin';

$allowed = false;
if ($scope === 'supervisor') {
    $supervisor_employee_id = isset($_GET['Supervisor_EmployeeID']) ? (int) $_GET['Supervisor_EmployeeID'] : -1;
    $allowed = $supervisor_employee_id > 0 && employeeHasConvenienceTeam($conn, $supervisor_employee_id);
} elseif ($scope === 'hr') {
    $allowed = hasHrConvenienceApprovalAccess($roles);
} elseif ($scope === 'finance') {
    $allowed = hasFinanceConveniencePaymentAccess($roles);
} else {
    $allowed = true;
}

if (!$conn || !$allowed) {
    echo json_encode($response);
    exit;
}

$filters = array(
    'filter_date' => isset($_GET['filter_date']) ? $_GET['filter_date'] : '',
    'employee_id' => isset($_GET['EmployeeID']) ? $_GET['EmployeeID'] : -1,
    'state' => isset($_GET['state']) ? $_GET['state'] : -1,
    'department' => isset($_GET['department']) ? $_GET['department'] : -1,
    'designation' => isset($_GET['designation']) ? $_GET['designation'] : -1,
    'ticket_id' => isset($_GET['ticket_id']) ? $_GET['ticket_id'] : -1,
    'employee_number' => isset($_GET['employee_number']) ? $_GET['employee_number'] : '',
);

$options = array('scope' => $scope === 'supervisor' ? 'supervisor' : 'admin');
if ($scope === 'supervisor') {
    $options['supervisor_employee_id'] = isset($_GET['Supervisor_EmployeeID']) ? (int) $_GET['Supervisor_EmployeeID'] : -1;
}

$summary = getConvenienceDashboardSummary($conn, $filters, $options);

echo json_encode(array(
    'error' => false,
    'message' => 'OK',
    'summary' => $summary,
    'formatted' => array(
        'total_amount' => convenienceFormatAmountLabel($summary['total_amount']),
        'pending_supervisor_amount' => convenienceFormatAmountLabel($summary['pending_supervisor_amount']),
        'supervisor_timeout_amount' => convenienceFormatAmountLabel($summary['supervisor_timeout_amount']),
        'pending_hr_amount' => convenienceFormatAmountLabel($summary['pending_hr_amount']),
        'pending_payment_amount' => convenienceFormatAmountLabel($summary['pending_payment_amount']),
        'payment_done_amount' => convenienceFormatAmountLabel($summary['payment_done_amount']),
        'rejected_amount' => convenienceFormatAmountLabel($summary['rejected_amount']),
    ),
));
