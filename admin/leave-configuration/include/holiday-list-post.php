<?php

include("../../controllers/common_controllers.php");
include('../controller/leave_configuration_controller.php');

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
   $searchQuery = " and (TypeOfLeave like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'leaveconfiguation', $filter);
## Total number of record with filtering
$totalRecords = getTotalLeave($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$leave_details = _getTableRecords($conn,'leaveconfiguation', $filter);

foreach ($leave_details as $leave_data) {
  extract($leave_data);

  $data[] = array(
     "TypeOfLeave"=>$leave_data['TypeOfLeave'],
     "NumberOfLeave"=>$leave_data['NumberOfLeave'],
            "Update"=>"<a onclick='UpdateLeave_modal(".$leave_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteLeave(".$leave_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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