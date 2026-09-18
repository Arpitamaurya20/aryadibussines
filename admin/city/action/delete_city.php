<?php
include('../../controllers/common_controllers.php');

include('../controller/city_controller.php');

$conn = _connectodb();
$CityId = $_POST['deleteID'];
$image = $_POST['image'];
$response = DeleteCityData($conn,$CityId,$image);
if($response==false){
	echo 'success';
}
?>
