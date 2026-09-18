<?php
include("../../controllers/common_controllers.php");
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where 1";
$city_details = _getTableRecords($conn,'citydata', $where);
if ($city_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>City Name</th>  
                         <th>State Name</th>  
                         <th>City Lead</th>  
                         <th>Corporate Lead</th>  
                         <th>url</th>  
                         <th>metaTitle</th>  
                         <th>metaDescription</th>  
                    </tr>
  ';
    foreach ($city_details as $Citydata) {
        $StateID = $Citydata["StateID"];
        $where_state = " where ID = $StateID";
        $StateName = _getTableDetails($conn,'state', $where_state);
        $CityLead = $Citydata["CityLead"];
        $where = " where ID = $CityLead";
        $EmployeeCityLead = _getTableDetails($conn,'employees', $where);
        $CorporateLead = $Citydata["CorporateLead"];
        $where_corporate = " where ID = $CorporateLead";
        $EmployeeCorporateLead = _getTableDetails($conn,'employees', $where_corporate);

        $output .= '<tr>  
       <td>' . $Citydata["CityName"] . '</td>  
       <td>' . $StateName["StateName"] . '</td>  
       <td>' . $EmployeeCityLead["Name"] . '</td>
       <td>' . $EmployeeCorporateLead["Name"] . '</td>
       <td>' . $Citydata["url"] . '</td>
       <td>' . $Citydata["metaTitle"] . '</td>
       <td>' . $Citydata["metaDescription"] . '</td>
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