<?php

include('../../controllers/common_controllers.php');

include('../controller/schema_controller.php');



$conn = _connectodb();

$ID = $_POST['deleteID'];

$response = DeleteSchemaData($conn,$ID);

if($response==false){

	echo 'success';

}

?>

