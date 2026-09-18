<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents("php://input"), true);

/* =====================
   VALIDATION
===================== */
if (empty($data['CustomerNumber'])) {
    echo json_encode([
        "error" => true,
        "message" => "CustomerID required"
    ]);
    exit;
}

$CustomerID = (int)$data['CustomerNumber'];

/* =====================
   FILTERS
===================== */
$where = " WHERE Phone = $CustomerID ";

if (!empty($data['Status'])) {
    $Status = cleantext($data['Status']);
    $where .= " AND BookingStatus = '$Status' ";
}

/* =====================
   PAGINATION
===================== */
$page  = !empty($data['page']) ? (int)$data['page'] : 1;
$limit = !empty($data['limit']) ? (int)$data['limit'] : 10;
$start = ($page - 1) * $limit;

/* =====================
   QUERY
===================== */
$sql = "
SELECT
    ID,
    BookingCode,
    ServiceName,
    SubService,
    Price,
    BookingStatus,
    TechnicianID,
    CreatedDate,
    CreatedTime
FROM service_bookings
$where
ORDER BY ID DESC
LIMIT $start, $limit
";

$result = mysqli_query($conn, $sql);

$bookings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}

echo json_encode([
    "error"   => false,
    "page"    => $page,
    "limit"   => $limit,
    "records" => count($bookings),
    "data"    => $bookings
]);
exit;
