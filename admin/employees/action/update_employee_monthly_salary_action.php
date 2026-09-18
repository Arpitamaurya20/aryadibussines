<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
$response = InsertMonthlySalaryEmployee($conn,$_POST);
echo json_encode($response);
?>