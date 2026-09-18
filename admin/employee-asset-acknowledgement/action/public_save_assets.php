<?php
ini_set('display_errors', '0');
error_reporting(0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

try {
    require_once(__DIR__ . '/../../controllers/common_controllers.php');
    require_once(__DIR__ . '/../controller/employee_asset_acknowledgement_controller.php');

    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $verified = verifyEmployeeAssetAckShareToken($token);
    if (empty($verified['valid'])) {
        echo json_encode(array('error' => true, 'message' => $verified['message'] ?? 'Invalid link'));
        exit;
    }

    $employeeId = (int) $verified['employee_id'];
    $conn = _connectodb();
    if (!$conn) {
        echo json_encode(array('error' => true, 'message' => 'Database connection failed.'));
        exit;
    }

    $assetsJson = isset($_POST['assets']) ? $_POST['assets'] : '[]';
    $assets = json_decode($assetsJson, true);
    if (!is_array($assets)) {
        $assets = array();
    }

    $result = saveEmployeeCompanyAssets($conn, $employeeId, $assets, 'PublicForm', $employeeId);
    echo json_encode($result);
} catch (Throwable $e) {
    echo json_encode(array('error' => true, 'message' => 'Failed to save assets. Please try again.'));
}
