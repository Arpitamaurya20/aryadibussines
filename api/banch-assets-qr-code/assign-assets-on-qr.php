<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
setTimeZone();
$response = array();
$conn = _connectodb();
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (isset($data['AssetsID']) && isset($data['UniqueCode'])) {
    $AssetsID   = mysqli_real_escape_string($conn, $data['AssetsID']);
    $UniqueCode = mysqli_real_escape_string($conn, $data['UniqueCode']);
    $row = _getTableDetails($conn, "branchassets_qr_code", "WHERE UniqueCode = '$UniqueCode' LIMIT 1");
    if (!empty($row)) {
        if ($row['Assigned'] == '1' && !empty($row['AssetID'])) {
            $response['error'] = true;
            $response['message'] = "This QR code is already assigned to Asset ID: " . $row['AssetID'];
        } else {
            $query_parameter = "AssetID = '$AssetsID', Assigned = '1' WHERE UniqueCode = '$UniqueCode' LIMIT 1";
            $update_result = _UpdateTableRecords($conn, "branchassets_qr_code", $query_parameter);

            if ($update_result['error'] == false) {
                $response['error'] = false;
                $response['message'] = "Asset assigned successfully";
                $response['asset_id'] = $AssetsID;
                $response['unique_code'] = $UniqueCode;
            } else {
                $response['error'] = true;
                $response['message'] = "Failed to assign asset";
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "QR code not found!";
    }

} else {
    $response['error'] = true;
    $response['message'] = "Missing required fields!";
}

echo json_encode($response);
?>
