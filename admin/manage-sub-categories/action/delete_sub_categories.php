<?php
include('../../controllers/common_controllers.php');
include('../controller/sub_categories_controller.php');

$conn = _connectodb();

$id = $_POST['deleteid'];

$response = deleteSubCategories($conn,$id);

if($response == true)
    {
        $response['error'] = false;
    }
else{
    	$response['error'] = true;
}

?>