<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/auto_ppm_controller.php');
$response = array();

if(isset($_POST))
{
    $conn = _connectodb();
    setTimeZone();
    $response = DeleteTempBranchAssetsInfo($conn, $_POST);
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}

echo json_encode($response);
?>

