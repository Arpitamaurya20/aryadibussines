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

$conn = _connectodb();
$assetId = isset($_POST['asset_id']) ? (int) $_POST['asset_id'] : 0;
$employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$holdStatus = isset($_POST['hold_status']) ? trim((string) $_POST['hold_status']) : '';
$remarks = isset($_POST['remarks']) ? trim((string) $_POST['remarks']) : '';
$updatedBy = isset($_SESSION['pb_username']) ? (string) $_SESSION['pb_username'] : '';

$result = updateEmployeeCompanyAssetHoldStatus($conn, $assetId, $employeeId, $holdStatus, $remarks, $updatedBy);
echo json_encode($result);
