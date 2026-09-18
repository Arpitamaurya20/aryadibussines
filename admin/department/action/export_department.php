<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$department_details = _getTableRecords($conn,'department', $where);
if ($department_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Department Name</th>                       
                         <th>Department Head</th>                       
                    </tr>
  ';
    foreach ($department_details as $DepartmentData) {
        $DepartmentEmployeeID = $DepartmentData["DepartmentHead"];
        $where = " where ID = $DepartmentEmployeeID";
        $EmployeesData = _getTableDetails($conn,'employees', $where);

        $output .= '<tr>  
       <td>' . $DepartmentData["DepartmentName"] . '</td>   
       <td>' . $EmployeesData["Name"] . '</td>   
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