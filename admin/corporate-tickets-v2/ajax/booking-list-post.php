<?php

include("../../controllers/common_controllers.php");
include('../controller/booking_controller.php');

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
   $searchQuery = " and (Name like '%".$searchValue."%' or BookingID like '%".$searchValue."%') or Phone like '%".$searchValue."%' or Email like '%".$searchValue."%' or Service_name like '%".$searchValue."%' or SubService like '%".$searchValue."%' or City_name like '%".$searchValue."%' or State_name like '%".$searchValue."%' or Status like '%".$searchValue."%' or AssignedTo like '%".$searchValue."%' or BookingTime like '%".$searchValue."%' or BookingDate like '%".$searchValue."%'";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'confirm_booking', $filter);
## Total number of record with filtering
$totalRecords = getTotalBookings($conn);


$filter = $filter." limit ".$row.",".$rowperpage;
$Booking_details = _getTableRecords($conn,'confirm_booking', $filter);

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

foreach ($Booking_details as $Booking_data) {
  extract($Booking_data);


  $data[] = array(
     "Name"=>$Name,
     "Phone_Email"=>$Phone."<br>".$Email,
     "Service_name"=>$Service_name,
     "Customer_address"=>$Customer_address,
     "View_Booking"=>"<a onclick='ViewBookingDetails($ID)'><span class='badge badge-primary cursor-pointer'>View Booking</span></a>",
     "BookingDate"=>$BookingDate,
     "BookingTime"=>$BookingTime,
     "Delete"=>"<a class='' onclick='Deletebooking($ID)'><i class='fal fa-trash' aria-hidden='true'></i>"
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