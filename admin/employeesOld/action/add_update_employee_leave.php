<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');
$response = array();
if(isset($_POST))
{
    $conn = _connectodb();
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $form_action = $_POST['form_action'];
    if($form_action == "add")
        $response = InsertEmployeeLeave($conn,$_POST);
    else
        $response = UpdateEmployeeLeave($conn,$_POST);
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}
echo json_encode($response);
?>