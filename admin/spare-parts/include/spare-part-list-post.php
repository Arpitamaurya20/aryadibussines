<?php

include("../../controllers/common_controllers.php");
include('../controller/spare_part_controller.php');

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
   $searchQuery = " and (SparePart like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'sparepartlist', $filter);
## Total number of record with filtering
$totalRecords = getTotalSparePart($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$spare_part_details = _getTableRecords($conn,'sparepartlist', $filter);

foreach ($spare_part_details as $spare_part_data) {
  extract($spare_part_data);

  $GetCategoryID = $spare_part_data['Categories'];
  $where = " where ID = $GetCategoryID";
  $Category_details = _getTableRecords($conn,'manage_categories', $where);
  foreach($Category_details as $Category_data){
    $Category_name = $Category_data['CategoriesName'];
  }

  $data[] = array(
     "SparePartName"=>$spare_part_data['SparePart'],
      "Categories"=>$Category_name,
      "Price"=>$spare_part_data['Price'],
          "Update"=>"<a onclick='UpdateSparePart_modal(".$spare_part_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
          "Delete"=>"<a onclick='DeleteSparePart(".$spare_part_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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