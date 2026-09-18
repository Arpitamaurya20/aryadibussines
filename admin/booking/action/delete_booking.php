<?php
include('../../controllers/common_controllers.php');
include('../controller/booking_controller.php');
$conn = _connectodb();
$id = $_POST['deleteid'];
$response = deletebooking($conn,$id);
if($response==false){
	echo 'success';
}
?>