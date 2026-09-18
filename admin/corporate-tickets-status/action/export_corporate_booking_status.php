<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$corporate_ticket_status_details = _getTableRecords($conn,'corporate_tickets_status', $where);
if ($corporate_ticket_status_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Ticket Status</th>                       
                    </tr>
  ';
    foreach ($corporate_ticket_status_details as $CorporateTicketStatusData) {
        $output .= '<tr>  
       <td>' . $CorporateTicketStatusData["Status"] . '</td>   
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