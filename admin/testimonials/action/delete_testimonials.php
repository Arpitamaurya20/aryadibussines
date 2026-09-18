<?php

include('../../controllers/common_controllers.php');

include('../controller/testimonials_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = DeletetestionialsData($conn,$id);

if($response==false){

	echo 'success';

}

?>

