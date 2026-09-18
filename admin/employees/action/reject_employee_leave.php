<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');

$conn = _connectodb();
$response = array(
    'error' => true,
    'message' => 'Technical Problem. Please try again'
);

$roles = isset($_SESSION['Roles']) ? $_SESSION['Roles'] : array();
if (!hasHrLeaveApprovalAccess($roles)) {
    $response['message'] = 'Not authorized.';
    echo json_encode($response);
    exit;
}

$EmployeeLeaveID = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$reason = isset($_POST['RejectionReason']) ? $_POST['RejectionReason'] : '';
if ($EmployeeLeaveID <= 0) {
    echo json_encode($response);
    exit;
}

if (!canHrApproveLeave($conn, $EmployeeLeaveID, $roles)) {
    $response['message'] = 'This leave is not pending HR final approval.';
    echo json_encode($response);
    exit;
}

$approver_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : 0;
$update = hrRejectEmployeeLeave($conn, $EmployeeLeaveID, $approver_employee_id, $reason);
if (!empty($update['success'])) {
    $response['message'] = 'Leave rejected successfully.';
    $response['error'] = false;
} else {
    $response['message'] = 'Unable to reject leave. ' . leaveUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
