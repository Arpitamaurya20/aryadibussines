<?php
include("../../controllers/common_controllers.php");
include('../controller/approval_pending_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $Branch_details = GetApporoveTicketDetailsbyID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $Branch_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>