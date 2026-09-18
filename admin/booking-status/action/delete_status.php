<?php

include('../../controllers/common_controllers.php');

include('../controller/status_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deletestatus($conn,$id);

if($response==false){

	echo 'success';

}
