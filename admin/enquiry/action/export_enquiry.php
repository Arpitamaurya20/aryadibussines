<?php
include("../../controllers/common_controllers.php");
include('../controller/contact_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$contact_details = _getTableRecords($conn,'contact', $where);
if ($contact_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>First Name</th>  
                         <th>Last Name</th>  
                         <th>Email</th>  
                         <th>Phone</th>  
                         <th>Message</th>  
                         <th>added on</th>   
                    </tr>
  ';
    foreach ($contact_details as $Contactdata) {

        $output .= '<tr>  
       <td>' . $Contactdata["fname"] . '</td>  
       <td>' . $Contactdata["lname"] . '</td>  
       <td>' . $Contactdata["email"] . '</td>  
       <td>' . $Contactdata["phone"] . '</td>
       <td>' . $Contactdata["message"] . '</td>
       <td>' . $Contactdata["added_on"] . '</td>
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