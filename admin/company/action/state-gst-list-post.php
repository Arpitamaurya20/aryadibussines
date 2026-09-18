<?php
require_once('../../includes/autoloader.inc.php');

@session_start();
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$columnName = "a.ID";
$columnSortOrder = "DESC";
$nav = $_GET['nav'];
$CorporateID = $CompanyID = $_GET['CompanyID'];
$filter_company_id = "";
if(isset($_GET['filter_company_id']))
{
    $f_company_id = $_GET['filter_company_id'];
    if($f_company_id != "")
    {
        $filter_company_id = " AND a.CompanyID = $f_company_id";
    }
}


$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.CompanyState like '%".$searchValue."%' or a.GST like '%".$searchValue."%' or a.Address like '%".$searchValue."%') ";
}

$filter = " where a.IsActive = 1";
if($CompanyID != -1)
{
    $filter = $filter." AND a.CompanyID = $CompanyID";
}
$filter = $filter;
$totalRecordwithFilter = $core->_getTotalRows($conn,'company_state_gst a JOIN company b ON a.CompanyID = b.ID',$filter);
$filter = $filter.$searchQuery.$filter_company_id." ORDER BY ".$columnName." ".$columnSortOrder;
## Total number of record with filtering
$totalRecords = $totalRecordwithFilter;

$filter = $filter." limit ".$row.",".$rowperpage;
$sql = "Select a.*,b.CompanyName FROM company_state_gst a JOIN company b ON a.CompanyID = b.ID ".$filter;
//echo $sql;
$result = mysqli_query($conn, $sql);
$state_gst_array = array();
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($state_gst_array, $row);
        }
    }
} else {
    //echo $sql;
}
foreach ($state_gst_array as $state_gst) 
{
  extract($state_gst);
  
  $data[] = array(
    "CompanyName"=>$CompanyName,
    "State"=>$CompanyState,
    "GST"=>$GST,
    "Address"=>$Address,
    "Action"=>"<a onclick='UpdateStateGST($ID)'><i class='fal fa-pencil' aria-hidden='true'></i></a> &nbsp;<a onclick='DeleteStateGST($ID)'><i class='fal fa-trash' aria-hidden='true'></i></a>"
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