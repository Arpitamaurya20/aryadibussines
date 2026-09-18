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
$epoch_time = $_POST['epoch_time'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$city_export = $_POST['city_export'];
$branch_export = $_POST['branch_export'];
$state_export = $_POST['state_export'];
$status_export = $_POST['status_export'];
$company_account_export = $_POST['company_account_export'];

$corporate_check = "1";
$status_check = "1";

if($status_export != "")
{
    $status_check = " c.Status = '$status_export'";
}
if($company_account_export != "")
{
    $corporate_check = " c.CorporateID = $company_account_export ";
}
if($corporate_id != "" && $corporate_id != -1)
{
    $corporate_check = " c.CorporateID = $corporate_id ";
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


$where = " where $corporate_check AND $status_check AND (c.CreatedDate>='$StartDate' AND c.CreatedDate<='$EndDate') AND c.IsActive = 1 order by b.ID DESC";

$corporate_ticket_details = array();
$sql = "Select a.*,b.T_VisitorNo,b.T_VisitCharge,b.T_MaterialCost,b.T_LabourCost,b.T_TotalPrice,b.C_VisitorNo,b.C_VisitCharge,b.C_MaterialCost,b.C_LabourCost,b.C_TotalPrice,b.Status as FinanceStatus,b.Remarks,c.StatusName from corporate_tickets a INNER JOIN corporate_tickets_finance b ON a.ID = b.TicketID INNER JOIN corporate_ticket_finance_status c ON b.Status = c.Status $where";

$sql = "SELECT a.QuotationID,a.Qty,a.PerItemPrice,a.TotalPrice,a1.LineItemName,a1.ARCItem,a1.Type as ItemType,a1.Category as ItemCategory,a1.Make,a1.HSN,a1.ARCCode,a1.UoM,a1.Tax,b.QuotationStatus,b.QuotationDate,c.ClientTicketID,c.TicketID,c.CorporateID,c.BranchID,c.Type,c.Service,c.Message,c.CreatedDate,c.CreatedTime,c.DueDate,c.CloseDate,c.CloseTime,c.CreatedBy,c.AssignedTo,c.Status FROM `corporate_ticket_quotation_items` a INNER JOIN corporate_rate_card a1 ON a.LineItemID = a1.ID INNER JOIN corporate_ticket_quotation b ON a.QuotationID = b.ID INNER JOIN corporate_tickets c ON b.TicketID = c.ID $where";
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

if (sizeof($corporate_ticket_details) > 0) {
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
                         <th>Ticket Created Date</th>  
                         <th>Ticket Created Time</th> 
                         <th>Ticket Close Date</th>  
                         <th>Ticket Close Time</th> 
                         <th>Ticket CreatedBy</th> 
                         <th>Ticket DueDate</th>  
                         <th>Ticket Assigned To</th>  
                         <th>Ticket Status</th>
                         <th>Quotation Status</th>
                         <th>Line Item</th>
                         <th>ARC Item</th>
                         <th>ARC Code</th>
                         <th>Type</th>
                         <th>Make</th>
                         <th>Category</th>
                         <th>HSN</th>
                         <th>Qty</th>
                         <th>UoM</th>
                         <th>Per Item Price</th>
                         <th>Total Price</th>
                         <th>Tax</th>
                         <th>Price Including Tax</th>
                         <th>Service Report</th>
                         <th>Quotation</th>
                    
                    </tr>
  ';
    foreach ($corporate_ticket_details as $CorporateTicketdata) {
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
        
        //new things---
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
            $QuotationStatus = $CorporateTicketdata['QuotationStatus'];
        }

        //
        if($to_continue)
        {
            continue;
        }
        $ARCItem = "No";
        if($CorporateTicketdata["ARCItem"] == 1)
        {
        	$ARCItem = "Yes";
        }

        $taxAmount = (floatval($CorporateTicketdata["TotalPrice"]) * floatval($CorporateTicketdata["Tax"])) / 100;
        $totalPrice = floatval($CorporateTicketdata["TotalPrice"]) + $taxAmount;
        $totalPrice = round($totalPrice, 2);

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

       <td>' . $CorporateTicketdata["QuotationStatus"] . '</td>  
       <td>' . $CorporateTicketdata["LineItemName"] . '</td>
       <td>' . $ARCItem . '</td>
       <td>' . $CorporateTicketdata["ARCCode"] . '</td>
       <td>' . $CorporateTicketdata["ItemType"] . '</td>
       <td>' . $CorporateTicketdata["Make"] . '</td>
       <td>' . $CorporateTicketdata["ItemCategory"] . '</td>
       <td>' . $CorporateTicketdata["HSN"] . '</td>
        <td>' . $CorporateTicketdata["Qty"] . '</td>
       <td>' . $CorporateTicketdata["UoM"] . '</td>
       <td>' . $CorporateTicketdata["PerItemPrice"] . '</td>
       <td>' . $CorporateTicketdata["TotalPrice"] . '</td>
       <td>' . $CorporateTicketdata["Tax"] . '</td>
       <td>' . $totalPrice . '</td>
        <td>' . $ServiceReport_html . '</td>
           <td>' . $Quotation_html . '</td>
           
        </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

$file_name = "../report_finance".$epoch_time.".xls";
$myfile = fopen($file_name, "w");
fwrite($myfile, $output);
fclose($myfile);
?>