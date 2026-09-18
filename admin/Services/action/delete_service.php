<?php

include('../../controllers/common_controllers.php');

include('../controller/service_controller.php');
$conn = _connectodb();

$ID = $_POST['deleteID'];
$serviceImg = $_POST['serviceImg'];

$response = DeleteServiceData($conn,$ID,$serviceImg);

if($response==false){

	echo 'success';

}
