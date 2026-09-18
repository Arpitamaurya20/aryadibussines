<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/employee-asset-acknowledgement/controller/employee_asset_acknowledgement_controller.php');

header('Content-Type: application/json; charset=utf-8');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (!is_array($data) || !isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
    $response['error'] = true;
    $response['message'] = 'Missing User Fields!';
    echo json_encode($response);
    exit;
}

$employeeId = (int) $data['EmployeeID'];
if ($employeeId <= 0) {
    $response['error'] = true;
    $response['message'] = 'Invalid EmployeeID.';
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$employee = getEmployeeAssetAckEmployee($conn, $employeeId);
if (!$employee) {
    $response['error'] = true;
    $response['message'] = 'Employee not found';
    echo json_encode($response);
    exit;
}

$token = createEmployeeAssetAckShareToken($employeeId);
$formUrl = buildEmployeeAssetAckPublicUrl($employeeId);
$verified = verifyEmployeeAssetAckShareToken($token);
$tokenExpiry = isset($verified['expiry']) ? (int) $verified['expiry'] : 0;

$assignedAssets = getEmployeeCompanyAssets($conn, $employeeId, true);
$pendingAssets = getPendingEmployeeCompanyAssets($conn, $employeeId);

$response['error'] = false;
$response['message'] = 'Form link generated successfully.';
$response['data'] = array(
    'employee_id' => (int) $employee['ID'],
    'employee_name' => $employee['Name'],
    'employee_number' => $employee['EmployeeNumber'],
    'department' => $employee['Department'],
    'designation' => $employee['Designation'],
    'form_url' => $formUrl,
    'token' => $token,
    'token_expires_at' => $tokenExpiry > 0 ? date('Y-m-d H:i:s', $tokenExpiry) : null,
    'token_expires_in_days' => $tokenExpiry > 0 ? max(0, (int) ceil(($tokenExpiry - time()) / 86400)) : 0,
    'has_pending_acknowledgement' => !empty($pendingAssets),
    'pending_asset_count' => count($pendingAssets),
    'total_assigned_assets' => count($assignedAssets),
);

echo json_encode($response);
