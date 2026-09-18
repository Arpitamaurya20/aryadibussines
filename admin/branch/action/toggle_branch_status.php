<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');

$response = array();
$response['error'] = true;
$response['message'] = "Technical Problem. Please try again";

if(isset($_POST['ID']) && isset($_POST['IsActive']))
{
	$conn = _connectodb();
	$BranchID = intval($_POST['ID']);
	$IsActive = intval($_POST['IsActive']) === 1 ? 1 : 0;
	$response = UpdateBranchActiveStatus($conn, $BranchID, $IsActive);
}

echo json_encode($response);
?>
