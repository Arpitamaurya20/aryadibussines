<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();

$filter_date = $_POST['filter_date_export'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];

$branch_export = $_POST['branch_export'];
$status_export = $_POST['status_export'];

$Employee_ID = -1;
$sql_in_account = "";
$filter_accounts = "1";
if(isset($_SESSION['Roles']['EmployeeRoles']))
{
    $EmployeeRoles = $_SESSION['Roles']['EmployeeRoles'];
    foreach($EmployeeRoles as $E_Role)
    {
        if($E_Role == "Branch Account Manager")
        {
            if(isset($_SESSION['Roles']['EmployeeID']))
            {
                $Employee_ID = $_SESSION['Roles']['EmployeeID'];
            }
        }
    }
}


if($Employee_ID!=-1)
{
    // Get cities mapped to Employees
    $branch = new Branch($conn);
    $branches_array_mapped_raw = $branch->getMappedAccountBranchesofAccountBranchManager($Employee_ID);
    $branches_array_mapped = array();
    foreach($branches_array_mapped_raw as $branches_mapped)
    {
        array_push($branches_array_mapped,$branches_mapped['ID']);
    }
    $sql_in_account = "'" . implode("', '", $branches_array_mapped) . "'";
    $filter_accounts = "1 AND BranchID IN (".$sql_in_account.")";
}


/*$corporate_id = -1;
$branch_id = -1;
*/


$corporate_check = "1";
$branch_check = "1";
$status_check = "1";
$filter_corporate_approval_pending = "1";

if($status_export != "")
{
    $status_check = " Status = '$status_export'";
}
/*if($company_account_export != "")
{
    $corporate_check = " CorporateID = $company_account_export ";
}*/

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


$where = " where $filter_accounts AND $corporate_check AND $branch_check AND $status_check AND $filter_corporate_approval_pending AND (CreatedDate>='$StartDate' AND CreatedDate<='$EndDate') AND IsActive = 1 order by ID desc";

$corporate_ticket_details = _getTableRecords($conn,'corporate_tickets', $where);
/*echo $where."<br>";
print_r($corporate_ticket_details);*/
if ($corporate_ticket_details > 0) {
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
                         <th>CreatedBy</th> 
                         <th>DueDate</th>  
                         <th>Assigned To</th>  
                         <th>Status</th>  
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
            
            
            if($branch_export != "")
            {
                if($BranchID != $branch_export)
                {
                    $to_continue = true;
                }
            }
             $Region = "N.A.";
            if(isset($branch_array[$BranchID]['BranchState']))
            {
                $State = $branch_array[$BranchID]['BranchState'];
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
        
        if($to_continue)
        {
            continue;
        }

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
       <td>' . $CorporateTicketdata["CreatedBy"] . '</td>
       <td>' . $CorporateTicketdata["DueDate"] . '</td>
       <td>' . $AssignEmployeeName . '</td>
       <td>' . $CorporateTicketdata["Status"] . '</td>
                    </tr>
   ';
    }
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}

$myfile = fopen("../account_branch_tickets_report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>