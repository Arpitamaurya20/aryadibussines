<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $Branch_arc_details = getARCItemsByID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $Branch_arc_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>