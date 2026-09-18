<?php
include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');
$UserType = SessionCheck();
$conn = _connectodb();
$response = array();
$response['error'] = true;

if(isset($_POST))
{
    // print_r($_POST);
    // $EmployeeID = $_POST['EmpID'];
    $EmployeeLeaveID = $_POST['ID'];
    // $getEmployeeDataLeave = $EmployeeData['EmployeeLeave'];
    $EmployeeLeaveData = getEmployeeLeaveDataByID($conn,$_POST);
    $LeaveType =  $EmployeeLeaveData['TypeOfLeave'];
    $EmployeeID =  $EmployeeLeaveData['EmployeeID'];
    $FromDate = $EmployeeLeaveData['FromDate'];
    $ToDate = $EmployeeLeaveData['ToDate'];

    $fromDateTime = new DateTime($FromDate);
    $toDateTime = new DateTime($ToDate);
    $interval = $fromDateTime->diff($toDateTime);
    $LeaveDays = $interval->format('%a');
     $EmployeeData = getEmployeeData($conn,$EmployeeID);
    $getEmployeeDataLeave = $EmployeeData['EmployeeLeave'];
    $EmployeeLeave =  $getEmployeeDataLeave + $LeaveDays;

    $update_param = " EmployeeLeave = '$EmployeeLeave' where ID=$EmployeeID";
    $result = _UpdateTableRecords($conn,'employees', $update_param);

    $update_employee_leave = " Approved = '0' where ID=$EmployeeLeaveID";
    $resultEmpLeave = _UpdateTableRecords($conn,'employee_leave', $update_employee_leave);

    

     if($result == true)
    {
        $response['message'] = "Leave Approoved";
        $response['error'] = false;
    }
    else
        $response['message'] = "Technical Problem. Please try again";

}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>
