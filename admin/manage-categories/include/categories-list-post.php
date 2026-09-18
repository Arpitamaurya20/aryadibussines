<?php

include("../../controllers/common_controllers.php");
include('../controller/categories_controller.php');

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
   $searchQuery = " and (CategoriesName like '%".$searchValue."%') ";
}

$filter = " where IsActive=1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'manage_categories', $filter);
## Total number of record with filtering
$totalRecords = getTotalCategories($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$categories_details = _getTableRecords($conn,'manage_categories', $filter);

foreach ($categories_details as $categories_data) {
  extract($categories_data);
  $ID=$categories_data['ID'];
 $data[] = array(
    "CategoriesName" => $categories_data['CategoriesName'],
    "Type"=>$categories_data['Type'],
    "Delete" =>
        "<a onclick='EditCategory($ID, \"".$categories_data['CategoriesName']."\", \"".$categories_data['Type']."\")'>
            <i class='fal fa-edit'></i>
         </a>
         &nbsp;
         <a onclick='DeleteCategories($ID);'>
            <i class='fal fa-trash'></i>
         </a>"
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
// DeleteCategories(".$categories_data['ID'].")
echo json_encode($response);
?>
