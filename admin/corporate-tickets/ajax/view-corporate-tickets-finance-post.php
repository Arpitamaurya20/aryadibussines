<?php

include("../../controllers/common_controllers.php");
include('../controller/corporate_tickets_controller.php');
include('../../company/controller/company_controller.php');
include('../../branch/controller/branch_controller.php');
include('../../branch-assets/controller/branch_assets_controller.php');
require_once('../../includes/autoloader.inc.php');

session_start();
$conn = _connectodb();

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$searchValue = $_POST['search']['value']; // Search value
$techx_admin = $_GET['techx_admin'];
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
    $ticket_status = $_GET['status'];
    if($ticket_status != "")
    {
        $filter_status = " AND d.Status = '$ticket_status'";
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
    if($EmployeeID!=-1)
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
$filter = $filter.$searchQuery.$filter_city.$filter_company_account.$filter_branch.$filter_state.$filter_city_lead_cities.$filter_status;
$filter = $filter." AND (a.CreatedDate>='$StartDate' AND a.CreatedDate<='$EndDate') ";

$sql_count = " Select COUNT(*) as row_count from corporate_tickets a INNER JOIN branch b ON a.BranchID = b.ID INNER JOIN corporate_tickets_finance c ON a.ID = c.TicketID INNER JOIN corporate_ticket_finance_status d ON c.Status = d.Status".$filter;
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

$sql = "Select a.*,b.BranchSite,b.BranchCity,d.StatusName as FinanceStatus,c.T_TotalPrice,c.C_TotalPrice from corporate_tickets a INNER JOIN branch b ON a.BranchID = b.ID INNER JOIN corporate_tickets_finance c ON a.ID = c.TicketID INNER JOIN corporate_ticket_finance_status d ON c.Status = d.Status".$filter;
//echo $sql;
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
  if($techx_admin == true)
    $Price_Summary = "Customer - &#8377;".$C_TotalPrice."<br>"."Techxpert - &#8377;".$T_TotalPrice;
  else
    $Price_Summary = "&#8377;".$C_TotalPrice;
  $data[] = array(
     "TicketID"=>cleantext($TicketID),
     "Corporate_Branch"=>cleantext($BranchSite),
     "Branch_City"=>cleantext($BranchCity),
     "Type"=>$Type,
     "Message"=>clean_datatable_text($Message),
     "Date_Time"=>$CreatedDate."<br>".$CreatedTime,
     "Status"=>$Status,
     "FinanceStatus"=>$FinanceStatus,
     "Prices"=>cleantext($Price_Summary),
     "View_Details"=>"<a onclick='ViewBookingDetails($ID)'><span class='badge badge-primary cursor-pointer'>View Ticket</span></a>"  
   );
}
## Response
//var_dump($data);
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);
// echo $response;
echo json_encode($response);
?>