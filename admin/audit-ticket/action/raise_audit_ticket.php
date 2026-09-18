<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/audit_ticket_controller.php');
include('../../branch/controller/branch_controller.php');

$UserType = SessionCheck();
$conn = _connectodb();

$data = $_POST;
$data['CreatedBy'] = isset($_SESSION['pb_username']) ? $_SESSION['pb_username'] : 'Portal';

$branchDetails = null;
if (isset($data['BranchID']) && (int) $data['BranchID'] > 0) {
    $branchDetails = GetBranchDetailsbyID($conn, (int) $data['BranchID']);
}

$response = createCorporateAuditTicket($conn, $data, $branchDetails);
echo json_encode($response);
