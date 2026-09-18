<?php

include("../../controllers/common_controllers.php");
include('../controller/resume_controller.php');

session_start();
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
   $searchQuery = " and (name like '%".$searchValue."%' or email like '%".$searchValue."%' or phone like '%".$searchValue."%') ";
}

$filter = " where id ORDER BY id DESC";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'resume', $filter);
## Total number of record with filtering
$totalRecords = getTotalResume($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$resume_details = _getTableRecords($conn,'resume', $filter);




foreach ($resume_details as $resume_data) {
  extract($resume_data);
  

  
  $data[] = array(
     "Name"=>$name,
     "Email"=>$email,
     "Phone"=>$phone,
     "Resume"=>"<a href='' onclick='resume($resume)'>$resume</a>",
     "Delete"=>"<a onclick='Deleteresume($id)'><i class='fal fa-trash' aria-hidden='true'></i>"
            
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