<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
$sv_obj = new Sitevisits($conn);

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.CompanyName like '%".$searchValue."%' OR b.BranchSite like '%".$searchValue."%' OR c.VisitTitle LIKE  '%".$searchValue."%' OR c.ReportNumber LIKE '%".$searchValue."%' OR c.ContactPerson LIKE '%".$searchValue."%')";
}
$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];
$filter_date = " AND (c.CreatedDate >= '$StartDate' AND c.CreatedDate <= '$EndDate')";

/*$filter_employee = "";
if(isset($_GET['EmployeeID']))
{
    $EmployeeID = $_GET['EmployeeID'];
    if($EmployeeID != "-1")
    {
        $filter_employee = " AND a.EmployeeID = $EmployeeID";
    }
}*/

$color_started = "#FFA500";
$color_observation_recorded = "#ff8c00";
$color_completed = "#28A745";

$filter = " where c.IsActive = 1";
$filter = $filter.$searchQuery.$filter_date." ORDER BY c.ID DESC";

$sql_count = " Select COUNT(*) as row_count FROM `site_visits` c INNER JOIN company a ON c.CorporateID = a.ID INNER JOIN branch b ON c.BranchID = b.ID ".$filter;
$result_Count = mysqli_query($conn, $sql_count);

$row_count_result = $result_Count->fetch_assoc();
$row_count = $row_count_result['row_count'];
$totalRecordwithFilter = $row_count;

## Total number of record with filtering
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');
$totalRecords = $totalRecordwithFilter;

$sql = "SELECT a.CompanyName,b.BranchSite,c.* FROM `site_visits` c INNER JOIN company a ON c.CorporateID = a.ID INNER JOIN branch b ON c.BranchID = b.ID ".$filter;
$sql = $sql." limit ".$row.",".$rowperpage;
$records = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($records, $row);
        }
    }
}

foreach ($records as $record) 
{
  extract($record);
  if($Status == "Completed")
    $bg_color = $color_completed;
  if($Status == "Observation Recorded")
    $bg_color = $color_observation_recorded;
  if($Status == "Started")
    $bg_color = $color_started;
  if($ReportNumber == "")
  {
    $ReportNumber = "Not Set";
  }
  $data[] = array(
    "Corporate"=>$CompanyName,
    "Branch"=>$BranchSite,
    "VisitTitle"=>$VisitTitle,
    "Status"=>"<span class='badge badge-danger cursor-pointer' style='background:".$bg_color."'>".$Status."</span>",
    "ContactPerson"=>$ContactPerson,
    "CreatedOn"=>$CreatedDate."<br>".$CreatedTime,
    "CompletedOn"=>$CompletedDate."<br>".$CompletedTime,
    "Details"=>"<a onclick='ViewSiteVistDetails($ID)'><span class='badge badge-primary cursor-pointer'>Details</span></a>",
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