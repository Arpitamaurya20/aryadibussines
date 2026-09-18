<?php

include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');

session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value

$filter_date = $_GET['filter_date'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$corporate_tickets_obj = new Corporateticket($conn);   
$corporate_status_array = $corporate_tickets_obj->getCorporateTicketStatusArray('All');
$status_array = array();
foreach($corporate_status_array as $corporate_status)
{
    $Status_i = $corporate_status['Status'];
    $status_array[$Status_i]['color'] = $corporate_status['Color'];
}

$filter_branch = "";
if(isset($_GET['branchName']))
{
    $branch_name = $_GET['branchName'];

    if($branch_name != "")
    {
        $filter_branch = " AND b.ID = '$branch_name'";
    }
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
        $filter_accounts = " AND a.BranchID IN (".$sql_in_account.")";
    }
}




$columnName = "ID";
$columnSortOrder = "DESC";
$UserType = SessionCheck();

$data = array();


$searchQuery = " ";
if($searchValue != ''){
   $searchQuery = " and (a.TicketID like '%".$searchValue."%' or a.Type like '%".$searchValue."%' or a.Message like '%".$searchValue."%' or a.ClientTicketID like '%".$searchValue."%' or b.BranchSite like '%".$searchValue."%' or b.BranchCity like '%".$searchValue."%') ";
}

$filter = " where a.BranchID = b.ID";
$filter_corporate_approval_pending = "";
$filter = $filter.$searchQuery.$filter_company_account.$filter_branch.$filter_accounts.$filter_status.$filter_corporate_approval_pending;
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
//$totalRecords = _getTotalRows($conn,'corporate_tickets',' where 1');
$totalRecords = $totalRecordwithFilter;

$filter = $filter. " ORDER BY a.".$columnName." ".$columnSortOrder;
$filter = $filter." limit ".$row.",".$rowperpage;

$sql = "Select a.*,b.BranchSite,b.BranchCity from corporate_tickets a,branch b ".$filter;
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
  $data[] = array(
     "TicketID"=>$TicketID,
     "Corporate_Branch"=>$BranchSite,
     "Branch_City"=>$BranchCity,
     "Type"=>$Type,
     "Message"=>$Message,
     "Date_Time"=>$CreatedDate."<br>".$CreatedTime,
     "Status"=>$Status_html,
     "View_Details"=>"<a onclick='ViewBookingDetails($ID)'><span class='badge badge-primary cursor-pointer'>View Ticket</span></a>"  
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