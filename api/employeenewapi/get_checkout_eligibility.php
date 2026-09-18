<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/employees/controller/employee_controller.php');

setTimeZone();

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = [];

if (!isset($data['EmployeeID']) || $data['EmployeeID'] === '') {
    $response['error'] = true;
    $response['message'] = 'Missing User Fields!';
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
$EmployeeID = (int) $data['EmployeeID'];
$checkout = getEmployeeCheckoutEligibility($conn, $EmployeeID);

$response['error'] = false;
$response['message'] = $checkout['message'];
$response['data'] = $checkout;

echo json_encode($response);
