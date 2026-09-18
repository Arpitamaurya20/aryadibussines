<?php
include('../../controllers/common_controllers.php');
include('../controller/categories_controller.php');
$conn = _connectodb();
$id = $_POST['deleteid'];
$response = softdeletecategory($conn,$id);
if($response == true)
    {
        $response['error'] = false;
    }
else{
    	$response['error'] = true;
}
?>
