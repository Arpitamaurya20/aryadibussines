<?php
include('../../controllers/common_controllers.php');

include('../controller/banners_controller.php');

$conn = _connectodb();
$ID = $_POST['deleteID'];
$response = DeletetestimonialData($conn,$ID);
if($response==false){
	echo 'success';
}
?>
