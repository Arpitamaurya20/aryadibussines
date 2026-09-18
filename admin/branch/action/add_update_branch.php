<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include("../../controllers/common_controllers.php");
include("../../employees/controller/employee_controller.php");
include('../controller/branch_controller.php');
$response = array();
if(isset($_POST))
{
    $conn = _connectodb();
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $form_action = $_POST['form_action'];
    if($form_action == "add")
        $response = InsertBranch($conn,$_POST);
    else
        $response = UpdateBranch($conn,$_POST);
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}
echo json_encode($response);
?>