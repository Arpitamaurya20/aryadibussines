<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/cart_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $OrderID = GenerateOrderID($conn) + 1;
    $_POST['OrderID'] = $OrderID;
    
    $conn = _connectodb();
    $response = InsertOrder($conn,$_POST);
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>