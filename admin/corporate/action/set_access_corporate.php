<?php
include("../../controllers/common_controllers.php");
include('../controller/corporate_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
    $_POST['CreatedBy'] = $username;
    $conn = _connectodb();
    $response = SetAccessCorporate($conn,$_POST);
}
echo json_encode($response);
?>