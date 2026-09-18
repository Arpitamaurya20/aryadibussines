<?php
@session_start();
include("../../controllers/common_controllers.php");
require_once('../../includes/autoloader.inc.php');
$conn = _connectodb();
setTimeZone();

$filter_date = $_POST['filter_date_export'];
$StartDate = explode(" - ",$filter_date)[0];
$EndDate = explode(" - ",$filter_date)[1];
$filter = " where 1";
$status_export = $_POST['status_export'];
if($status_export != "")
{
    $filter = $filter." AND Status = '$status_export'";
}

$employee_filter_export = $_POST['employee_filter_export'];
if($employee_filter_export != "-1")
{
    $filter = $filter." AND EmployeeID = '$employee_filter_export'";
}
$employee_obj = new Employee($conn);
$Supervisor_EmployeeID = $_POST['Supervisor_EmployeeID'];
if($Supervisor_EmployeeID != "-1")
{
    
   
    $employees_array_raw = $employee_obj->GetSupervisedEmployees($Supervisor_EmployeeID);
    $employees_array = array();
    foreach($employees_array_raw as $employee)
    {
        array_push($employees_array,$employee['ID']);
    }
    $employees_in_string = implode(",",$employees_array);
    $filter = $filter." AND EmployeeID IN (".$employees_in_string.")";
    
}

// APPLY DATE FILTER  <<<<<<<< VERY IMPORTANT
// ----------------------------------------------
if (!empty($StartDate) && !empty($EndDate)) {
    $filter .= " AND DATE(a.ConvenienceDate) BETWEEN '$StartDate' AND '$EndDate' ";
}


$employee_array = $employee_obj->setEmployeeArray('All');

$employee_convenience_details = array();
$sql = "Select a.*,b.StatusName as C_Status from employee_convenience a INNER JOIN employee_convenience_status b on a.Status = b.Status ".$filter;
$result = mysqli_query($conn, $sql);
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($employee_convenience_details, $row);
        }
    }
} else {
    //echo $sql;
}
$output ="";
if (sizeof($employee_convenience_details) > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Employee</th>
                         <th>From</th>  
                         <th>To</th>  
                         <th>Amount</th>  
                         <th>Date</th>
                         <th>Status</th> 
                         <th>Remarks</th> 
                    </tr>
  ';
    foreach ($employee_convenience_details as $ec) {
        extract($ec);
        $EmployeeID = $ec['EmployeeID'];
        if(isset($employee_array[$EmployeeID]))
        {
            $EmployeeName = $employee_array[$EmployeeID]['Name'];
        }
        
        $output .= '<tr> 
            <td>' . $EmployeeName . '</td>
            <td>' . $ConvenienceFrom . '</td>
            <td>' . $ConvenienceTo . '</td>
            <td>' . $ConvenienceAmount . '</td> 
            <td>' . $ConvenienceDate . '</td>
            <td>' . $C_Status . '</td>
             <td>' . $Reference . '</td>
            

       
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