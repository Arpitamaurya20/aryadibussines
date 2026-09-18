<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$join_us_details = _getTableRecords($conn,'join_us', $where);
if ($join_us_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Name</th>  
                         <th>Phone</th>  
                         <th>Email</th>  
                         <th>Message</th>  
                    </tr>
  ';
    foreach ($join_us_details as $JoinUsData) {

        $output .= '<tr>  
       <td>' . $JoinUsData["Name"] . '</td>
       <td>' . $JoinUsData["Phone"] . '</td>  
       <td>' . $JoinUsData["Email"] . '</td>
       <td>' . $JoinUsData["Message"] . '</td>
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