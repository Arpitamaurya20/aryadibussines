<?php
include("../../controllers/common_controllers.php");
include('../controller/company_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";
if(isset($_POST))
{
    $_POST['CreatedBy'] = $username;
    $conn = _connectodb();
    $response = CorporateSetAccess($conn,$_POST);
}
echo json_encode($response);
?>