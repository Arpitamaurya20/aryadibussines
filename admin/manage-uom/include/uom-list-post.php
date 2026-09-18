<?php

include("../../controllers/common_controllers.php");
include('../controller/uom_controller.php');

@session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";
$UserType = $_GET['UserType'];

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (UOMName like '%".$searchValue."%') ";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'manage_uom', $filter);
## Total number of record with filtering
$totalRecords = getTotalUOM($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$uom_details = _getTableRecords($conn,'manage_uom', $filter);

foreach ($uom_details as $uom_data) {
  extract($uom_data);

  $data[] = array(
     "SiteName"=>$uom_data['UOMName'],
            "Delete"=>"<a onclick='DeleteUOM(".$uom_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
   );
}
## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);
// echo $response;
echo json_encode($response);
?>