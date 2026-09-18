<?php
// require('../PHPExcel/Classes/PHPExcel.php');

// require('../library/php-excel-reader/excel_reader2.php');
include("../../controllers/common_controllers.php");
include('../controller/company_controller.php');
$conn = _connectodb();
setTimeZone();

// $District = $_POST['filter_district'];
// $filter_district = "";
// if($District != -1 && $District != "")
// {
//   $filter_district = " AND District LIKE '%$District%'";
// }

$output ="";
$where = " where IsActive = 1";
$company_details = _getTableDetails($conn,'company', $where);
print_r($company_details);
// die();
if ($company_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Corporate</th>  
                         <th>Company Name</th>  
                         <th>Company Email</th>  
                         <th>Phone Number</th>  
                         <th>Mobile Number</th>  
                         <th>Tendor</th>  
                         <th>R&M TAT</th>  
                         <th>PO/WO</th>  
                         <th>PO/WO Date</th>  
                         <th>Access</th>  
                         <th>Created By</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th> 
                    </tr>
  ';
    foreach ($company_details as $Companydata) {

        $output .= '<tr>  
       <td>' . $Companydata["CorporateName"] . '</td>  
       <td>' . $Companydata["CompanyName"] . '</td>  
       <td>' . $Companydata["CompanyEmail"] . '</td>  
       <td>' . $Companydata["CompanyPhone"] . '</td>  
       <td>' . $Companydata["CompanyMobile"] . '</td>
       <td>' . $Companydata["CompanyTendor"] . '</td>
       <td>' . $Companydata["CompanyTAT"] . '</td>
       <td>' . $Companydata["CompanyPOWO"] . '</td>
       <td>' . $Companydata["CompanyPOWODate"] . '</td>
       <td>' . $Companydata["Access"] . '</td>
       <td>' . $Companydata["CreatedBy"] . '</td>
       <td>' . $Companydata["CreatedDate"] . '</td>
       <td>' . $Companydata["CreatedTime"] . '</td>
                    </tr>
   ';
    }
    // $output .= '</table>'; 
    // echo $output;
} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}
// header('Content-Type: application/xls');
// header('Content-Disposition: attachment; filename=awareness' . time() . '.xls');
// echo $output;
$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>