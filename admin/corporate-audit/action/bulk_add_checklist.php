<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();

$data_raw = file_get_contents('php://input');
$payload = json_decode($data_raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$payload['CreatedBy'] = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : '';
$response = bulkInsertCorporateAuditChecklist($conn, $payload);
echo json_encode($response);
