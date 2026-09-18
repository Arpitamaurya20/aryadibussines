<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$booking_status_details = _getTableRecords($conn,'booking_status', $where);
if ($booking_status_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Booking Status</th>                       
                    </tr>
  ';
    foreach ($booking_status_details as $BookingStatusData) {
        $output .= '<tr>  
       <td>' . $BookingStatusData["Status"] . '</td>   
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