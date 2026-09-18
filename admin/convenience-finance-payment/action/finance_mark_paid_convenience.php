<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$convenience_id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$payment_reference = isset($_POST['payment_reference']) ? trim((string) $_POST['payment_reference']) : '';
$payment_mode = isset($_POST['payment_mode']) ? trim((string) $_POST['payment_mode']) : '';
$payment_remarks = isset($_POST['payment_remarks']) ? trim((string) $_POST['payment_remarks']) : '';

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
if (!canFinanceMarkConveniencePaid($conn, $convenience_id, $roles)) {
    $response['message'] = 'You are not allowed to mark this convenience as paid.';
    echo json_encode($response);
    exit;
}

$update = financeMarkConveniencePaid($conn, $convenience_id, $finance_employee_id, $payment_reference, $payment_mode, $payment_remarks);
if (!empty($update['success'])) {
    $response['error'] = false;
    $response['message'] = 'Payment marked as done. Employee has been notified.';
} else {
    $response['message'] = 'Unable to mark payment. ' . convenienceUpdateErrorMessage($update['result']);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
