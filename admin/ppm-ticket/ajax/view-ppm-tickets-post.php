<?php

include("../../controllers/common_controllers.php");
include('../controller/ppm_controller.php');
include('../controller/state_manager_verification_controller.php');
include('../../branch/controller/branch_controller.php');
include('../../company/controller/company_controller.php');
include('../../branch-assets/controller/branch_assets_controller.php');
require_once('../../includes/autoloader.inc.php');
session_start();
$conn = _connectodb();

$ppm_ticket_obj = new Ppmtickets($conn);   
$ppm_status_array = $ppm_ticket_obj->getPPMTicketStatusArray('All');
$status_array = array();
foreach($ppm_status_array as $ppm_status)
{
    $Status_i = $ppm_status['Status'];
    $status_array[$Status_i]['color'] = $ppm_status['Color'];
}

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$filter =  " where a.IsActive = 1 ";

$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$filter = $filter." AND (a.PPMDate>='$StartDate' AND a.PPMDate<='$EndDate') ";

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

$filter_branch = "";
if(isset($_GET['BranchID']))
{
    $filter_branch_id = $_GET['BranchID'];
    if($filter_branch_id != "" && $filter_branch_id != -1)
    {
        $filter_branch = " AND a.BranchID = '$filter_branch_id'";
    }
}

$filter_state = "";
if(isset($_GET['SateName']))
{
    $state_name = $_GET['SateName'];
    if($state_name != "" && $state_name != -1)
    {
        $filter_state = " AND b.BranchState = '$state_name'";
    }
}

$filter_accounts = "";
if(isset($_GET['EmployeeID']))
{
    $EmployeeID = $_GET['EmployeeID'];
    if($EmployeeID!=-1)
    {
        // Get cities mapped to Employees
        $branch = new Branch($conn);
        $branches_array_mapped_raw = $branch->getMappedAccountBranchesofAccountBranchManager($EmployeeID);
        $branches_array_mapped = array();
        foreach($branches_array_mapped_raw as $branches_mapped)
        {
            array_push($branches_array_mapped,$branches_mapped['ID']);
        }
        $sql_in_account = "'" . implode("', '", $branches_array_mapped) . "'";
        $filter_accounts = " AND a.BranchID IN (".$sql_in_account.") AND a.Status != 'Planned'";
    }
}

$columnName = "ID";
$columnSortOrder = "DESC";
$UserType = SessionCheck();
$psmvCanAccess = psmv_canUserVerifyTicket($_SESSION);
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

$data = array();

$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.TicketID like '%".$searchValue."%' or a.PPMDate like '%".$searchValue."%' or b.BranchSite like '%".$searchValue."%' or c.CompanyName like '%".$searchValue."%' or d.EquipmentName like '%".$searchValue."%' or a.Status like '%".$searchValue."%') ";
}
if($CorporateID != -1)
{
    $filter = $filter." and a.CorporateID = $CorporateID and a.Status != 'Planned'";
}
if($BranchID != -1)
{
     $filter = $filter." and a.BranchID = $BranchID and a.Status != 'Planned'";
}
$filter = $filter.$searchQuery.$filter_status.$filter_accounts.$filter_company_account.$filter_branch.$filter_state;
$sql_count = " Select COUNT(*) as row_count from ppm_tickets a INNER JOIN branch b ON a.BranchID = b.ID INNER JOIN company c ON a.CorporateID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID ".$filter;
$result_Count = mysqli_query($conn, $sql_count);
if ($result_Count) 
{
    $row_count_result = $result_Count->fetch_assoc();
    $row_count = $row_count_result['row_count'];
    $totalRecordwithFilter = $row_count;
}


## Total number of record with filtering
$totalRecords = _getTotalRows($conn,'ppm_tickets',' where IsActive = 1');
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');

$filter = $filter. " ORDER BY a.".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;

$sql = "Select a.*,b.BranchSite,c.CompanyName,d.EquipmentName from ppm_tickets a INNER JOIN branch b ON a.BranchID = b.ID INNER JOIN company c ON a.CorporateID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID ".$filter;
//echo $sql;
$corporate_ppm_tickets = array();
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($corporate_ppm_tickets, $row);
        }
    }
} else {
    //echo $sql;
}



foreach ($corporate_ppm_tickets as $ppm_ticket) {
  extract($ppm_ticket);
  if(isset($status_array[$Status]))
  {
    $bg_color = $status_array[$Status]['color'];
    $Status_html = "<span class='badge cursor-pointer' style='background:$bg_color;color:#fff;'>".$Status."</span>";
  }
  else
  {
    $Status_html = $Status;
  }
  $View_Details = "<a onclick='ViewPPMTicketDetails($ID)'><span class='badge badge-primary cursor-pointer'>View Ticket</span></a>";
  $isVerified = psmv_isTicketVerifiedByStateManager($conn, $ID);
  $tickIcon = psmv_renderVerificationTickIcon($isVerified);
  $TicketID_html = "<span class='psmv-ticket-id-cell'>".$tickIcon."&nbsp;".cleantext($TicketID)."&nbsp;<a onclick='ViewPPMTicketDetails($ID)'><i class='fal fa-external-link'></i></a></span>";
  $smVerificationHtml = psmv_renderVerificationActionButton($ID, $TicketID, $isVerified, $psmvCanAccess, $Status);
  $actionHtml = $psmvCanAccess ? $smVerificationHtml : '-';

  $rowData = array(
     "TicketID"=>$TicketID_html,
     "BranchAsset"=>cleantext($EquipmentName),
     "Corporate"=>cleantext($CompanyName),
     "Branch"=>cleantext($BranchSite),
     "PPM_Date"=>$PPMDate,
     "Status"=>$Status_html,
     "View_Ticket"=>$View_Details,
     "Action"=>$actionHtml
   );

  $data[] = $rowData;
}
## Response
//var_dump($data);
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecordwithFilter,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);
// echo $response;
echo json_encode($response);
?>