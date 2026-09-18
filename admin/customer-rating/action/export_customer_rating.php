<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$customer_rating_details = _getTableRecords($conn,'customer_rating', $where);
if ($customer_rating_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Ticket ID</th>                       
                         <th>Rating</th>                       
                         <th>Message</th>                       
                    </tr>
  ';
    foreach ($customer_rating_details as $CustomerRatingData) {
        $output .= '<tr>  
       <td>' . $CustomerRatingData["TicketID"] . '</td>   
       <td>' . $CustomerRatingData["Rating"] . '</td>   
       <td>' . $CustomerRatingData["Message"] . '</td>   
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