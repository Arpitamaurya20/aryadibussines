<?php
include("../../controllers/common_controllers.php");
include('../controller/booking_status_controller.php');
$response = array();
if(isset($_POST))
{
    $conn = _connectodb();
    $response = InsertBooking($conn,$_POST);
    if($response['error'] == false)
        $response['message'] = "Booking Status Added";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>