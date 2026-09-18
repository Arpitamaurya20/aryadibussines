<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$site_details = _getTableRecords($conn,'site', $where);
if ($site_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Site Name</th>                            
                    </tr>
  ';
    foreach ($site_details as $SiteData) {
        $output .= '<tr>  
       <td>' . $SiteData["site_name"] . '</td>  
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