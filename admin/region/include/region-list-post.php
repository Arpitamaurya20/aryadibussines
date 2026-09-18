<?php

include("../../controllers/common_controllers.php");
include("../../employees/controller/employee_controller.php");
include('../controller/region_controller.php');

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
   $searchQuery = " and (RegionName like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'region', $filter);
## Total number of record with filtering
$totalRecords = getTotalRegion($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$region_details = _getTableRecords($conn,'region', $filter);

foreach ($region_details as $Region_data) {
  extract($Region_data);

  $RegionHeadEmployeeID = $Region_data['RegionHead'];
  $where = " where ID = $RegionHeadEmployeeID";
  $Region_head_details = _getTableRecords($conn,'employees', $where);
  foreach($Region_head_details as $Region_Emp_data){
    $Region_Emp_name = $Region_Emp_data['Name'];
  }

  $RegionCorpHeadEmployeeID = $Region_data['RegionCorporateHead'];
  $where = " where ID = $RegionCorpHeadEmployeeID";
  $Region_Corp_head_details = _getTableRecords($conn,'employees', $where);
  foreach($Region_Corp_head_details as $Region_Corp_Emp_data){
    $Region_Corp_Emp_name = $Region_Corp_Emp_data['Name'];
  }

  $data[] = array(
     "RegionName"=>$Region_data['RegionName'],
        "RegionHead"=>$Region_Emp_name,
          "RegionCorporateHead"=>$Region_Corp_Emp_name,
            "Update"=>"<a onclick='UpdateRegion_modal(".$Region_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteRegion(".$Region_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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