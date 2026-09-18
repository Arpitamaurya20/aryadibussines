<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_spare_part_controller.php');
$response = array();
$response['error'] = true;
if(isset($_POST))
{
    $conn = _connectodb();
    $result = DeleteBranchSparePart($conn,$_POST);
    if($result == true)
    {
        $response['message'] = "Branch Spare Part Deleted";
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