<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
include('../../dynamic-ppm/controller/dynamic_ppm_controller.php');
header('Content-Type: application/json');
$response = array();
$response['error'] = true;
if(isset($_POST['ID']))
{
    $conn = _connectodb();
    $ID = intval($_POST['ID']);
    $Branch_details = GetBranchAssetsbyID($conn,$ID);
    if ($Branch_details && isset($Branch_details['ID'])) {
        if (!empty($Branch_details['BranchID'])) {
            $branch_row = _getTableDetails($conn, 'branch', " where ID = " . intval($Branch_details['BranchID']));
            $Branch_details['BranchSite'] = ($branch_row && isset($branch_row['BranchSite'])) ? $branch_row['BranchSite'] : '';
        } else {
            $Branch_details['BranchSite'] = '';
        }
        $assetMap = getDynamicPPMAssetChecklistMapping($conn, $ID);
        $Branch_details['AssetChecklistID'] = ($assetMap && isset($assetMap['ChecklistID'])) ? (int) $assetMap['ChecklistID'] : -1;
        $response['error'] = false;
        $response['data'] = $Branch_details;
    } else {
        $response['message'] = "Asset details not found";
    }
}
else
{
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>
