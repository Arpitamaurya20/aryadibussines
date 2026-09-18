<?php

include("../../controllers/common_controllers.php");
include('../controller/site_controller.php');

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
   $searchQuery = " and (site_name like '%".$searchValue."%') ";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'site', $filter);
## Total number of record with filtering
$totalRecords = getTotalSite($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$site_details = _getTableRecords($conn,'site', $filter);

foreach ($site_details as $site_data) {
  extract($site_data);

  $data[] = array(
     "SiteName"=>$site_data['site_name'],
            "Delete"=>"<a onclick='Deletesite(".$site_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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