<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents("php://input");
$data = json_decode($data_raw, true);

$response = array();
$response['data'] = array();

$conn = _connectodb();

/* ================= INPUTS ================= */
$CompanyID = isset($data['CompanyID']) ? intval($data['CompanyID']) : -1;
$filter_company_id = isset($data['filter_company_id']) ? $data['filter_company_id'] : "";
$type = isset($data['type']) ? $data['type'] : "";
$category = isset($data['category']) ? $data['category'] : "";
$subcategory = isset($data['subcategory']) ? $data['subcategory'] : "";
$searchValue = isset($data['search']) ? trim($data['search']) : "";

$page  = isset($data['page']) ? intval($data['page']) : 1;
$limit = isset($data['limit']) ? intval($data['limit']) : 20;
$offset = ($page - 1) * $limit;

/* ================= FILTERS ================= */
$where = " WHERE a.IsActive = 1 AND (a.ARCItem = 1 Or a.ARCItem = 0)";

if ($CompanyID != -1) {
    $where .= " AND a.CompanyID = $CompanyID";
}

if (!empty($filter_company_id)) {
    $where .= " AND a.CompanyID = $filter_company_id";
}

if (!empty($type)) {
    $where .= " AND a.Type = '$type'";
}

if (!empty($category)) {
    $where .= " AND a.Category = '$category'";
}

if (!empty($subcategory)) {
    $where .= " AND a.SubCategory = '$subcategory'";
}

if (!empty($searchValue)) {
    $where .= " AND (
        a.Category LIKE '%$searchValue%' OR
        a.Type LIKE '%$searchValue%' OR
        a.Make LIKE '%$searchValue%' OR
        a.SubCategory LIKE '%$searchValue%' OR
        a.LineItemName LIKE '%$searchValue%' OR
        a.HSN LIKE '%$searchValue%' OR
        a.ARCCode LIKE '%$searchValue%'
    )";
}

/* ================= TOTAL COUNT ================= */
$count_sql = "
SELECT COUNT(*) as total
FROM corporate_rate_card a
JOIN company b ON a.CompanyID = b.ID
$where
";
$count_result = mysqli_query($conn, $count_sql);
$totalRecords = mysqli_fetch_assoc($count_result)['total'];

/* ================= DATA QUERY ================= */
$sql = "
SELECT a.*, b.CompanyName
FROM corporate_rate_card a
JOIN company b ON a.CompanyID = b.ID
$where
ORDER BY a.ID DESC
LIMIT $offset, $limit
";

$result = mysqli_query($conn, $sql);

if ($result && $result->num_rows > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $response['data'][] = array(
            "id" => $row['ID'],
            "company_name" => $row['CompanyName'],
            "type" => $row['Type'],
            "category" => $row['Category'],
            "subcategory" => $row['SubCategory'],
            "line_item_name" => $row['LineItemName'],
            "make" => $row['Make'],
            "hsn" => $row['HSN'],
            "arc_code" => $row['ARCCode'],
            "uom" => $row['UoM'],
            "price" => $row['Price'],
            "tax" => $row['Tax']
        );
    }
}

/* ================= RESPONSE ================= */
$response['error'] = false;
$response['page'] = $page;
$response['limit'] = $limit;
$response['total_records'] = $totalRecords;
$response['total_pages'] = ceil($totalRecords / $limit);

echo json_encode($response);
?>
