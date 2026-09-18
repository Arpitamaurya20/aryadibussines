<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$convenience_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$remarks = isset($_POST['remarks']) ? trim((string) $_POST['remarks']) : '';
$approved_amount = isset($_POST['approved_amount']) ? trim((string) $_POST['approved_amount']) : '';
if ($convenience_id <= 0) {
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
if (!hasHrConvenienceApprovalAccess($roles)) {
    $response['message'] = 'You do not have HR convenience approval access.';
    echo json_encode($response);
    exit;
}

$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
if (!canHrApproveConvenience($conn, $convenience_id, $roles)) {
    $response['message'] = 'You are not allowed to approve this convenience request.';
    echo json_encode($response);
    exit;
}

$update = hrFinalApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks, $approved_amount);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'HR final approval done. Record sent to finance for payment.';
} else {
    $response['message'] = 'Unable to approve convenience. ' . convenienceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
