<?php 
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
    $conn = _connectodb();
    $response = GenerateEmployeeNumber($conn,$_POST); 
}
echo json_encode($response);
?>