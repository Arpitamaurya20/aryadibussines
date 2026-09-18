<?php

include('../../controllers/common_controllers.php');

include('../controller/uom_controller.php');



$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deleteuom($conn,$id);

if($response==false){

    echo 'success';

}
?>
