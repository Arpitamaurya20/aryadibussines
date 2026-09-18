<?php
include("../../controllers/common_controllers.php");
include('../controller/contact_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$quote_details = _getTableRecords($conn,'get_quote', $where);
if ($quote_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Name</th>  
                         <th>Email</th>  
                         <th>Mobile Number</th>  
                         <th>Service</th>  
                         <th>Message</th>  
                         <th>OnDate</th>   
                         <th>OnTime</th>   
                    </tr>
  ';
    foreach ($quote_details as $Quotedata) {

        $output .= '<tr>  
       <td>' . $Quotedata["name"] . '</td>  
       <td>' . $Quotedata["email"] . '</td>  
       <td>' . $Quotedata["mobile_number"] . '</td>
       <td>' . $Quotedata["service"] . '</td>
       <td>' . $Quotedata["message"] . '</td>
       <td>' . $Quotedata["ondate"] . '</td>
       <td>' . $Quotedata["ontime"] . '</td>
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