<?php

include("../../controllers/common_controllers.php");
include('../controller/arc_controller.php');

@session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";
// $UserType = $_GET['UserType'];

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (ItemName like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1 ORDER BY ID DESC";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'arc', $filter);
## Total number of record with filtering
$totalRecords = getTotalARCItem($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$arc_details = _getTableRecords($conn,'arc', $filter);

foreach ($arc_details as $arc_data) {
  extract($arc_data);

  $CategoriesID = $arc_data['ItemCategories'];
  $where = " where ID = $CategoriesID";
  $Categories_details = _getTableRecords($conn,'manage_categories', $where);
  foreach($Categories_details as $category_data){
    $CategoriesName = $category_data['CategoriesName'];
  }

  $data[] = array(
     "Code"=>$arc_data['ItemCode'],
     "ArcProductName"=>$arc_data['ItemName'],
     "Price"=>$arc_data['ItemPrice'],
     "CategoryName"=>$CategoriesName,
            "Update"=>"<a onclick='UpdateARC_modal(".$arc_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteARCAssets(".$arc_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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