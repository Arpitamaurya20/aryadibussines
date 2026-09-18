<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
    $conn = _connectodb();
    $response = BranchResetPassword($conn,$_POST);
}
echo json_encode($response);
?>