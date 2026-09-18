<?php
include("../../controllers/common_controllers.php");
include('../controller/approval_pending_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();

$TicketID = $_POST['TicketID'];
$UserType = SessionCheck();
$response = array(
    'error' => true,
    'message' => 'You are not authorized to approve this ticket.'
);

$ticket_details = _getTableDetails($conn, 'corporate_tickets', " where ID = $TicketID");
if(!is_array($ticket_details) || empty($ticket_details))
{
    $response['message'] = 'Ticket not found.';
    echo json_encode($response);
    exit;
}

$canApprove = false;
if($UserType == "Admin")
{
    $canApprove = true;
}
else if($UserType == "Corporate Admin" && isset($_SESSION['Roles']['CorporateID']))
{
    $canApprove = ((int)$_SESSION['Roles']['CorporateID'] === (int)$ticket_details['CorporateID']);
}

if(!$canApprove)
{
    echo json_encode($response);
    exit;
}

if($ticket_details['Status'] != 'Need Approval By Company Admin')
{
    $response['message'] = 'This ticket is not pending company admin approval.';
    echo json_encode($response);
    exit;
}

$change_status = ManageTicketApproval($conn,$TicketID);

echo json_encode($change_status);

?>