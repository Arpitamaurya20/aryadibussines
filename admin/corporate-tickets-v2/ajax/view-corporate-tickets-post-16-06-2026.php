<?php

include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../../company/controller/company_controller.php');
include('../../branch/controller/branch_controller.php');
include('../../branch-assets/controller/branch_assets_controller.php');
require_once('../../includes/autoloader.inc.php');


session_start();
$conn = _connectodb();

$corporate_tickets_obj = new Corporateticket($conn);   
$corporate_status_array = $corporate_tickets_obj->getCorporateTicketStatusArray('All');
$status_array = array();
foreach($corporate_status_array as $corporate_status)
{
    $Status_i = $corporate_status['Status'];
    $status_array[$Status_i]['color'] = $corporate_status['Color'];
}

$StateManager = false;
if(CheckRole($_SESSION,"State Corporate Lead") == true )
{
    $StateManager = true;
}
if(isset($_SESSION['Roles']['EmployeeID']))
{
    $Employee_ID = $_SESSION['Roles']['EmployeeID'];
}
$TicketManager = false;
if(CheckRole($_SESSION,"Ticket Manager") == true )
{
    $TicketManager = true;
}
$CFO = false;
if(CheckRole($_SESSION,"CFO") == true )
{
    $TicketManager = true;
}

$Procurement = false;
if(CheckRole($_SESSION,"Procurement") == true )
{
    $Procurement = true;
}

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];
$filter_city = "";
if(isset($_GET['city']))
{
    $city = $_GET['city'];
    if($city != "")
    {
        $filter_city = " AND b.BranchCity = '$city'";
    }
}

$filter_branch = "";
if(isset($_GET['branchName']))
{
    $branch_name = $_GET['branchName'];
    if($branch_name != "")
    {
        $filter_branch = " AND b.BranchSite = '$branch_name'";
    }
}

$filter_state = "";
if(isset($_GET['stateName']))
{
    $stateName = $_GET['stateName'];
    if($stateName != "")
    {
        $filter_state = " AND b.BranchState = '$stateName'";
    }
}

$filter_state_manager = "";
if($StateManager)
{
    $state_object = new State($conn);
    $state_array = $state_object->getStatesMapped_StateLead($Employee_ID);
    $state_array_mapped = array();
    foreach($state_array as $state_mapped)
    {
        array_push($state_array_mapped,$state_mapped['StateName']);
    }
    $sql_in_state_string = "'" . implode("', '", $state_array_mapped) . "'";
    $filter_state_manager = " AND b.BranchState IN ($sql_in_state_string)";
}

$filter_status = "";
if(isset($_GET['status']))
{
    $ticket_status = $_GET['status'];
    if($ticket_status != "")
    {
        $filter_status = " AND a.Status = '$ticket_status'";
    }
}

$filter_company_account = "";
if(isset($_GET['filter_company_id']))
{
    $filter_company_id = $_GET['filter_company_id'];
    if($filter_company_id != "")
    {
        $filter_company_account = " AND a.CorporateID = '$filter_company_id'";
    }
}

$filter_city_lead_cities = "";
if(isset($_GET['EmployeeID']))
{
    $EmployeeID = $_GET['EmployeeID'];
    if($EmployeeID!=-1 && !$TicketManager && !$StateManager && !$Procurement)
    {
        // Get cities mapped to Employees
        $city = new City($conn);
        $cities_array_mapped_raw = $city->getMappedCitiesofCityLead($EmployeeID,'Corporate');
        $cities_array_mapped = array();
        foreach($cities_array_mapped_raw as $city_mapped)
        {
            array_push($cities_array_mapped,$city_mapped['CityName']);
        }
        $sql_in_string = "'" . implode("', '", $cities_array_mapped) . "'";
        $filter_city_lead_cities = " AND b.BranchCity IN (".$sql_in_string.")";
    }
}

$filter_finance_not_placed = "";
if(isset($_GET['finance_not_placed']))
{
    $filter_finance_not_placed = " AND a.ID NOT IN (Select TicketID from corporate_tickets_finance)";
}

$filter_category = "";
if (isset($_GET['category_id'])) {
    $category_id = $_GET['category_id'];
    if ($category_id != "") {
        $filter_category = " AND a.Service = '$category_id'";
    }
}


$columnName = "ID";
$columnSortOrder = "DESC";
$UserType = SessionCheck();

$data = array();

$CorporateID = -1;
$BranchID = -1;
$techx_admin = true;
if($UserType == "Corporate Admin")
{
    $CorporateID = $_SESSION['Roles']['CorporateID'];
    $techx_admin = false;
}
if($UserType == "Corporate Branch User")
{
    $CorporateID = $_SESSION['Roles']['CorporateID'];
    $BranchID = $_SESSION['Roles']['BranchID'];
    $techx_admin = false;
}
if($UserType == "Corporate User")
{
    $CorporateID = $_SESSION['Roles']['CorporateID'];
    $techx_admin = false;
}

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.TicketID like '%".$searchValue."%' or a.Type like '%".$searchValue."%' or a.Message like '%".$searchValue."%' or a.ClientTicketID like '%".$searchValue."%' or b.BranchSite like '%".$searchValue."%' or b.BranchCity like '%".$searchValue."%') ";
}

$filter = " where a.BranchID = b.ID";
if($CorporateID != -1)
{
    $filter = $filter." and CorporateID = $CorporateID";
}
if($BranchID != -1)
{
     $filter = $filter." and BranchID = $BranchID";
}
$filter = $filter.$searchQuery.$filter_city.$filter_company_account.$filter_branch.$filter_state.$filter_city_lead_cities.$filter_status.$filter_finance_not_placed.$filter_category;
$filter = $filter." AND (a.CreatedDate>='$StartDate' AND a.CreatedDate<='$EndDate') ";

$sql_count = " Select COUNT(*) as row_count from corporate_tickets a INNER JOIN branch b ON a.BranchID = b.ID".$filter;
$result_Count = mysqli_query($conn, $sql_count);
if ($result_Count) 
{
    $row_count_result = $result_Count->fetch_assoc();
    $row_count = $row_count_result['row_count'];
    $totalRecordwithFilter = $row_count;
}
else
{
    $tf_where = " where (CreatedDate>='$StartDate' AND CreatedDate<='$EndDate')";
    $totalRecordwithFilter = _getTotalRows($conn,'corporate_tickets',$tf_where);
}

## Total number of record with filtering
$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');

$filter = $filter. " ORDER BY a.".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;

//$sql = "Select a.*,b.BranchSite,b.BranchCity,c. from corporate_tickets a,branch b ".$filter;
$sql = "Select a.*,b.BranchSite,b.BranchCity,c.CompanyName from corporate_tickets a INNER JOIN branch b ON a.BranchID = b.ID INNER JOIN company c ON a.CorporateID = c.ID ".$filter;
$corporate_tickets = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($corporate_tickets, $row);
        }
    }
} else {
    //echo $sql;
}



foreach ($corporate_tickets as $corporate_ticket) {
  extract($corporate_ticket);
  $CorporateName = cleantext($CompanyName);
  $BranchSite = cleantext($BranchSite);
  $BranchCity = cleantext($BranchCity);
  $BranchSite_html = $CorporateName."<br>".$BranchSite."<br>".$BranchCity;
  $Status = cleantext($Status);
  if(isset($status_array[$Status]))
  {
    $bg_color = $status_array[$Status]['color'];
    $Status_html = "<span class='badge cursor-pointer' style='background:$bg_color;color:#fff;'>".$Status."</span>";
  }
  else
  {
    $Status_html = $Status;
  }
  
    $TicketID_html = cleantext($TicketID)."&nbsp;<a onclick='ViewBookingDetails($ID)'><i class='fal fa-external-link'></i></a>";
  $data[] = array(
     "TicketID"=>$TicketID_html,
     "ClientTicketID"=>cleantext($ClientTicketID),
     "Corporate_Branch"=>$BranchSite_html,
     "Type"=>cleantext($Type),
     "Message"=>clean_datatable_text($Message),
     "Date_Time"=>$CreatedDate."<br>".$CreatedTime,
     "Status"=>$Status_html,
     "View_Details"=>"<a onclick='ViewBookingDetails($ID)'><span class='badge badge-primary cursor-pointer'>View Ticket</span></a>",
     "Delete"=>"<a onclick='DeleteTicket($ID)'><i class='fas fa-trash-alt'></i></a>" 
   );
}
## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecordwithFilter,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);
// echo $response;
echo json_encode($response);
?>
