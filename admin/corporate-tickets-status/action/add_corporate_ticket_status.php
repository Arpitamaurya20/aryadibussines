<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_status_controller.php');
$response = array();
if(isset($_POST))
{
    $conn = _connectodb();
    $response = InsertCorporateStatus($conn,$_POST);
    if($response['error'] == false)
        $response['message'] = "Corporate Tickets Status Added";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>