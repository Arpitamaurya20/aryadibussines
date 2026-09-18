<?php
require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../controller/employee_asset_acknowledgement_controller.php');

@session_start();
header('Content-Type: application/json; charset=utf-8');

$response = array('error' => true, 'message' => 'Unauthorized');

if (!isset($_SESSION['pb_username'])) {
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
if (!hasEmployeeAssetAckAdminAccess($roles)) {
    $response['message'] = 'You do not have access to manage employee assets.';
    echo json_encode($response);
    exit;
}

$employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
if ($employeeId <= 0) {
    echo json_encode(array('error' => true, 'message' => 'Employee is required.'));
    exit;
}

$conn = _connectodb();
$employee = getEmployeeAssetAckEmployee($conn, $employeeId);
if (!$employee) {
    echo json_encode(array('error' => true, 'message' => 'Employee not found.'));
    exit;
}

$url = buildEmployeeAssetAckPublicUrl($employeeId);
echo json_encode(array(
    'error' => false,
    'message' => 'Public link generated.',
    'url' => $url,
    'employee_name' => $employee['Name'],
));
