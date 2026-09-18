<?php
@session_start();
include("../../controllers/common_controllers.php");
include('../controller/branch_controller.php');
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();
$output ="";
$UserType = SessionCheck();
if($UserType == "Corporate Admin")
{
    $corporate_user = true;
    $corporate_account_admin = true;
    $CorporateID = $CompanyID = $_SESSION['Roles']['CorporateID'];
    $where = " where IsActive = 1 and CompanyID = $CorporateID";
}
else
{
    $where = " where IsActive = 1";
}
$employee = new Employee($conn);
$employee_array = $employee->setEmployeeArray('All');
$branch_details = _getTableRecords($conn,'branch', $where);
if ($branch_details > 0) {
    $output .= '
   <table class="table" border="1">  
        <tr>  
             <th>CompanyID</th>  
             <th>BranchSite</th>  
             <th>BranchCode</th>  
             <th>BranchEmail</th>  
             <th>BranchMobile</th>  
             <th>BranchLandline</th>  
             <th>BranchAddress1</th>  
             <th>BranchAddress2</th>  
             <th>BranchCity</th>  
             <th>BranchState</th>  
             <th>BranchPostalCode</th>  
             <th>SiteIncharge</th>
             <th>Account Branch Manager</th>  
        </tr>
  ';
    foreach ($branch_details as $Branchdata) {
        $CompanyID = $Branchdata["CompanyID"];
        $where = " where ID = '$CompanyID'";
        $CompanyData = _getTableDetails($conn,'company', $where);
        $CompanyName = $CompanyData['CompanyName'];   
        $AccountBranchManager = $Branchdata["AccountBranchManager"];
        if($AccountBranchManager == -1)
        {
            $AccountBranchManager = "Not Set";
        }
        else
        {
            $AccountBranchManager = $employee_array[$AccountBranchManager]['Name'];
        }
        $output .= '<tr>  
       <td>' . $CompanyData['CompanyName'] . '</td>  
       <td>' . $Branchdata["BranchSite"] . '</td>  
       <td>' . $Branchdata["BranchCode"] . '</td>  
       <td>' . $Branchdata["BranchEmail"] . '</td>  
       <td>' . $Branchdata["BranchMobile"] . '</td>
       <td>' . $Branchdata["BranchLandline"] . '</td>
       <td>' . $Branchdata["BranchAddress1"] . '</td>
       <td>' . $Branchdata["BranchAddress2"] . '</td>
       <td>' . $Branchdata["BranchCity"] . '</td>
       <td>' . $Branchdata["BranchState"] . '</td>
       <td>' . $Branchdata["BranchPostalCode"] . '</td>
       <td>' . $Branchdata["SiteIncharge"] . '</td>
       <td>' . $AccountBranchManager.'</td>
        </tr>
   ';
    }

} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}
echo $output;
$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>