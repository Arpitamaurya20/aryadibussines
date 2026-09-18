<?php

include("../../controllers/common_controllers.php");
include('../controller/sub_categories_controller.php');

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
   $searchQuery = " and (SubCategoriesName like '%".$searchValue."%') ";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'manage_subcategories', $filter);
## Total number of record with filtering
$totalRecords = getTotalSubCategories($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$sub_categories_details = _getTableRecords($conn,'manage_subcategories', $filter);

foreach ($sub_categories_details as $sub_categories_data) {
  extract($sub_categories_data);
  $CategoriesID = $sub_categories_data['Categories'];
  $where = " where ID = $CategoriesID";
  $sub_categories_details = _getTableRecords($conn,'manage_categories', $where);
  foreach($sub_categories_details as $categories_data){
    $CategoriesName = $categories_data['CategoriesName'];
}

  $data[] = array(
    "CategoriesName"=>$CategoriesName,
     "SubCategoriesName"=>$sub_categories_data['SubCategoriesName'],
            "Delete"=>"<a onclick='DeleteSubCategories(".$sub_categories_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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