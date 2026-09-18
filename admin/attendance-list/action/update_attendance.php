<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/attendance_controller.php');
$response = array();
if(isset($_POST))
{

    $conn = _connectodb();
    // print_r($_POST);exit;
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    // $form_action = $_POST['form_action'];
        $response = UpdateAttendance($conn,$_POST);
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}
echo json_encode($response);
?>