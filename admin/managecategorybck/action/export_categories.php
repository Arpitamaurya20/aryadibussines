<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$categories_details = _getTableRecords($conn,'manage_categories', $where);
if ($categories_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Categories Name</th>                       
                    </tr>
  ';
    foreach ($categories_details as $CategoriesData) {
        $output .= '<tr>  
       <td>' . $CategoriesData["CategoriesName"] . '</td>   
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