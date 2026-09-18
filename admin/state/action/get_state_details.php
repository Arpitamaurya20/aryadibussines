<?php
include("../../controllers/common_controllers.php");
include('../controller/state_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $state_details = GetStateDetailsbyID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $state_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>