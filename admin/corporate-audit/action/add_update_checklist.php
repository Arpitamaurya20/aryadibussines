<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/corporate_audit_controller.php');

SessionCheck();
$conn = _connectodb();

$form_action = isset($_POST['form_action']) ? $_POST['form_action'] : 'add';
$_POST['CreatedBy'] = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : '';

if ($form_action === 'add') {
    $response = insertCorporateAuditChecklist($conn, $_POST);
} else {
    $response = updateCorporateAuditChecklist($conn, $_POST);
}

echo json_encode($response);
