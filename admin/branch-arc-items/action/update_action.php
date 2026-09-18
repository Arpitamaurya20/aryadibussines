<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $result = UpdateBranchARCItem($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "Branch ARC Item Updated";
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