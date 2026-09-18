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
	$_POST['UpdatedBy'] = $username;
    $_POST['UpdatedDate'] = date("Y-m-d");
    $_POST['UpdatedTime'] = date("H:i:s");
    $conn = _connectodb();
    $response = ManageBookingAssignmentStatus($conn,$_POST);
}
echo json_encode($response);
?>