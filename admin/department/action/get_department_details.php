<?php
include("../../controllers/common_controllers.php");
include('../controller/department_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $region_details = GetDepartmentDetailsbyID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $region_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>