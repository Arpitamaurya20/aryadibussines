<?php

include('../../controllers/common_controllers.php');

include('../controller/resume_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deleteresume($conn,$id);

if($response==false){

	echo 'success';

}
