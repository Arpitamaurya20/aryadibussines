<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn=_connectodb();

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    "error" => true,
    "message" => "",
    "data" => []
];

/* ================= VALIDATION ================= */
if (empty($data['CompanyID']) || !is_numeric($data['CompanyID'])) {
    $response['message'] = "CompanyID is required";
    echo json_encode($response);
    exit;
}

$CompanyID = (int)$data['CompanyID'];

/* ================= CHECK EXISTING INITIALIZED CART ================= */
$checkSql = "
    SELECT ID, CartNumber, CompanyID, Status
    FROM corporate_quotation_cart
    WHERE CompanyID = $CompanyID
      AND Status = 'Initialized'
      AND IsActive = 1
    ORDER BY ID DESC
    LIMIT 1
";

$existingCart = _getSQLRecords($conn, $checkSql);

if (!empty($existingCart)) {
    // Return existing cart
    $response['error'] = false;
    $response['message'] = "Existing cart returned";
    $response['data'] = [
        "CartID" => $existingCart[0]['ID'],
        "CartNumber" => $existingCart[0]['CartNumber'],
        "CompanyID" => $existingCart[0]['CompanyID'],
        "Status" => $existingCart[0]['Status']
    ];

    echo json_encode($response);
    exit;
}

/* ================= CREATE NEW CART ================= */
$insertData = [
    "CartNumber"   => 'CART-' . $CompanyID . '-' . date('YmdHis'),
    "CompanyID"    => $CompanyID,
    "TicketID"     => -1,
    "QuotationID"  => -1,
    "Status"       => 'Initialized',
    "CreatedDate"  => date('Y-m-d'),
    "CreatedTime"  => date('H:i:s'),
    "IsActive"     => 1
];

$insertResponse = _InsertTableRecords_prepare(
    $conn,
    "corporate_quotation_cart",
    $insertData
);

if ($insertResponse['error']) {
    $response['message'] = $insertResponse['message'];
    echo json_encode($response);
    exit;
}

/* ================= SUCCESS RESPONSE ================= */
$response['error'] = false;
$response['message'] = "Cart initialized successfully";
$response['data'] = [
    "CartID" => $insertResponse['last_insert_id'],
    "CartNumber" => $insertData['CartNumber'],
    "CompanyID" => $CompanyID,
    "Status" => "Initialized"
];

echo json_encode($response);
exit;
