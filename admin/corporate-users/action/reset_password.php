<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_users_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
    $conn = _connectodb();
    $response = CorporateUserResetPassword($conn,$_POST);
}
echo json_encode($response);
?>