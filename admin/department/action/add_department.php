<?php
include("../../controllers/common_controllers.php");
include('../controller/department_controller.php');
$conn = _connectodb();

$department_name = $_POST["department_name"];
$department_head = $_POST["department_head"];
$addDate = date('Y-m-d');
$addTime = date('H:i:s');


$department_query = "INSERT INTO department (Department_name,Department_head,created_date,created_time) VALUES('$department_name','$department_head','$addDate','$addTime ')";
$department_result = mysqli_query($conn, $department_query);
$department_response = array();


if ($department_result) {
    $department_response['error'] = false;
    $department_response['message'] = "Department Added to System";

} else {
    echo mysqli_error($conn);
    $department_response['error'] = true;
    $department_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($department_response);

?>