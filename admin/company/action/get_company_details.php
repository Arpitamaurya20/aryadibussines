<?php
include("../../controllers/common_controllers.php");
include('../controller/company_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $ID = $_POST['ID'];
    $Company_details = GetCompanyDetailsbyID($conn,$ID);
    $response['error'] = false;
    $response['data'] = $Company_details;
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>