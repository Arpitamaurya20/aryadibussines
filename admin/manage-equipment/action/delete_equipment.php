<?php

include('../../controllers/common_controllers.php');

include('../controller/equipment_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deleteEquipment($conn,$id);

if($response==false){

	echo 'success';

}
