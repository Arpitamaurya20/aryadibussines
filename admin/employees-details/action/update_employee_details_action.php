<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../../employees/controller/employee_controller.php');
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
$response = UpdateEmployee($conn,$_POST);
echo json_encode($response);
?>