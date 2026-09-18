<?php

include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../controller/ticket_escalation_controller.php');
include('../controller/state_manager_verification_controller.php');
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

$smvCanAccess = smv_canUserVerifyTicket($_SESSION);
$UserType = SessionCheck();

$state_object = new State($conn);
$Employee_ID = $state_object->resolveEmployeeIdFromSession($_SESSION);
$filter_state_scoped = "";
$state_scoped = false;
$allowed_state_names = array();
if ($Employee_ID > 0 && !$TicketManager && !$Procurement) {
    if ($state_object->employeeHasStateScope($Employee_ID)) {
        $state_scoped = true;
        $allowed_state_names = $state_object->getAllowedStateNamesForEmployee($Employee_ID);
        $filter_state_scoped = $state_object->buildBranchStateScopeSql($Employee_ID, 'b');
    }
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

$filter_status = "";
if(isset($_GET['status']))
{
    $ticket_status = trim((string) $_GET['status']);
    if($ticket_status != "")
    {
        if ($ticket_status === 'Escalated') {
            $filter_status = te_buildEscalatedStatusFilterSql('a');
        } else {
            $ticket_status = mysqli_real_escape_string($conn, $ticket_status);
            $filter_status = " AND a.Status = '$ticket_status'";
        }
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
    if($EmployeeID!=-1 && !$TicketManager && !$StateManager && !$Procurement && !$state_scoped)
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

$filter_corporate_approval_pending = "";


$columnName = "ID";
$columnSortOrder = "DESC";

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
   $searchQuery = " and (
    a.ID like '%".$searchValue."%'
    OR a.TicketID like '%".$searchValue."%'
    OR a.ClientTicketID like '%".$searchValue."%'
    OR a.Type like '%".$searchValue."%'
    OR a.Message like '%".$searchValue."%'
    OR b.BranchSite like '%".$searchValue."%'
    OR b.BranchCity like '%".$searchValue."%'
) ";

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
$filter = $filter.$searchQuery.$filter_city.$filter_company_account.$filter_branch.$filter_state.$filter_city_lead_cities.$filter_state_scoped.$filter_status.$filter_finance_not_placed.$filter_category.$filter_corporate_approval_pending;
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
  if ($Status !== 'Escalated' && te_getOpenEscalation($conn, (int) $ID)) {
    $Status = 'Escalated';
  }
  if(isset($status_array[$Status]))
  {
    $bg_color = $status_array[$Status]['color'];
    $Status_html = "<span class='badge' style='background:#f1f5f9; color:#475569; border: 1px solid #cbd5e1; font-weight:600; padding: 4px 8px;'>".$Status."</span>";
  }
  else
  {
    $Status_html = $Status;
  }

  $isVerified = smv_isTicketVerifiedByStateManager($conn, $ID);
  $tickIcon = smv_renderVerificationTickIcon($isVerified);
  $TicketID_html = "<span class='smv-ticket-id-cell'>".$tickIcon."&nbsp;".cleantext($TicketID)."&nbsp;<a onclick='ViewBookingDetails($ID)'><i class='fal fa-external-link'></i></a></span>";
  $smVerificationHtml = smv_renderVerificationActionButton($ID, $TicketID, $Type, $isVerified, $smvCanAccess, $Status);

  $rowData = array(
     "TicketID"=>$TicketID_html,
     "ClientTicketID"=>cleantext($ClientTicketID),
     "Corporate_Branch"=>$BranchSite_html,
     "Type"=>cleantext($Type),
     "Message"=>clean_datatable_text($Message),
     "Date_Time"=>$CreatedDate."<br>".$CreatedTime,
     "Status"=>$Status_html,
     "View_Details"=>"<a onclick='ViewBookingDetails($ID)' class='btn btn-sm btn-outline-secondary py-1 px-2 font-weight-bold'>View Ticket</a>",
     "Delete"=>"<a onclick='DeleteTicket($ID)'><i class='fas fa-trash-alt'></i></a>"
   );

  if ($smvCanAccess) {
      $rowData["SM_Verification"] = $smVerificationHtml;
  }

  $data[] = $rowData;
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
