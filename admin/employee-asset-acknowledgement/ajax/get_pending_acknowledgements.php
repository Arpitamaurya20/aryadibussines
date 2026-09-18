<?php
require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../controller/employee_asset_acknowledgement_controller.php');

@session_start();
header('Content-Type: application/json; charset=utf-8');

$response = array('error' => true, 'message' => 'Unauthorized', 'assets' => array(), 'employee' => null, 'has_pending' => false);

if (!isset($_SESSION['pb_username'])) {
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$roles = $_SESSION['Roles'] ?? array();
$employeeId = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : 0;

if ($employeeId <= 0) {
    $response['error'] = false;
    $response['message'] = 'No employee profile';
    $response['has_pending'] = false;
    echo json_encode($response);
    exit;
}

$employee = getEmployeeAssetAckEmployee($conn, $employeeId);
$pendingAssets = getPendingEmployeeCompanyAssets($conn, $employeeId);

$response['error'] = false;
$response['message'] = 'OK';
$response['has_pending'] = !empty($pendingAssets);
$response['employee'] = $employee ? array(
    'id' => (int) $employee['ID'],
    'name' => $employee['Name'],
) : null;
$response['assets'] = array_map(function ($row) {
    return array(
        'id' => (int) $row['ID'],
        'category' => $row['AssetCategory'],
        'description' => $row['AssetDescription'],
        'serial' => $row['AssetSerialNumber'],
        'allocation_date' => $row['AllocationDate'],
    );
}, $pendingAssets);

echo json_encode($response);
