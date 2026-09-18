<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();

$corporate_id = $_POST['CorporateID'];
$branch_id = $_POST['BranchID'];
$filter_date = $_POST['filter_date_export'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

if(isset($_POST['city_export']))
    $city_export = $_POST['city_export'];
if(isset($_POST['branch_export']))
    $branch_export = $_POST['branch_export'];
if(isset($_POST['state_export']))
    $state_export = $_POST['state_export'];
if(isset($_POST['status_export']))
    $status_export = $_POST['status_export'];
if(isset($_POST['company_account_export']))
    $company_account_export = $_POST['company_account_export'];

$techx_admin = $_POST['techx_admin'];

$corporate_check = "1";
$status_check = "1";

if($status_export != "")
{
    $status_check = " a.Status = '$status_export'";
}
if($company_account_export != "")
{
    $corporate_check = " a.CorporateID = $company_account_export ";
}
if($corporate_id != -1 && $corporate_id != "")
{
    $corporate_check = " a.CorporateID = $corporate_id ";
}

$output ="";
$city_array = array();
$city_array_raw = _getTableRecords($conn,'citydata',' where 1');
foreach($city_array_raw as $city)
{
    $CityName = $city['CityName'];
    $city_array[$CityName]['CorporateLead'] = $city['CorporateLead'];
}

$employee_object = new Employee($conn);
$employee_array = $employee_object->setEmployeeArray('All');

$company_object = new Company($conn);
$company_array = $company_object->setCompanyArray('All');

$branch_object = new Branch($conn);
$branch_array = $branch_object->setBranchArray('All');

$state_object = new State($conn);
$state_region_array = $state_object->getStateswithRegion('All');


$where = " where $corporate_check AND $status_check AND (a.CreatedDate>='$StartDate' AND a.CreatedDate<='$EndDate') AND a.IsActive = 1 order by a.ID desc";

$corporate_ticket_details = array();
$sql = "Select sr.ID as ServiceReportID,cq.ID as QuotationID,cq.QuotationStatus as QS,a.*,b.T_VisitorNo,b.T_VisitCharge,b.T_MaterialCost,b.T_LabourCost,b.T_TotalPrice,b.C_VisitorNo,b.C_VisitCharge,b.C_MaterialCost,b.C_LabourCost,b.C_TotalPrice,b.Status as FinanceStatus,b.Remarks,c.StatusName from corporate_tickets a INNER JOIN corporate_tickets_finance b ON a.ID = b.TicketID INNER JOIN corporate_ticket_finance_status c ON b.Status = c.Status  LEFT JOIN corporate_ticket_general_service_report sr ON sr.TicketID = a.ID LEFT JOIN corporate_ticket_quotation cq ON a.ID = cq.TicketID $where";
echo $sql;
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($corporate_ticket_details, $row);
        }
    }
} else {
    //echo $sql;
}

if (sizeof($corporate_ticket_details) > 0) 
{
    if($techx_admin)
    {
        $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Client Ticket ID</th>
                         <th>Ticket ID</th>  
                         <th>Corporate Name</th>  
                         <th>Branch Name</th>  
                         <th>City</th>
                         <th>City Lead</th> 
                         <th>State</th>
                         <th>Region</th> 
                         <th>Type</th>    
                         <th>Service</th>  
                         <th>Message</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th> 
                         <th>Close Date</th>  
                         <th>Close Time</th> 
                         <th>CreatedBy</th> 
                         <th>DueDate</th>  
                         <th>Assigned To</th>  
                         <th>Ticket Status</th>
                         <th>Finance Status</th>
                         <th>Visit (T)</th>
                         <th>Visit Charge (T)</th> 
                         <th>Material Cost (T)</th>
                         <th>Labour Cost (T)</th> 
                         <th>Total Cost (T)</th>
                         <th>Visit (C)</th>
                         <th>Visit Charge (C)</th> 
                         <th>Material Cost (C)</th>
                         <th>Labour Cost (C)</th> 
                         <th>Total Cost (C)</th>
                         <th>Service Report</th>
                         <th>Quotation</th>
                         <th>Quotation Status</th>
                    </tr>
  ';
    }
    else
    {
        $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Client Ticket ID</th>
                         <th>Ticket ID</th>  
                         <th>Corporate Name</th>  
                         <th>Branch Name</th>  
                         <th>City</th>
                         <th>City Lead</th> 
                         <th>State</th>
                         <th>Region</th> 
                         <th>Type</th>    
                         <th>Service</th>  
                         <th>Message</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th> 
                         <th>Close Date</th>  
                         <th>Close Time</th> 
                         <th>CreatedBy</th> 
                         <th>DueDate</th>  
                         <th>Assigned To</th>  
                         <th>Status</th>
                         <th>Visit (C)</th>
                         <th>Visit Charge (C)</th> 
                         <th>Material Cost (C)</th>
                         <th>Labour Cost (C)</th> 
                         <th>Total Cost (C)</th>
                         <th>Service Report</th>
                         <th>Quotation</th>
                         <th>Quotation Status</th>
                    </tr>
  ';
    }
    
    foreach ($corporate_ticket_details as $CorporateTicketdata) 
    {
        $to_continue = false;

        $CorporateID = $CorporateTicketdata["CorporateID"];
        
        if(isset($company_array[$CorporateID]))
        {
            $CompanyName = $company_array[$CorporateID]['CompanyName'];
        }
        else
        {
            $CompanyName = "N.A.";
        }


        $BranchID = $CorporateTicketdata["BranchID"];
        $BranchName = "N.A.";
        $City = "N.A.";
        $State = "N.A.";
        $CityLead = "N.A.";
        $AssignEmployeeName = "N.A."; 
        if(isset($branch_array[$BranchID]))
        {
            $BranchName = $branch_array[$BranchID]['BranchName'];
            $City = $branch_array[$BranchID]['BranchCity'];
            
            if($city_export != "")
            {
                if($City != $city_export)
                {
                    $to_continue = true;
                }
            }

            if($branch_export != "")
            {
                if($BranchName != $branch_export)
                {
                    $to_continue = true;
                }
            }
             $Region = "N.A.";
            if(isset($branch_array[$BranchID]['BranchState']))
            {
                $State = $branch_array[$BranchID]['BranchState'];
                if($state_export != "")
                {
                    if($State != $state_export)
                    {
                        $to_continue = true;
                    }
                }

                if(isset($state_region_array[$State]))
                {
                    $Region = $state_region_array[$State]['RegionName'];
                }
            }
            else
            {   
                $to_continue = true;
            }
            
            if(isset($city_array[$City]))
            {
                $CityLead_ID = $city_array[$City]['CorporateLead'];
                
                if(isset($employee_array[$CityLead_ID]))
                {
                    $CityLead = $employee_array[$CityLead_ID]['Name'];
                }
            }

            
        }
        else
        {
            $to_continue = true;
        }

        $AssignID = $CorporateTicketdata["AssignedTo"];
        if(isset($employee_array[$AssignID]))
        {
            $AssignEmployeeName = $employee_array[$AssignID]['Name'];
        }

        $ServiceReport_html = "Not Generated";
        if($CorporateTicketdata['ServiceReportID'] != null)
        {
            $ServiceReportID = $CorporateTicketdata['ServiceReportID'];
            $ServiceReport_html = "<a href='https://techxpertindia.in/admin/corporate-tickets/action/generate_service_report_pdf.php?ServiceReportID=".$ServiceReportID."'>View</a>";
        }

        $Quotation_html = "Not Generated";
        $QuotationStatus = "NA";
        if($CorporateTicketdata['QuotationID'] != null)
        {
            $QuotationID = $CorporateTicketdata['QuotationID'];
            $Quotation_html = "<a href='https://techxpertindia.in/admin/corporate-tickets/action/generate_quotation_pdf.php?QuotationID=".$QuotationID."'>View</a>";
            $QuotationStatus = $CorporateTicketdata['QS'];
        }
        
        if($to_continue)
        {
            continue;
        }
        if($techx_admin)
        {
            $output .= '<tr> 
           <td>' . $CorporateTicketdata['ClientTicketID'] . '</td>  
           <td>' . $CorporateTicketdata['TicketID'] . '</td>  
           <td>' . $CompanyName . '</td>  
           <td>' . $BranchName . '</td>
           <td>' . $City . '</td>
           <td>' . $CityLead . '</td>  
           <td>' . $State . '</td>  
           <td>' . $Region . '</td>
           <td>' . $CorporateTicketdata["Type"] . '</td>  
           <td>' . $CorporateTicketdata["Service"] . '</td>
           <td>' . $CorporateTicketdata["Message"] . '</td>
           <td>' . $CorporateTicketdata["CreatedDate"] . '</td>
           <td>' . $CorporateTicketdata["CreatedTime"] . '</td>
           <td>' . $CorporateTicketdata["CloseDate"] . '</td>
           <td>' . $CorporateTicketdata["CloseTime"] . '</td>
           <td>' . $CorporateTicketdata["CreatedBy"] . '</td>
           <td>' . $CorporateTicketdata["DueDate"] . '</td>
           <td>' . $AssignEmployeeName . '</td>
           <td>' . $CorporateTicketdata["Status"] . '</td>
           <td>' . $CorporateTicketdata["StatusName"] . '</td>  
           <td>' . $CorporateTicketdata["T_VisitorNo"] . '</td>
           <td>' . $CorporateTicketdata["T_VisitCharge"] . '</td>
           <td>' . $CorporateTicketdata["T_MaterialCost"] . '</td>
           <td>' . $CorporateTicketdata["T_LabourCost"] . '</td>
           <td>' . $CorporateTicketdata["T_TotalPrice"] . '</td>
           <td>' . $CorporateTicketdata["C_VisitorNo"] . '</td>
           <td>' . $CorporateTicketdata["C_VisitCharge"] . '</td>
            <td>' . $CorporateTicketdata["C_MaterialCost"] . '</td>
           <td>' . $CorporateTicketdata["C_LabourCost"] . '</td>
           <td>' . $CorporateTicketdata["C_TotalPrice"] . '</td>
           <td>' . $ServiceReport_html . '</td>
           <td>' . $Quotation_html . '</td>
           <td>' . $QuotationStatus . '</td>
            </tr>
       ';
    }
    else
    {
         $output .= '<tr> 
           <td>' . $CorporateTicketdata['ClientTicketID'] . '</td>  
           <td>' . $CorporateTicketdata['TicketID'] . '</td>  
           <td>' . $CompanyName . '</td>  
           <td>' . $BranchName . '</td>
           <td>' . $City . '</td>
           <td>' . $CityLead . '</td>  
           <td>' . $State . '</td>  
           <td>' . $Region . '</td>
           <td>' . $CorporateTicketdata["Type"] . '</td>  
           <td>' . $CorporateTicketdata["Service"] . '</td>
           <td>' . $CorporateTicketdata["Message"] . '</td>
           <td>' . $CorporateTicketdata["CreatedDate"] . '</td>
           <td>' . $CorporateTicketdata["CreatedTime"] . '</td>
           <td>' . $CorporateTicketdata["CloseDate"] . '</td>
           <td>' . $CorporateTicketdata["CloseTime"] . '</td>
           <td>' . $CorporateTicketdata["CreatedBy"] . '</td>
           <td>' . $CorporateTicketdata["DueDate"] . '</td>
           <td>' . $AssignEmployeeName . '</td>
           <td>' . $CorporateTicketdata["Status"] . '</td> 
           <td>' . $CorporateTicketdata["C_VisitorNo"] . '</td>
           <td>' . $CorporateTicketdata["C_VisitCharge"] . '</td>
            <td>' . $CorporateTicketdata["C_MaterialCost"] . '</td>
           <td>' . $CorporateTicketdata["C_LabourCost"] . '</td>
           <td>' . $CorporateTicketdata["C_TotalPrice"] . '</td>
            <td>' . $ServiceReport_html . '</td>
           <td>' . $Quotation_html . '</td>
           <td>' . $QuotationStatus . '</td>
            </tr>
       ';
    }
    }
} 
else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>