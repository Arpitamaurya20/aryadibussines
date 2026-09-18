<?php
require_once('../../includes/autoloader.inc.php');
require_once('../../controllers/common_controllers.php');
require_once('../controller/employee_asset_acknowledgement_controller.php');

@session_start();
header('Content-Type: application/json; charset=utf-8');

$response = array('error' => true, 'message' => 'Unauthorized');

$conn = _connectodb();
$assetIdsJson = isset($_POST['asset_ids']) ? $_POST['asset_ids'] : '[]';
$assetIds = json_decode($assetIdsJson, true);
if (!is_array($assetIds)) {
    $assetIds = array();
}
$consent = isset($_POST['consent']) ? (string) $_POST['consent'] : '';
if ($consent !== '1') {
    echo json_encode(array('error' => true, 'message' => 'You must confirm possession of all listed assets.'));
    exit;
}

$employeeId = 0;
$source = 'EmployeePortal';

if (isset($_POST['public_token']) && trim($_POST['public_token']) !== '') {
    $verified = verifyEmployeeAssetAckShareToken($_POST['public_token']);
    if (empty($verified['valid'])) {
        echo json_encode(array('error' => true, 'message' => $verified['message'] ?? 'Invalid link'));
        exit;
    }
    $employeeId = (int) $verified['employee_id'];
    $source = 'PublicForm';
} else {
    if (!isset($_SESSION['pb_username'])) {
        echo json_encode($response);
        exit;
    }
    $roles = $_SESSION['Roles'] ?? array();
    $employeeId = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : 0;
    if ($employeeId <= 0) {
        echo json_encode(array('error' => true, 'message' => 'Employee profile not found.'));
        exit;
    }
}

$ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
$result = acknowledgeEmployeeCompanyAssets($conn, $employeeId, $assetIds, $source, $ip);
echo json_encode($result);
