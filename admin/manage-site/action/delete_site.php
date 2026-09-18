<?php

include('../../controllers/common_controllers.php');

include('../controller/site_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deletesite($conn,$id);

if($response==false){

	echo 'success';

}
