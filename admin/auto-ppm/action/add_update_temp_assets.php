<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/auto_ppm_controller.php');
$response = array();

if(isset($_POST))
{
    $conn = _connectodb();
    setTimeZone();
    
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $form_action = $_POST['form_action'];
    
    if($form_action == "add" || $form_action == "update")
    {
        $response = InsertUpdateTempBranchAssetsInfo($conn, $_POST);
    }
    else
    {
        $response['error'] = true;
        $response['message'] = "Invalid form action";
    }
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}

echo json_encode($response);
?>

