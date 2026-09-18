<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
require_once('../controller/ticket_escalation_controller.php');
$conn = _connectodb();
$config_obj = new Config($conn);

setTimeZone();
$UserType = SessionCheck();
$finance_values_include = false;
if($UserType == "Admin" && 0)
{
    $finance_values_include = true;
}
$corporate_id = $_POST['CorporateID'];
$conf_data = array();
$conf_data['CorporateID'] = $corporate_id;
$fields_data = $config_obj->getAllConfigurableFields($conf_data);
$branch_id = $_POST['BranchID'];
$filter_date = $_POST['filter_date_export'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$city_export = $_POST['city_export'];
$branch_export = $_POST['branch_export'];
$address_export = "";
if(isset($_POST['address_export']))
    $address_export = $_POST['address_export'];
$state_export = $_POST['state_export'];
$status_export = $_POST['status_export'];
$company_account_export = $_POST['company_account_export'];
$export_finance_not_placed = $_POST['export_finance_not_placed'];

$city_lead = "no";
$Employee_ID = -1;
$sql_in_string = "";
if(isset($_SESSION['Roles']['EmployeeRoles']))
{
    $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
    foreach($EmployeeRoles as $E_Role)
    {
        if($E_Role == "City Corporate Lead")
        {
            $city_lead = "yes";
            if(isset($_SESSION['Roles']['EmployeeID']))
            {
                $Employee_ID = $_SESSION['Roles']['EmployeeID'];
            }
        }
    }
}


$corporate_check = "1";
$branch_check = "1";
$status_check = "1";

if($corporate_id != -1)
{
    $corporate_check = " CorporateID = $corporate_id ";
}
if($branch_id != -1)
{
    $branch_check = " BranchID = $branch_id ";
}

if($status_export != "")
{
    $status_export = trim((string) $status_export);
    if ($status_export === 'Escalated') {
        $status_check = ltrim(te_buildEscalatedStatusFilterSql('corporate_tickets'), ' AND');
    } else {
        $status_export = mysqli_real_escape_string($conn, $status_export);
        $status_check = " Status = '$status_export'";
    }
}
if($company_account_export != "")
{
    $corporate_check = " CorporateID = $company_account_export ";
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

$finance_not_placed_filter = "1";
if($export_finance_not_placed == 1)
{
    $finance_not_placed_filter = "ID NOT IN (Select TicketID from corporate_tickets_finance)";
}

$corporate_approval_pending_filter = "1";

$where = " where $corporate_check AND $branch_check AND $status_check AND $finance_not_placed_filter AND $corporate_approval_pending_filter AND (CreatedDate>='$StartDate' AND CreatedDate<='$EndDate') AND IsActive = 1 order by ID desc";

$finance_th = "";
if($finance_values_include)
{
    $finance_th = "<th>Customer Cost</th>";
    $finance_th .= "<th>TechXpert Cost</th>";
}

// Configurable Fields
$cf_th = "";
$cf_td = array();
foreach($fields_data as $field)
{
    $cf_th .= "<th>".$field['Title']."</th>";
    array_push($cf_td,$field['Param']);
}

$corporate_ticket_details = _getTableRecords($conn,'corporate_tickets', $where);
if ($corporate_ticket_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Client Ticket ID</th>
                         <th>Ticket ID</th>  
                         <th>Corporate Name</th>  
                         <th>Branch Name</th>  
                         <th>Branch Address</th>
                         <th>Branch Account Manager</th>
                         <th>City</th>
                         <th>City Lead</th> 
                         <th>State</th>
                         <th>Region</th>    
                         <th>Type</th>'.$cf_th.'  
                         <th>Service</th>  
                         <th>Message</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th>  
                         <th>Close Date</th>  
                         <th>Close Time</th>
                         <th>CreatedBy</th> 
                         <th>DueDate</th>  
                         <th>Assigned To</th>  
                         <th>Status</th>'.
                         $finance_th.'  
                    </tr>
  ';
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
        $BranchAddress1 = "N.A.";
        $City = "N.A.";
        $State = "N.A.";
        $CityLead = "N.A.";
        $AssignEmployeeName = "N.A."; 
        $AssignBranchManager = "N.A.";
        if(isset($branch_array[$BranchID]))
        {
            $BranchName = $branch_array[$BranchID]['BranchName'];
            $BranchAddress1 = $branch_array[$BranchID]['BranchAddress1'];
            $City = $branch_array[$BranchID]['BranchCity'];
            $BranchAccountManager = $branch_array[$BranchID]['BranchAccountManager'];
            if(isset($employee_array[$BranchAccountManager]))
            {
                $AssignBranchManager = $employee_array[$BranchAccountManager]['Name'];
            }
            
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
                if($city_lead == "yes" && $Employee_ID != $CityLead_ID)
                {
                    $to_continue = true;
                }
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
        
        if($to_continue)
        {
            continue;
        }
        $finance_td = "";
        if($finance_values_include)
        {
            $finance_td = "<td>0</td>";
            $finance_td .= "<td>0</td>";
        }
        $td_array = "";
        foreach($cf_td as $cf)
        {
            $td_array .= "<td>".$CorporateTicketdata[$cf]."</td>";
        }
    $output .= '<tr> 
       <td>' . $CorporateTicketdata['ClientTicketID'] . '</td>  
       <td>' . $CorporateTicketdata['TicketID'] . '</td>  
       <td>' . $CompanyName . '</td>  
       <td>' . $BranchName . '</td>
       <td>' . $BranchAddress1. '</td>
       <td>' . $AssignBranchManager. '</td>
       <td>' . $City . '</td>
       <td>' . $CityLead . '</td>  
       <td>' . $State . '</td>  
       <td>' . $Region . '</td>
       <td>' . $CorporateTicketdata["Type"] . '</td> '.$td_array.'
       <td>' . $CorporateTicketdata["Service"] . '</td>
       <td>' . $CorporateTicketdata["Message"] . '</td>
       <td>' . $CorporateTicketdata["CreatedDate"] . '</td>
       <td>' . $CorporateTicketdata["CreatedTime"] . '</td>
       <td>' . $CorporateTicketdata["CloseDate"] . '</td>
       <td>' . $CorporateTicketdata["CloseTime"] . '</td>
       <td>' . $CorporateTicketdata["CreatedBy"] . '</td>
       <td>' . $CorporateTicketdata["DueDate"] . '</td>
       <td>' . $AssignEmployeeName . '</td>
       <td>' . $CorporateTicketdata["Status"] . '</td>'.
                $finance_td.'
    </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>