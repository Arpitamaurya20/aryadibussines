<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../../employees-convenience/controller/convenience_controller.php');

$conn = _connectodb();
$response = array('error' => true, 'message' => 'Technical Problem. Please try again');

$ids = isset($_POST['IDs']) && is_array($_POST['IDs']) ? $_POST['IDs'] : array();
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));
$payment_reference = isset($_POST['payment_reference']) ? trim((string) $_POST['payment_reference']) : '';
$payment_mode = isset($_POST['payment_mode']) ? trim((string) $_POST['payment_mode']) : '';
$payment_remarks = isset($_POST['payment_remarks']) ? trim((string) $_POST['payment_remarks']) : '';

if (empty($ids)) {
    $response['message'] = 'Please select at least one convenience request.';
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
$summary = financeBulkMarkConveniencePaid($conn, $ids, $finance_employee_id, $roles, $payment_reference, $payment_mode, $payment_remarks);
$response['error'] = ((int) ($summary['success'] ?? 0)) <= 0;
$response['message'] = buildConvenienceBulkActionMessage($summary, 'marked as paid');
$response['summary'] = $summary;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
