<?php

include("../../controllers/common_controllers.php");
include('../controller/booking_status_controller.php');

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
   $searchQuery = " and (Status like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'booking_status', $filter);
## Total number of record with filtering
$totalRecords = getTotalBookingStatus($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$booking_status_details = _getTableRecords($conn,'booking_status', $filter);

foreach ($booking_status_details as $booking_status_data) {
  extract($booking_status_data);

  $data[] = array(
     "Status"=>$booking_status_data['Status'],
            "Delete"=>"<a onclick='DeleteBookingStatus(".$booking_status_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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