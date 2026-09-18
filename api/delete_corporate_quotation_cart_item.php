<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    "error" => true,
    "message" => ""
];

/* ================= VALIDATION ================= */
if (empty($data['CartItemID']) || !is_numeric($data['CartItemID'])) {
    $response['message'] = "CartItemID is required";
    echo json_encode($response);
    exit;
}

$CartItemID = (int)$data['CartItemID'];

/* ================= SOFT DELETE ================= */
$queryParam = "
    IsActive = 0
    WHERE ID = $CartItemID
";

$updateResponse = _UpdateTableRecords(
    $conn,
    "corporate_quotation_cart_item",
    $queryParam
);

if ($updateResponse['error']) {
    echo json_encode($updateResponse);
    exit;
}

/* ================= SUCCESS RESPONSE ================= */
$response['error'] = false;
$response['message'] = "Cart item deleted successfully";

echo json_encode($response);
exit;
