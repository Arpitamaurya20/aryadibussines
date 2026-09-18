<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$convenience_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$reason = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';

if ($convenience_id <= 0) {
    echo json_encode($response);
    exit;
}

$roles = $_SESSION['Roles'] ?? array();
if (!hasFinanceConveniencePaymentAccess($roles)) {
    $response['message'] = 'You do not have finance convenience payment access.';
    echo json_encode($response);
    exit;
}

$finance_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
if (!canFinanceRejectConveniencePayment($conn, $convenience_id, $roles)) {
    $response['message'] = 'You are not allowed to reject this convenience payment.';
    echo json_encode($response);
    exit;
}

$update = financeRejectConveniencePayment($conn, $convenience_id, $finance_employee_id, $reason);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Payment rejected. Employee has been notified.';
} else {
    $response['message'] = 'Unable to reject payment. ' . convenienceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
