<?php
include('../../controllers/common_controllers.php');
include('../controller/employee_controller.php');
setTimeZone();
$UserType = SessionCheck();
$username = $_SESSION['pb_username'];
$response = array();
$response["message"] = "Unauthorized Access";

if(isset($_POST))
{
	$_POST['username'] = $username;
    $_POST['CreatedDate'] = date("Y-m-d");
    $_POST['CreatedTime'] = date("H:i:s");
    $conn = _connectodb();
    $response = CheckForDuplicateEmployeeDetails($conn,$_POST);
    if($response['error'] === false)
    {
        $response = InsertEmployee($conn);
        if($response['error'] == false)
        {
        	$response['message'] = "Employee Added to System";
            if($_POST['work_type'] == "Vendor")
            {
                // add user action
                $_POST['EmployeeID'] = $response['last_insert_id'];
                $_POST['Role'] = "Vendor";
                InsertUserRole($conn,$_POST);
            }
        }
    }
}
echo json_encode($response);

?>