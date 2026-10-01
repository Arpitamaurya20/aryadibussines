<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');

$response = array();
$response['error'] = true;
$response['message'] = "Technical Problem. Please try again";

if(isset($_POST['ID']) && isset($_POST['IsActive']))
{
	$conn = _connectodb();
	$response = UpdateBranchAssetActiveStatus($conn, $_POST['ID'], $_POST['IsActive']);
}

echo json_encode($response);
?>
