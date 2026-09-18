<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$spare_part_details = _getTableRecords($conn,'sparepartlist', $where);
if ($spare_part_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Spare Part Code</th>                       
                         <th>Spare Part Name</th>                       
                         <th>Price</th>                       
                         <th>Categories</th>                       
                         <th>UOM</th>                       
                    </tr>
  ';
    foreach ($spare_part_details as $SparePartData) {
        $CategoriesID = $SparePartData["Categories"];
        $where = " where ID = $CategoriesID";
        $CategoriesData = _getTableDetails($conn,'manage_categories', $where);
        $UOMID = $SparePartData["UOM"];
        $wheres = " where ID = '$UOMID'";
        $UOMData = _getTableDetails($conn,'manage_uom', $wheres);

        $output .= '<tr>  
       <td>' . $SparePartData["SparePartCode"] . '</td>   
       <td>' . $SparePartData["SparePart"] . '</td>   
       <td>' . $SparePartData["Price"] . '</td>   
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