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
$employee_obj = new Employee($conn);

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (b.EmployeeName like '%".$searchValue."%') ";
}
$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];
$filter_date = " AND (a.RecordDate >= '$StartDate' AND a.RecordDate <= '$EndDate')";

$filter_employee = "";
if(isset($_GET['EmployeeID']))
{
    $EmployeeID = $_GET['EmployeeID'];
    if($EmployeeID != "-1")
    {
        $filter_employee = " AND a.EmployeeID = $EmployeeID";
    }
}

$filter = " where b.IsActive = 1";
$filter = $filter_date.$filter_employee.$filter." ORDER BY a.ID DESC";

$sql_count = " Select COUNT(*) as row_count FROM `employee_attendance` a LEFT JOIN employees b ON a.EmployeeID = b.ID".$filter;
$result_Count = mysqli_query($conn, $sql_count);

$row_count_result = $result_Count->fetch_assoc();
$row_count = $row_count_result['row_count'];
$totalRecordwithFilter = $row_count;

## Total number of record with filtering
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');
$totalRecords = $totalRecordwithFilter;

$sql = "Select b.ID, b.Name, a.* from `employee_attendance` a LEFT JOIN employees b ON a.EmployeeID = b.ID".$filter;
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

  $duration = $employee_obj->calculatetimeDifference($InTime,$OutTime);

  // ---------------- CHECK IN ----------------
  $InTime_html = $InTime;

  if($CheckinImage != "")
  {
    $InTime_html .= " <a onclick='ViewAttendanceImage(\"".$CheckinImage."\")'>
                      <i class='fal fa-eye'></i></a>";
  }

  // Add Checkin Location
  if(!empty($Latitude) && !empty($Longitude))
  {
    $InTime_html .= " <br>
    <a href='javascript:void(0);'
    onclick='openLocationModal(".$Latitude.",".$Longitude.")'
    style='font-size:12px;color:#184384;'>
    View Location
    </a>";
  }

  // ---------------- CHECK OUT ----------------
  $OutTime_html = $OutTime;

  if($CheckoutImage != "")
  {
    $OutTime_html .= " <a onclick='ViewAttendanceImage(\"".$CheckoutImage."\")'>
                      <i class='fal fa-eye'></i></a>";
  }

  // Add Checkout Location
  if(!empty($CheckoutLatitude) && !empty($CheckoutLongitude))
  {
    $OutTime_html .= " <br>
    <a href='javascript:void(0);'
    onclick='openLocationModal(".$CheckoutLatitude.",".$CheckoutLongitude.")'
    style='font-size:12px;color:#184384;'>
    View Location
    </a>";
  }

  $data[] = array(
    "EmployeeName"=>$Name,
    "RecordDate"=>$RecordDate,
    "CheckInTime"=>$InTime_html,
    "CheckOutTime"=>$OutTime_html,
    "Duration"=>$duration,
    "State"=>$State
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