<?php
include("../../controllers/common_controllers.php");
include('../controller/status_controller.php');
$conn = _connectodb();

$status_name = $_POST["status_name"];


$status_query = "INSERT INTO status ( booking_status ) VALUES('$status_name')";
$status_result = mysqli_query($conn, $status_query);
$status_response = array();


if ($status_result) {
    $status_response['error'] = false;
    $status_response['message'] = "Status Added to System";

} else {
    echo mysqli_error($conn);
    $status_response['error'] = true;
    $status_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
}
echo json_encode($status_response);

?>