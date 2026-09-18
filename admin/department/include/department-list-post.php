<?php

include("../../controllers/common_controllers.php");
include('../controller/department_controller.php');

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
   $searchQuery = " and (DepartmentName like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'department', $filter);
## Total number of record with filtering
$totalRecords = getTotalDepartment($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$department_details = _getTableRecords($conn,'department', $filter);

foreach ($department_details as $department_data) {
  extract($department_data);

  $RegionHeadEmployeeID = $department_data['DepartmentHead'];
  $where = " where ID = $RegionHeadEmployeeID";
  $Region_head_details = _getTableRecords($conn,'employees', $where);
  foreach($Region_head_details as $Region_Emp_data){
    $Region_Emp_name = $Region_Emp_data['Name'];
  }

  $data[] = array(
     "DepartmentName"=>$department_data['DepartmentName'],
     "DepartmentHead"=>$Region_Emp_name,
            "Update"=>"<a onclick='UpdateDepartment_modal(".$department_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteDepartment(".$department_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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