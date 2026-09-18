<?php
require_once('../common_api_header.php');
require_once('../../admin/controllers/common_controllers.php');
require_once('../../admin/crm/controller/crm_controller.php');

$response = array();
$conn = _connectodb();

$sql = "SELECT * FROM crm_accounts WHERE IsActive = 1 ORDER BY ID DESC";
$result = mysqli_query($conn, $sql);
$customers = [];
if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $customers[] = $row;
    }
}

if ($customers !== null) {
    $response['data'] = $customers;
    $response['error'] = false;
    $response['message'] = "Customers fetched successfully";
} else {
    $response["error"] = true;
    $response["message"] = "Failed to fetch customers";
}

echo json_encode($response);
?>
