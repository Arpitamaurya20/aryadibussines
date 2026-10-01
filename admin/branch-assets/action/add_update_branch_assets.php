<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
include('../../dynamic-ppm/controller/dynamic_ppm_controller.php');
$response = array();
if(isset($_POST))
{
    $conn = _connectodb();
    $_POST['CreatedBy'] = $_SESSION['pb_username'];
    $form_action = $_POST['form_action'];
    if($form_action == "add")
        $response = InsertBranchAssets($conn,$_POST);
    else
        $response = UpdateBranchAssets($conn,$_POST);

    $assetID = 0;
    if ($form_action == "add" && isset($response['last_insert_id'])) {
        $assetID = (int) $response['last_insert_id'];
    } elseif (isset($_POST['form_id'])) {
        $assetID = (int) $_POST['form_id'];
    }

    if ($assetID > 0) {
        $checklistID = isset($_POST['asset_checklist_id']) ? (int) $_POST['asset_checklist_id'] : 0;
        $categoryID = isset($_POST['categories']) ? (int) $_POST['categories'] : 0;
        $mapRes = mapDynamicPPMAssetChecklist($conn, $assetID, $categoryID, $checklistID, $_POST['CreatedBy']);
        if (isset($mapRes['changed']) && $mapRes['changed']) {
            if (isset($response['error']) && $response['error'] && isset($response['message']) && $response['message'] === 'No changes to update') {
                $response['error'] = false;
                $response['message'] = $mapRes['message'];
            }
        }
    }
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem, Please try again later !";
}
echo json_encode($response);
?>