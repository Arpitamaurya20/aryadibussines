<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$data = json_decode(file_get_contents("php://input"), true);

/* =====================
   FILTERS (OPTIONAL)
===================== */
$where = " WHERE 1 ";

if (!empty($data['CityLeadID'])) {
    $CityLeadID = (int)$data['CityLeadID'];
    $where .= " AND sb.CityLeadID = $CityLeadID ";
}

if (!empty($data['TechnicianID'])) {
    $TechnicianID = (int)$data['TechnicianID'];
    $where .= " AND sb.TechnicianID = $TechnicianID ";
}

if (!empty($data['Status'])) {
    $Status = cleantext($data['Status']);
    $where .= " AND sb.BookingStatus = '$Status' ";
}

/* =====================
   PAGINATION
===================== */
$page  = !empty($data['page']) ? (int)$data['page'] : 1;
$limit = !empty($data['limit']) ? (int)$data['limit'] : 20;
$start = ($page - 1) * $limit;

/* =====================
   QUERY
===================== */
$sql = "
SELECT 
    sb.ID,
    sb.BookingCode,
    sb.CustomerName,
    sb.Phone,
    sb.ServiceName,
    sb.SubService,
    sb.Price,
    sb.City,
    sb.BookingStatus,
    sb.TechnicianID,
    sb.CreatedDate,
    sb.CreatedTime
FROM service_bookings sb
$where
ORDER BY sb.ID DESC
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
