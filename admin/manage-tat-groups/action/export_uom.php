<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$uom_details = _getTableRecords($conn,'manage_uom', $where);
if ($uom_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>UOM Name</th>                            
                    </tr>
  ';
    foreach ($uom_details as $UOMData) {
        $output .= '<tr>  
       <td>' . $UOMData["UOMName"] . '</td>  
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