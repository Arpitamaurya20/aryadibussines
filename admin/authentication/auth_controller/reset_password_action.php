<?php 
include('../../controllers/common_controllers.php');
include('authentication_controller.php');
$conn = _connectodb();
$response = array();
if(isset($_POST))
{
    $email = $_POST['email'];
    $password = $_POST['password'];
    $response = UpdatePassword($conn,$email,$password);
}
else
{
	$response['error'] = true;
	$response['message'] = "Unauthorized Access";
}
echo json_encode($response);
?>
