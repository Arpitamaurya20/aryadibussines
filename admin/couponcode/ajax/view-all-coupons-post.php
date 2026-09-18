<?php
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
session_start();

$conn = _connectodb();

## Read DataTables values
$draw         = $_POST['draw'];
$row          = $_POST['start'];
$rowperpage   = $_POST['length'];
$searchValue  = $_POST['search']['value'];

## Base filter
$filter = " WHERE 1 ";

## Search filter
if ($searchValue != '') {
    $filter .= " AND (
        CouponName LIKE '%$searchValue%' 
        OR Discount LIKE '%$searchValue%' 
        OR Type LIKE '%$searchValue%'
        OR CreatedDate LIKE '%$searchValue%'
    )";
}

## Optional Active filter
if (isset($_GET['IsActive']) && $_GET['IsActive'] !== '') {
    $IsActive = intval($_GET['IsActive']);
    $filter .= " AND IsActive = $IsActive";
}

## Count filtered records
$sql_count = "SELECT COUNT(*) AS total FROM coupons_code $filter";
$result_count = mysqli_query($conn, $sql_count);
$totalRecordwithFilter = 0;

if ($result_count) {
    $row_count = mysqli_fetch_assoc($result_count);
    $totalRecordwithFilter = $row_count['total'];
}

## Total records (without filter)
$totalRecords = _getTotalRows($conn, 'coupons_code', ' WHERE 1');

## Order & Limit
$filter .= " ORDER BY ID DESC LIMIT $row, $rowperpage";

## Fetch data
$sql = "SELECT 
            ID,
            CouponName,
            Discount,
            Type,
            IsActive,
            CreatedDate,
            CreatedTime
        FROM coupons_code 
        $filter";

$result = mysqli_query($conn, $sql);
$data = array();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        $status_html = $row['IsActive'] == 1
            ? "<span class='badge bg-success'>Active</span>"
            : "<span class='badge bg-danger'>Inactive</span>";

        $statusBtn = $row['IsActive'] == 1
    ? "<button class='btn btn-success btn-xs' onclick='toggleCouponStatus({$row['ID']},0)'>Active</button>"
    : "<button class='btn btn-warning btn-xs' onclick='toggleCouponStatus({$row['ID']},1)'>Inactive</button>";

$action_html = "
    <button class='btn btn-primary btn-xs' onclick='openEditCoupon({$row['ID']})'>
        <i class='fas fa-edit'></i>
    </button>
    <button class='btn btn-danger btn-xs' onclick='openDeleteCoupon({$row['ID']})'>
        <i class='fas fa-trash'></i>
    </button>
    $statusBtn
";

        $data[] = array(
            "CouponName"   => cleantext($row['CouponName']),
            "Discount"     => cleantext($row['Discount']),
            "Type"         => cleantext($row['Type']),
            "IsActive"     => $status_html,
            "CreatedDate"  => $row['CreatedDate'],
            "CreatedTime"  => $row['CreatedTime'],
            "Action"       => $action_html
        );
    }
}

## Response for DataTables
$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);
?>
