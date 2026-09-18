<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();
$id = isset($_POST['ID']) ? (int) $_POST['ID'] : 0;
$data = getCorporateMasterSubAuditById($conn, $id);

if (!empty($data)) {
    echo json_encode(array('error' => false, 'data' => $data));
} else {
    echo json_encode(array('error' => true, 'message' => 'Record not found.'));
}
