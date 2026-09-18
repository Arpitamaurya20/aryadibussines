<?php
ob_start();
require_once('common_api_header.php');
header('Content-Type: application/json; charset=utf-8');
require_once('../admin/controllers/common_controllers.php');
require_once("../admin/booking/controller/booking_controller.php");
setTimeZone();

$conn = _connectodb();
$data = json_decode(file_get_contents("php://input"), true);
$response = [];

if (
    empty($data['bookingName']) ||
    empty($data['phoneNumber']) ||
    empty($data['serviceName']) ||
    empty($data['cityName'])
) {
    ob_end_clean();
    echo json_encode([
        "error" => true,
        "message" => "Required fields missing"
    ]);
    exit;
}

$response = CreateServiceBooking($conn, $data);
ob_end_clean();
echo json_encode($response);
