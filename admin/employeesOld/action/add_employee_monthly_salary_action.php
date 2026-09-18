<?php
@session_start();
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
$UserType = SessionCheck();
$conn = _connectodb();
setTimeZone();
$EmployeeAllData = getEmployeeMonthlySalaryData($conn,$_POST['ID']);

foreach ($EmployeeAllData as $salaryData)
{
	$basicData = $salaryData['Basic'];
	$daData = $salaryData['DA'];
	$hraData = $salaryData['HRA'];
}
if(($basicData!=null) and ($daData!=null) and ($hraData!=null) )
{
$response = InsertMonthlySalaryEmployee($conn,$salaryData,$_POST['year'],$_POST['month']);
}
else {
	echo "false";
}
?>