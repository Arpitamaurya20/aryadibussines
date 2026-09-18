<?php
include("../../controllers/common_controllers.php");
include('../controller/service_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $laundry_details = GetLaundryServiceConfDetailsbyID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $laundry_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>