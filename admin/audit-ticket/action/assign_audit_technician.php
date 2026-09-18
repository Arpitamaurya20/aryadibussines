<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/audit_ticket_controller.php');
include('../../employees/controller/employee_controller.php');

$UserType = SessionCheck();
$conn = _connectodb();

$data = $_POST;
$data['UpdatedBy'] = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : 'Portal';
if (!empty($data['reassign'])) {
    $data['is_reassign'] = 1;
}

$response = auditTicketAssignToTechnician($conn, $data, $_SESSION);
echo json_encode($response);
