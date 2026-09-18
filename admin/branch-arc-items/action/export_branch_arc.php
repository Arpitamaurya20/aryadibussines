<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$Branch_arc_details = _getTableRecords($conn,'branch_arc', $where);
print_r($Branch_arc_details);
// die();
if ($Branch_arc_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>BranchID</th>  
                         <th>Item Name</th>  
                         <th>Item Description</th>  
                         <th>Item Code</th>  
                         <th>Item Categories</th>  
                         <th>Item UOM</th>  
                         <th>Item Price</th>    
                    </tr>
  ';
    foreach ($Branch_arc_details as $Companydata) {
        $BranchID = $Companydata["BranchID"];
        $where = " where ID = $CorporateID";
        $BranchData = _getTableDetails($conn,'branch_arc', $where);

        $output .= '<tr> 
       <td>' . $CorporateData["BranchID"] . '</td>  
       <td>' . $Companydata["ItemName"] . '</td>  
       <td>' . $Companydata["ItemDescription"] . '</td>  
       <td>' . $Companydata["ItemCode"] . '</td>  
       <td>' . $Companydata["ItemCategories"] . '</td>
       <td>' . $Companydata["ItemUOM"] . '</td>
       <td>' . $Companydata["ItemPrice"] . '</td>
                    </tr>
   ';
    }

} else {
    $output = "<table><tr><td>No Data Available</td></tr></table>";
}
$myfile = fopen("../report.xls", "w");
fwrite($myfile, $output);
fclose($myfile);
?>