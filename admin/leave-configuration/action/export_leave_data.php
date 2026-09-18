<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$leave_details = _getTableRecords($conn,'leaveconfiguation', $where);
if ($leave_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Type Of Leave</th>                       
                         <th>Number Of Leave</th>                       
                    </tr>
  ';
    foreach ($leave_details as $LeaveData) {
        $output .= '<tr>  
       <td>' . $LeaveData["TypeOfLeave"] . '</td>   
       <td>' . $LeaveData["NumberOfLeave"] . '</td>   
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