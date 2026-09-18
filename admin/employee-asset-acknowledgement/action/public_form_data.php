<?php
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once(__DIR__ . '/../../controllers/common_controllers.php');
    require_once(__DIR__ . '/../controller/employee_asset_acknowledgement_controller.php');

    $token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : '');
    $verified = verifyEmployeeAssetAckShareToken($token);
    if (empty($verified['valid'])) {
        echo json_encode(array('error' => true, 'message' => $verified['message'] ?? 'Invalid link'));
        exit;
    }

    $conn = _connectodb();
    if (!$conn) {
        echo json_encode(array('error' => true, 'message' => 'Database connection failed.'));
        exit;
    }

    $employeeId = (int) $verified['employee_id'];
    $employee = getEmployeeAssetAckEmployee($conn, $employeeId);
    if (!$employee) {
        echo json_encode(array('error' => true, 'message' => 'Employee not found or inactive.'));
        exit;
    }

    $assets = getEmployeeCompanyAssets($conn, $employeeId, true);
    $pending = getPendingEmployeeCompanyAssets($conn, $employeeId);

    echo json_encode(array(
        'error' => false,
        'employee' => array(
            'id' => (int) $employee['ID'],
            'name' => $employee['Name'],
            'employee_number' => $employee['EmployeeNumber'],
            'department' => $employee['Department'],
            'designation' => $employee['Designation'],
        ),
        'assets' => array_map(function ($row) {
            return array(
                'id' => (int) $row['ID'],
                'category' => $row['AssetCategory'],
                'description' => $row['AssetDescription'],
                'serial' => $row['AssetSerialNumber'],
                'allocation_date' => $row['AllocationDate'],
                'status' => $row['AcknowledgementStatus'],
                'remarks' => $row['Remarks'],
            );
        }, $assets),
        'pending_assets' => array_map(function ($row) {
            return (int) $row['ID'];
        }, $pending),
        'categories' => getEmployeeAssetCategories(),
        'token' => $token,
    ));
} catch (Throwable $e) {
    echo json_encode(array('error' => true, 'message' => 'Failed to load asset form data.'));
}
