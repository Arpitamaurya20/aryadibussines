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

if (isset($data['UniqueCode'])) {
    $UniqueCode = mysqli_real_escape_string($conn, $data['UniqueCode']);
    $UpdatedDate = date("Y-m-d H:i:s");

    // Get QR code details
    $row = _getTableDetails($conn, "branchassets_qr_code", "WHERE UniqueCode = '$UniqueCode' LIMIT 1");

    if (!empty($row)) {
        if ($row['Assigned'] != '1') {
            $response['error'] = true;
            $response['message'] = "This QR code is not assigned to any asset.";
        } else {
            // Unassign the asset
            $query_parameter = "AssetID = -1, Assigned = '0', Latitude = NULL, Longitude = NULL, UpdatedDate = '$UpdatedDate' WHERE UniqueCode = '$UniqueCode' LIMIT 1";
            $update_result = _UpdateTableRecords($conn, "branchassets_qr_code", $query_parameter);

            if ($update_result['error'] == false) {
                $response['error'] = false;
                $response['message'] = "Asset unassigned successfully.";
                $response['unique_code'] = $UniqueCode;
                $response['updated_date'] = $UpdatedDate;
            } else {
                $response['error'] = true;
                $response['message'] = "Failed to unassign asset.";
            }
        }
    } else {
        $response['error'] = true;
        $response['message'] = "QR code not found!";
    }

} else {
    $response['error'] = true;
    $response['message'] = "Missing required field: UniqueCode!";
}

echo json_encode($response);
?>
