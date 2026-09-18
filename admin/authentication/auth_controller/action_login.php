<?php 
include('../../controllers/common_controllers.php');
include('authentication_controller.php');
setTimeZone();
if(isset($_POST))
{
    $username_user = $_POST['username'];
    $password_user = $_POST['password'];
    $password_hash = md5($password_user);
    $conn = _connectodb();
    $response = login($conn,$username_user,$password_hash);
    echo json_encode($response);
    // var_dump($response);
}
else
{
    echo "Unauthorized Access";
}
?>
