<?php
include("../../controllers/common_controllers.php");
include('../controller/department_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $result = DeleteDepartment($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "Department Deleted";
        $response['error'] = false;
    }
    else
    	$response['message'] = "Technical Problem. Please try again";
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>