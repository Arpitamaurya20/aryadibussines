<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
require_once('../../includes/autoloader.inc.php');
// include('../branch/controller/branch_controller.php');
// include('../branch/controller/company_controller.php');
$conn = _connectodb();
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
if(isset($_POST))
{
	$_POST['UpdatedBy'] = $username;
    $_POST['UpdatedDate'] = date("Y-m-d");
    $_POST['UpdatedTime'] = date("H:i:s");
    $_POST['Remarks'] = $_POST['ticket_remarks'];
    $conn = _connectodb();

    // $Branch_details= GetBranchDetailsbyID($conn,$ID);
    // $Branch_mobile_number = $Branch_details["BranchMobile"];
    $response_images['error'] = false;
    if($_POST['TicketStatus'] == "Closed")
    {
        // Check for Ticket Images
        $corporateticket_obj = new Corporateticket($conn);
        $response_images = $corporateticket_obj->CheckForTicketImages($_POST['TicketID']);
    }
    if($response_images['error'] == false)
    {
        $notification_array = array();
        array_push($notification_array,'8948975967');
        if (isset($_POST['IsReassign']) && $_POST['IsReassign'] == 1) {
            $_POST['TicketStatus'] = ''; // force empty
        }
        $response = ManageTicketAssignmentStatus($conn,$_POST);
    }
    else
    {
        $response = $response_images;
    }
}
echo json_encode($response);
?>