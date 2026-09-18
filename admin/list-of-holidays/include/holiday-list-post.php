<?php

include("../../controllers/common_controllers.php");
include('../controller/holidays_controller.php');

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
$totalRecordwithFilter = _getTotalRows($conn,'listofholidays', $filter);
## Total number of record with filtering
$totalRecords = getTotalHolidays($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$holidays_details = _getTableRecords($conn,'listofholidays', $filter);

foreach ($holidays_details as $holiday_data) {
  extract($holiday_data);

  $RegionHeadEmployeeID = $holiday_data['RegionName'];
  $where = " where ID = $RegionHeadEmployeeID";
  $Region_head_details = _getTableRecords($conn,'region', $where);
  foreach($Region_head_details as $Region_Emp_data){
    $Region_Emp_name = $Region_Emp_data['RegionName'];
  }

  $data[] = array(
     "RegionName"=>$Region_Emp_name,
     "HolidaysName"=>$holiday_data['HolidaysName'],
     "HolidaysDate"=>$holiday_data['HolidaysDate'],
            "Update"=>"<a onclick='UpdateHolidays_modal(".$holiday_data['ID'].")' class='cursor-pointer'><i class='fal fa-edit' aria-hidden='true'></i></a>",
            "Delete"=>"<a onclick='DeleteHolidays(".$holiday_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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