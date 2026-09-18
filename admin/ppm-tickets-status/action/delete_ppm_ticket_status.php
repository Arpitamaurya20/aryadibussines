<?php
include("../../controllers/common_controllers.php");
include('../controller/ppm_tickets_status_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $result = DeletePPMStatus($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "PPM Tickets Status Deleted";
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