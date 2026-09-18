<?php
include("../../controllers/common_controllers.php");
include('../controller/contact_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$resume_details = _getTableRecords($conn,'resume', $where);
if ($resume_details > 0) {
    $output .= '
   <table class="table" border="1">  
        <tr>  
             <th>Name</th>  
             <th>Email</th>  
             <th>Phone</th>  
             <th>added on</th>   
        </tr>
  ';
    foreach ($resume_details as $Resumedata) {

        $output .= '<tr>  
       <td>' . $Resumedata["name"] . '</td>  
       <td>' . $Resumedata["email"] . '</td>  
       <td>' . $Resumedata["phone"] . '</td>
       <td>' . $Resumedata["added_on"] . '</td>
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