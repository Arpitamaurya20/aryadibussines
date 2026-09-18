<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../common_api_header.php');
require_once('../admin/controllers/common_controllers.php');
setTimeZone();
$response = array();
$conn = _connectodb();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (isset($data['AssetsCode']) && !empty($data['AssetsCode'])) {
    $assetCode = mysqli_real_escape_string($conn, $data['AssetsCode']);
} elseif (isset($_GET['code']) && !empty($_GET['code'])) {
    $assetCode = mysqli_real_escape_string($conn, $_GET['code']);
} else {
    $response["error"] = true;
    $response["message"] = "Missing Asset Code";
    echo json_encode($response);
    exit;
}
$sql1 = "SELECT * FROM branchassets_qr_code WHERE UniqueCode = '$assetCode' LIMIT 1";
$qrResult = _getSQLRecords($conn, $sql1);
if (!empty($qrResult)) {
    $assetID = $qrResult[0]['AssetID'];
    $sql2 = "SELECT `ID`, `BranchID`, `EquipmentName`, `Make`, `Model`, `SNo`, `Capacity`, 
                    `Qty`, `UoM`, `UnitRate`, `ManufacturingYear`, `EquipmentAge`, `ServiceType`, 
                    `Category`, `SubCategory`, `Tat`, `AMCStartDate`, `AMCEndDate`, `SOW`, 
                    `FloorNumber`, `EquipmentLocation`, `Description`, `CreatedBy`, 
                    `CreatedDate`, `IsActive`
             FROM branch_assets 
             WHERE ID = '$assetID' LIMIT 1";
    $assetResult = _getSQLRecords($conn, $sql2);

    if (!empty($assetResult)) {
        $response["error"] = false;
        $response["message"] = "Asset details fetched successfully";
        $response["asset"] = $assetResult[0];
        $response["qr_info"] = $qrResult[0];
        if ($qrResult[0]['Assigned'] == "0") {
            $response["assign_required"] = true;
            $response["next_action"] = "Go to asset assignment section";
        } else {
            $response["assign_required"] = false;
            $response["next_action"] = "Asset already assigned";
        }
    } else {
        $response["error"] = true;
        $response["message"] = "Asset not found in branch_assets";
    }
} else {
    $response["error"] = true;
    $response["message"] = "Invalid Asset Code";
}

echo json_encode($response);
?>
