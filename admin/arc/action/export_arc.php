<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$arc_details = _getTableRecords($conn,'arc', $where);
if ($arc_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Item Code</th>                       
                         <th>Item Name</th>                       
                         <th>Item Description</th>                       
                         <th>Price</th>                       
                         <th>Categories</th>                       
                         <th>UOM</th>                       
                    </tr>
  ';
    foreach ($arc_details as $ARCData) {
        $CategoriesID = $ARCData["ItemCategories"];
        $where = " where ID = $CategoriesID";
        $CategoriesData = _getTableDetails($conn,'manage_categories', $where);
        $UOMID = $ARCData["ItemUOM"];
        $wheres = " where ID = '$UOMID'";
        $UOMData = _getTableDetails($conn,'manage_uom', $wheres);

        $output .= '<tr>  
       <td>' . $ARCData["ItemCode"] . '</td>   
       <td>' . $ARCData["ItemName"] . '</td>   
       <td>' . $ARCData["ItemDescription"] . '</td>   
       <td>' . $ARCData["ItemPrice"] . '</td>   
       <td>' . $CategoriesData["CategoriesName"] . '</td>   
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