<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/audit_ticket_controller.php');

$UserType = SessionCheck();
$conn = _connectodb();

$ticketId = isset($_POST['AuditTicketID']) ? (int) $_POST['AuditTicketID'] : 0;
$status = isset($_POST['Status']) ? $_POST['Status'] : '';
$remarks = isset($_POST['Remarks']) ? $_POST['Remarks'] : '';
$updatedBy = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : 'Portal';

if ($ticketId <= 0 || $status === '') {
    echo json_encode(array('error' => true, 'message' => 'Ticket ID and Status are required.'));
    exit;
}

$response = updateCorporateAuditTicketStatus($conn, $ticketId, $status, $updatedBy, $remarks);
echo json_encode($response);
