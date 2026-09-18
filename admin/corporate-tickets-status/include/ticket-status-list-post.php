<?php

include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_status_controller.php');

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
$totalRecordwithFilter = _getTotalRows($conn,'corporate_tickets_status', $filter);
## Total number of record with filtering
$totalRecords = getTotalTicketStatus($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$ticket_status_details = _getTableRecords($conn,'corporate_tickets_status', $filter);

foreach ($ticket_status_details as $ticket_status_data) {
  extract($ticket_status_data);

  $data[] = array(
     "Status"=>$ticket_status_data['Status'],
            "Delete"=>"<a onclick='DeleteCorporateStatus(".$ticket_status_data['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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