<?php 
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
	$_POST['pb_username'] = $username;
    $_POST['CreatedDate'] = date("Y-m-d");
    $_POST['CreatedTime'] = date("H:i:s");
    $conn = _connectodb();
    $response = ManageAccess($conn,$_POST); 
    if($response['error'] == false)
    {
    	$response['message'] = "Access Updated";
    }
}
echo json_encode($response);
?>