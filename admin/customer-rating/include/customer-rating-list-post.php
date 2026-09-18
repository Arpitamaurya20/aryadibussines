<?php

include("../../controllers/common_controllers.php");
include('../controller/customer_rating_controller.php');

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
   $searchQuery = " and (Rating like '%".$searchValue."%') ";
}

$filter = " where 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'customer_rating', $filter);
## Total number of record with filtering
$totalRecords = getTotalCustomerRating($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$customer_rating_details = _getTableRecords($conn,'customer_rating', $filter);

foreach ($customer_rating_details as $customer_rating_data) {
  extract($customer_rating_data);

  $data[] = array(
     "TicketID"=>$customer_rating_data['TicketID'],
     "Rating"=>$customer_rating_data['Rating'],
     "Message"=>$customer_rating_data['Message'],
     "Date"=>$customer_rating_data['CreatedDate'],
            "Delete"=>"<a onclick='DeleteCustomerRating(".$customer_rating_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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