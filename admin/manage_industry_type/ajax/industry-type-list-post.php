<?php
session_start();
include('../../controllers/common_controllers.php');

$UserType = SessionCheck();
$conn = _connectodb();

// DataTables parameters
$draw   = isset($_POST['draw'])   ? intval($_POST['draw'])   : 1;
$start  = isset($_POST['start'])  ? intval($_POST['start'])  : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';

// Count total records
$total_query = "SELECT COUNT(*) as cnt FROM master_industry_type WHERE 1";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$totalRecords = $total_row['cnt'];

// Count filtered records
$filter_where = "WHERE 1";
if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($conn, $search);
    $filter_where .= " AND IndustryTypeName LIKE '%$search_escaped%'";
}
$filtered_query = "SELECT COUNT(*) as cnt FROM master_industry_type $filter_where";
$filtered_result = mysqli_query($conn, $filtered_query);
$filtered_row = mysqli_fetch_assoc($filtered_result);
$totalFiltered = $filtered_row['cnt'];

// Fetch paginated data
$data_query = "SELECT ID, IndustryTypeName, IsActive, CreatedDate 
               FROM master_industry_type 
               $filter_where 
               ORDER BY ID ASC 
               LIMIT $start, $length";
$data_result = mysqli_query($conn, $data_query);

$data = [];
while ($row = mysqli_fetch_assoc($data_result)) {

    // Status badge
    if ($row['IsActive'] == 1) {
        $status = '<span class="badge badge-success">Active</span>';
    } else {
        $status = '<span class="badge badge-danger">Inactive</span>';
    }

    // Format date
    $created_date = !empty($row['CreatedDate']) ? date('d M Y', strtotime($row['CreatedDate'])) : '-';

    // Action buttons
    $action = '<button class="btn btn-xs btn-warning" onclick="editIndustryType(' 
                . $row['ID'] . ',\'' . addslashes($row['IndustryTypeName']) . '\',' . $row['IsActive'] . ')">
                    <i class="fal fa-edit"></i> Edit
                </button> ';

    if ($UserType == "Admin" || $UserType == "Sub Admin") {
        $action .= '<button class="btn btn-xs btn-danger" onclick="deleteIndustryType(' . $row['ID'] . ')">
                        <i class="fal fa-trash"></i> Delete
                    </button>';
    }

    $data[] = [
        'id'               => $row['ID'],
        'IndustryTypeName' => $row['IndustryTypeName'],
        'IsActive'         => $status,
        'CreatedDate'      => $created_date,
        'Action'           => $action,
    ];
}

$response = [
    "draw"            => $draw,
    "recordsTotal"    => $totalRecords,
    "recordsFiltered" => $totalFiltered,
    "data"            => $data,
];

echo json_encode($response);
?>