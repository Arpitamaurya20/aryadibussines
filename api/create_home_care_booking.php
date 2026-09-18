<?php
// twm
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
require_once("../admin/booking/controller/booking_controller.php");
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
$conn = _connectodb();
if(isset($data['bookingName']))
{
   $response = CreateBooking($conn,$data,"Home Care Services");
}
else
{
    $response['error'] = true;
    $response['emessage'] = "Missing User Fields!";
}
echo json_encode($response);
?>