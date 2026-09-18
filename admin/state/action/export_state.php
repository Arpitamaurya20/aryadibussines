<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$state_details = _getTableRecords($conn,'state', $where);
if ($state_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>State Name</th>  
                         <th>State Head</th>  
                         <th>StateCorporateHead</th>  
                         <th>Region Name</th>  
                    </tr>
  ';
    foreach ($state_details as $Statedata) {
         $RegionID = $Statedata["RegionID"];
        $where_region = " where ID = $RegionID";
        $RegionName = _getTableDetails($conn,'region', $where_region);
        $StateHead = $Statedata["StateHead"];
        $where = " where ID = $StateHead";
        $EmployeeStateHead = _getTableDetails($conn,'employees', $where);
        $CorporateLead = $Statedata["StateCorporateHead"];
        $where_corporate = " where ID = $CorporateLead";
        $EmployeeCorporateHead = _getTableDetails($conn,'employees', $where_corporate);

        $output .= '<tr>  
       <td>' . $Statedata["StateName"] . '</td>  
       <td>' . $EmployeeStateHead["Name"] . '</td>  
       <td>' . $EmployeeCorporateHead["Name"] . '</td>  
       <td>' . $RegionName["RegionName"] . '</td>
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