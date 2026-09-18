<?php
include("../../controllers/common_controllers.php");
include('../controller/ppm_controller.php');
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
    $conn = _connectodb();

    // $Branch_details= GetBranchDetailsbyID($conn,$ID);
    // $Branch_mobile_number = $Branch_details["BranchMobile"];

    $response_images = array('error' => false);
    if($_POST['TicketStatus'] == "Closed")
     {
         // Check for Ticket Images
         $ppmticketobj = new Ppmtickets($conn);
         $response_images = $ppmticketobj->CheckForTicketImages($_POST['TicketID']);
    }
    if($response_images['error'] == false)
    {
        $response = ManagePPMTicketAssignmentStatus($conn,$_POST);
    }
    else
    {
        $response = $response_images;
    }

   
}
echo json_encode($response);
?>