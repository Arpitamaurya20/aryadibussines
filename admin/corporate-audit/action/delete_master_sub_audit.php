<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();
$id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$response = deleteCorporateMasterSubAudit($conn, $id);
echo json_encode($response);
