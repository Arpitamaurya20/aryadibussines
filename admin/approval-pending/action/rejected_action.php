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
    'message' => 'You are not authorized to cancel this ticket.'
);

$ticket_details = _getTableDetails($conn, 'corporate_tickets', " where ID = $TicketID");
if(!is_array($ticket_details) || empty($ticket_details))
{
    $response['message'] = 'Ticket not found.';
    echo json_encode($response);
    exit;
}

$canReject = false;
if($UserType == "Admin")
{
    $canReject = true;
}
else if($UserType == "Corporate Admin" && isset($_SESSION['Roles']['CorporateID']))
{
    $canReject = ((int)$_SESSION['Roles']['CorporateID'] === (int)$ticket_details['CorporateID']);
}

if(!$canReject)
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

$change_status = ManageTicketReject($conn,$TicketID);

echo json_encode($change_status);

?>