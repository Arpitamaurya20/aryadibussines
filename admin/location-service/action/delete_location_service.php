<?php
include('../../controllers/common_controllers.php');

include('../controller/location_service_controller.php');

$conn = _connectodb();
$location_serviceId = $_POST['deleteID'];
$response = Deletelocation_serviceData($conn,$location_serviceId);
if($response==false){
	echo 'success';
}
?>
