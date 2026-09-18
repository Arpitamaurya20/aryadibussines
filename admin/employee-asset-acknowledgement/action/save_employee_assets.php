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
$employeeId = isset($_POST['employee_id']) ? (int) $_POST['employee_id'] : 0;
$assetsJson = isset($_POST['assets']) ? $_POST['assets'] : '[]';
$assets = json_decode($assetsJson, true);
if (!is_array($assets)) {
    $assets = array();
}

$createdBy = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : null;
$result = saveEmployeeCompanyAssets($conn, $employeeId, $assets, 'Admin', $createdBy);
echo json_encode($result);
