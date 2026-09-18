<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$region_details = _getTableRecords($conn,'region', $where);
if ($region_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>RegionName</th>  
                         <th>Region Head</th>  
                         <th>Region Corporate Head</th>                           
                    </tr>
  ';
    foreach ($region_details as $Regiondata) {
        $RegionHead = $Regiondata["RegionHead"];
        $RegionCorporateHead = $Regiondata["RegionCorporateHead"];
        $where = " where ID = $RegionHead";
        $EmployeeRegionHeadData = _getTableDetails($conn,'employees', $where);
        $where_corporate = " where ID = $RegionCorporateHead";
        $EmployeeCorporateHeadData = _getTableDetails($conn,'employees', $where_corporate);

        $output .= '<tr>  
       <td>' . $Regiondata["RegionName"] . '</td>  
       <td>' . $EmployeeRegionHeadData["Name"] . '</td>  
       <td>' . $EmployeeCorporateHeadData["Name"] . '</td>  
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