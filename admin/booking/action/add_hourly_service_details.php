<?php
include('../../controllers/common_controllers.php');
include('../controller/booking_controller.php');
$conn = _connectodb();
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
if(isset($_POST))
{
	$_POST['CreatedBy'] = $username;
    $conn = _connectodb();
    $response = InsertHourlyServiceDetails($conn,$_POST);
}
echo json_encode($response);
?>