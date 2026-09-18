<?php

include("../../controllers/common_controllers.php");
include("../../employees/controller/employee_controller.php");
include('../controller/state_controller.php');

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
   $searchQuery = " and (StateName like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'state', $filter);
## Total number of record with filtering
$totalRecords = getTotalState($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$state_details = _getTableRecords($conn,'state', $filter);

## Read value
// $draw = $_POST['draw'];

// $data = array();
// $Emp_name = array();

// ## Total number of record with filtering
// $sel = mysqli_query($conn,"select count(*) as allstate from state WHERE 1");
// $records = mysqli_fetch_assoc($sel);
// $totalRecordwithFilter = $records['allstate'];

// $totalRecords = getTotalState($conn);

// $where = " where IsActive = 1";
// $state_details = _getTableRecords($conn,'state', $where);
// print_r($state_details);

foreach ($state_details as $State_data) {
  extract($State_data);

  $SateHeadEmployeeID = $State_data['StateHead'];
  $where = " where ID = $SateHeadEmployeeID";
  $State_head_details = _getTableRecords($conn,'employees', $where);
  foreach($State_head_details as $State_Emp_data){
    $State_Emp_name = $State_Emp_data['Name'];
  }

  $SateCorpHeadEmployeeID = $State_data['StateCorporateHead'];
  $where = " where ID = $SateCorpHeadEmployeeID";
  $State_Corp_head_details = _getTableRecords($conn,'employees', $where);
  foreach($State_Corp_head_details as $State_Corp_Emp_data){
    $State_Corp_Emp_name = $State_Corp_Emp_data['Name'];
  }

  $RegionID = $State_data['RegionID'];
  $where = " where ID = $RegionID";
  $Region_details = _getTableRecords($conn,'region', $where);
  foreach($Region_details as $Region_data){
    $Region_name = $Region_data['RegionName'];
  }
  

  $data[] = array(
     "StateName"=>$State_data['StateName'],
        "Region"=>$Region_name,
        "StateHead"=>$State_Emp_name,
            "StateCorporateHead"=>$State_Corp_Emp_name,
            "Update"=>"<a onclick='UpdateState_modal(".$State_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteState(".$State_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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