<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$sub_categories_details = _getTableRecords($conn,'manage_subcategories', $where);
if ($sub_categories_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Categories Name</th>                       
                         <th>Sub Categories Name</th>                       
                    </tr>
  ';
    foreach ($sub_categories_details as $SubCategoriesData) {
        $CategoriesID = $SubCategoriesData["Categories"];
        $where = " where ID = $CategoriesID";
        $CategoriesData = _getTableDetails($conn,'manage_categories', $where);

        $output .= '<tr>  
       <td>' . $CategoriesData["CategoriesName"] . '</td>   
       <td>' . $SubCategoriesData["SubCategoriesName"] . '</td>   
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