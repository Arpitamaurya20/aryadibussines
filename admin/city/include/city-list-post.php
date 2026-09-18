<?php

include("../../controllers/common_controllers.php");
include('../controller/city_controller.php');

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
   $searchQuery = " and ( CityName like '%".$searchValue."%' or CityId like '%".$searchValue."%') ";
}

// $filter = " where CityId ORDER BY CityId";
$filter = " where 1";

$filter = $filter.$searchQuery;
$totalRecordwithFilter = _getTotalRows($conn,'citydata', $filter);
## Total number of record with filtering
$totalRecords = getTotalCity($conn);

$filter = $filter." limit ".$row.",".$rowperpage;
$city_details = _getTableRecords($conn,'citydata', $filter);




foreach ($city_details as $city_data) {
  extract($city_data);
  $IsFeatured = "";
  if($featured == 1)
  {
    $IsFeatured = "<i class='fa fa-check' aria-hidden='true' style='color: blue;'></i>";
  }
  $Status = "";
  if($status == 0)
  {
      $Status = "<a href='?action=deactive&ID=$CityId' class='btn btn-dark btn-sm shadow-none waves-effect waves-dark' title='Click to active' data-toggle='tooltip'> Deactive</a>";
  }
  else
  {
      $Status = "<a href='?action=active&ID=$CityId' class='btn btn-success btn-sm shadow-none waves-effect waves-dark' title='Click to Deactive' data-toggle='tooltip'>Active</a>";
  }

  $Delete = "DeleteCity('$CityId',$image)";


  
  $data[] = array(
     "CityName"=>$city_data['CityName'],
     "IsFeatured"=>$IsFeatured,
     "Status"=>$Status,
     "Edit"=>"<span style='cursor:pointer;'><a href='update-city?Id=$CityId'><i class='fal fa-edit'></i></a></span>",
     "Delete"=>"<a onclick=$Delete><i class='fal fa-trash' aria-hidden='true'></i></a>"
            
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