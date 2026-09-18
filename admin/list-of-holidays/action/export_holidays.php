<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$holidays_details = _getTableRecords($conn,'listofholidays', $where);
if ($holidays_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Region Name</th>                       
                         <th>Holidays Name</th>                       
                         <th>Holidays Date</th>                       
                    </tr>
  ';
    foreach ($holidays_details as $HolidayData) {
        $RegionID = $HolidayData["RegionName"];
        $where = " where ID = $RegionID";
        $RegionData = _getTableDetails($conn,'region', $where);

        $output .= '<tr>  
       <td>' . $RegionData["RegionName"] . '</td>   
       <td>' . $HolidayData["HolidaysName"] . '</td>   
       <td>' . $HolidayData["HolidaysDate"] . '</td>   
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