<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $TicketID = $_POST['TicketID'];
    // $Client_id = $_POST['ClientTicketID'];
    $result = UpdateSparePart($conn,$TicketID,$_POST);
    if($result == true)
    {
        $response['message'] = "Spare Part added in this Ticket";
        $response['error'] = false;
    }
    else
    	$response['message'] = "Technical Problem. Please try again";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>