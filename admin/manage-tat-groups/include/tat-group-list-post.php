<?php
require_once('../../includes/autoloader.inc.php');

@session_start();
$dbh = new dbh();
$conn = $dbh->_connectodb();

$core = new Core();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "ID";
$columnSortOrder = "DESC";

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (Name like '%".$searchValue."%') ";
}

$filter = " where IsActive = 1";
$filter = $filter.$searchQuery;
$totalRecordwithFilter = $core->_getTotalRows($conn,'tat_group', $filter);
## Total number of record with filtering
$totalRecords = $core->_getTotalRows($conn,'tat_group',$filter);

$filter = $filter." limit ".$row.",".$rowperpage;
$tat_groups = $core->_getTableRecords($conn,'tat_group', $filter);

foreach ($tat_groups as $tat_group) {
  extract($tat_group);

  $data[] = array(
     "TATGroup"=>$tat_group['Name'],
     "DefaultTATDays"=>$tat_group['DefaultTATDays'],
     "DefaultTATHours"=>$tat_group['DefaultTATHours'],
     "UpdatedAt"=>$tat_group['UpdatedDate']."<br>".$tat_group['UpdatedTime'],
     "UpdatedBy"=>$tat_group['UpdatedBy'],
     "Action"=>"<a onclick='EditTATGroup(".$tat_group['ID'].")'><i class='fal fa-edit' aria-hidden='true'></i></a>&nbsp;&nbsp;"."<a onclick='DeleteTATGroup(".$tat_group['ID'].")'><i class='fal fa-trash' aria-hidden='true'></i></a>",
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