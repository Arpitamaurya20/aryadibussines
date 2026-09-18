<?php

include("../../controllers/common_controllers.php");
include('../controller/employee_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$employee_details = _getTableRecords($conn,'employees', $where);

if ($employee_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Name</th>  
                         <th>Designation</th>  
                         <th>Employee Number</th>  
                         <th>Divison</th>  
                         <th>Department</th>  
                         <th>Basic</th>  
                         <th>DA</th>  
                         <th>HRA</th>  
                         <th>Bonus</th>  
                         <th>HealthInsurance</th>  
                         <th>Others</th>  
                         <th>Email</th>  
                         <th>ContactNumber</th>  
                         <th>BankAccountName</th> 
                         <th>BankAccountNumber</th> 
                         <th>PAN</th> 
                         <th>Aadhar</th> 
                         <th>Supervisor</th> 
                         <th>Gender</th> 
                         <th>UANNumber</th> 
                         <th>DateofJoining</th> 
                         <th>Epf_number</th> 
                         <th>Esic_number</th> 
                         <th>City</th> 
                    </tr>
  ';
    foreach ($employee_details as $Employeedata) {
        $SupervisorName='';
        $SupervisorID = $Employeedata["Supervisor"];
        if ($SupervisorID) {
        $where = " where Supervisor = '$SupervisorID'";
        $employee_detail_by_supervisor = _getTableDetails($conn,'employees', $where);
        $SupervisorName = $employee_detail_by_supervisor['Name'];        
        }
        
        $output .= '<tr>  
       <td>' . $Employeedata["Name"] . '</td>  
       <td>' . $Employeedata["Designation"] . '</td>  
       <td>' . $Employeedata["EmployeeNumber"] . '</td>  
       <td>' . $Employeedata["Division"] . '</td>  
       <td>' . $Employeedata["Department"] . '</td>
       <td>' . $Employeedata["Basic"] . '</td>
       <td>' . $Employeedata["DA"] . '</td>
       <td>' . $Employeedata["HRA"] . '</td>
       <td>' . $Employeedata["Bonus"] . '</td>
       <td>' . $Employeedata["HealthInsurance"] . '</td>
       <td>' . $Employeedata["Others"] . '</td>
       <td>' . $Employeedata["Email"] . '</td>
       <td>' . $Employeedata["ContactNumber"] . '</td>
       <td>' . $Employeedata["BankAccountName"] . '</td>
       <td>' . $Employeedata["BankAccountNumber"] . '</td>
       <td>' . $Employeedata["PAN"] . '</td>
       <td>' . $Employeedata["Aadhar"] . '</td>
       <td>' . $SupervisorName . '</td>
       <td>' . $Employeedata["Gender"] . '</td>
       <td>' . $Employeedata["UANNumber"] . '</td>
       <td>' . $Employeedata["DateofJoining"] . '</td>
       <td>' . $Employeedata["Epf_number"] . '</td>
       <td>' . $Employeedata["Esic_number"] . '</td>
       <td>' . $Employeedata["City"] . '</td>
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