<?php
include("../../controllers/common_controllers.php");
include('../controller/ppm_controller.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();

$filter_date = $_POST['filter_date_export'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$status_export = $_POST['status_export'];
$company_account_export = $_POST['company_account_export'];
$state_export = $_POST['state_export'];

$export_date_type = $_POST['date_type_export'];

$status_check = 1;
if($status_export != "")
{
    $status_check = " a.Status = '$status_export'";
}
$corporate_check = 1;
if($company_account_export != "")
{
    $corporate_check = " a.CorporateID = $company_account_export ";
}
$state_check = 1;
if($state_export != "")
{
    $state_check = " c.BranchState ='$state_export' ";
}


$filter_accounts = "1";
if(isset($_POST['EmployeeID']))
{
    $EmployeeID = $_POST['EmployeeID'];
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
        $filter_accounts = " a.BranchID IN (".$sql_in_account.")";
    }
}


$output ="";
$where = " where $corporate_check AND $status_check AND $filter_accounts AND $state_check AND (a.PPMDate>='$StartDate' AND a.PPMDate<='$EndDate') AND a.IsActive = 1 order by a.ID desc";
//$ppm_ticket_details = _getTableRecords($conn,'ppm_tickets', $where);
$sql = " Select a.*,Em.Name As EmployeeName, b.CompanyName,c.BranchSite,c.BranchState,d.EquipmentName,e.ID as ServiceReportID from ppm_tickets a INNER JOIN company b ON a.CorporateID = b.ID INNER JOIN branch c ON a.BranchID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID LEFT JOIN ppm_ticket_general_service_report e ON e.TicketID = a.ID LEFT JOIN employees Em ON a.AssignedTo = Em.ID".$where;
$core = new Core();
$ppm_ticket_details = $core->_getSQLRecords($conn,$sql);


if($export_date_type == "close_date")
{
    $where = " where $corporate_check AND $status_check AND $filter_accounts AND $state_check AND (a.CloseDate>='$StartDate' AND a.CloseDate<='$EndDate') AND a.IsActive = 1 order by a.ID desc";
    //$ppm_ticket_details = _getTableRecords($conn,'ppm_tickets', $where);
    $sql = " Select a.*,Em.Name As EmployeeName, b.CompanyName,c.BranchSite,c.BranchState,d.EquipmentName,e.ID as ServiceReportID from ppm_tickets a INNER JOIN company b ON a.CorporateID = b.ID INNER JOIN branch c ON a.BranchID = c.ID INNER JOIN branch_assets d ON a.BranchAssetID = d.ID LEFT JOIN ppm_ticket_general_service_report e ON e.TicketID = a.ID LEFT JOIN employees Em ON a.AssignedTo = Em.ID".$where;
    $core = new Core();
    $ppm_ticket_details = $core->_getSQLRecords($conn,$sql);
}

if ($ppm_ticket_details > 0) 
{

    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Ticket ID</th>  
                         <th>Corporate</th>  
                         <th>Branch</th>  
                         <th>Branch Asset</th>
                         <th>Branch State</th>  
                         <th>PPMDate</th>  
                         <th>Close Date</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th>  
                         <th>CreatedBy</th> 
                         <th>DueDate</th>    
                         <th>Status</th>
                         <th>Assigned To</th>
                         <th>Service Report</th>  
                    </tr>
  ';
    foreach ($ppm_ticket_details as $PPMTicketdata) 
    {
       /* $CorporateID = $PPMTicketdata["CorporateID"];
        $where = " where ID = $CorporateID";
        $CorporateData = _getTableDetails($conn,'company', $where);*/
        $CorporateName = $PPMTicketdata['CompanyName'];

       /* $BranchID = $PPMTicketdata["BranchID"];
        $where = " where ID = $BranchID";
        $BranchData = _getTableDetails($conn,'branch', $where);*/
        $BranchName = $PPMTicketdata['BranchSite'];
/*
        $BranchAssetID =  $PPMTicketdata["BranchAssetID"] ;
        $where = " where ID = $BranchAssetID";
        $BranchAssetsData = _getTableDetails($conn,'branch_assets', $where);*/
        $BranchAssetsName = $PPMTicketdata['EquipmentName'];
        $BranchState = $PPMTicketdata['BranchState'];

        $ServiceReport_html = "Not Generated";
        if($PPMTicketdata['ServiceReportID'] != null)
        {
            $ServiceReportID = $PPMTicketdata['ServiceReportID'];
            $ServiceReport_html = "<a href='https://techxpertindia.in/admin/corporate-tickets/action/generate_ppm_service_report_pdf.php?ServiceReportID=".$ServiceReportID."'>View</a>";
        }

        $output .= '<tr>  
       <td>' . $PPMTicketdata["TicketID"] . '</td>  
       <td>' . $CorporateName . '</td>  
       <td>' . $BranchName . '</td>  
       <td>' . $BranchAssetsName . '</td>
       <td>' . $BranchState . '</td>
       <td>' . $PPMTicketdata["PPMDate"] . '</td>
       <td>' . $PPMTicketdata["CloseDate"] . '</td>
       <td>' . $PPMTicketdata["CreatedDate"] . '</td>
       <td>' . $PPMTicketdata["CreatedTime"] . '</td>
       <td>' . $PPMTicketdata["CreatedBy"] . '</td>
       <td>' . $PPMTicketdata["DueDate"] . '</td>
       <td>' . $PPMTicketdata["Status"] . '</td>
       <td>' . $PPMTicketdata["EmployeeName"] . '</td>
       <td>' . $ServiceReport_html . '</td>
                    </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}
$file = "../report.xls";
if (file_exists($file)) 
{
    if (unlink($file)) 
    {
    }
} 

$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>