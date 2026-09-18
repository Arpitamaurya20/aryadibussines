<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$ppm_ticket_status_details = _getTableRecords($conn,'ppm_ticket_status', $where);
if ($ppm_ticket_status_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>PPM Ticket Status</th>                       
                    </tr>
  ';
    foreach ($ppm_ticket_status_details as $PPMTicketStatusData) {
        $output .= '<tr>  
       <td>' . $PPMTicketStatusData["Status"] . '</td>   
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