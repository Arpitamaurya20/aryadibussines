<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$branch_assets_details = _getTableRecords($conn,'branch_assets', $where);
if ($branch_assets_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>BranchID</th>  
                         <th>EquipmentName</th>  
                         <th>Make</th>  
                         <th>Model</th>  
                         <th>SNo</th>  
                         <th>Capacity</th>  
                         <th>Qty</th>  
                         <th>UnitRate</th>  
                         <th>Amount</th>  
                         <th>ManufacturingYear</th> 
                         <th>EquipmentAge</th>  
                         <th>ServiceType</th>  
                         <th>Category</th>  
                         <th>SubCategory</th>  
                         <th>Tat</th>  
                         <th>AMCStartDate</th>  
                         <th>AMCEndDate</th>  
                         <th>FloorNumber</th>  
                         <th>EquipmentLocation</th>  
                         <th>Description</th>  
                         <th>Created By</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th> 
                    </tr>
  ';
    foreach ($branch_assets_details as $BranchAssetsdata) {
        $BranchID = $BranchAssetsdata["BranchID"];
        $where = " where ID = $BranchID";
        $BranchData = _getTableDetails($conn,'branch', $where);
        $output .= '<tr>  

       <td>' . $BranchData["BranchSite"] . '</td>  
       <td>' . $BranchAssetsdata["EquipmentName"] . '</td>  
       <td>' . $BranchAssetsdata["Make"] . '</td>  
       <td>' . $BranchAssetsdata["Model"] . '</td>  
       <td>' . $BranchAssetsdata["SNo"] . '</td>
       <td>' . $BranchAssetsdata["Capacity"] . '</td>
       <td>' . $BranchAssetsdata["Qty"] . '</td>
       <td>' . $BranchAssetsdata["UoM"] . '</td>
       <td>' . $BranchAssetsdata["Amount"] . '</td>
       <td>' . $BranchAssetsdata["ManufacturingYear"] . '</td>
       <td>' . $BranchAssetsdata["ServiceType"] . '</td>
       <td>' . $BranchAssetsdata["Category"] . '</td>
       <td>' . $BranchAssetsdata["Tat"] . '</td>
       <td>' . $BranchAssetsdata["AMCStartDate"] . '</td>
       <td>' . $BranchAssetsdata["AMCEndDate"] . '</td>
       <td>' . $BranchAssetsdata["SOW"] . '</td>
       <td>' . $BranchAssetsdata["FloorNumber"] . '</td>
       <td>' . $BranchAssetsdata["EquipmentLocation"] . '</td>
       <td>' . $BranchAssetsdata["Description"] . '</td>
       <td>' . $BranchAssetsdata["CreatedBy"] . '</td>
       <td>' . $BranchAssetsdata["CreatedDate"] . '</td>
       <td>' . $BranchAssetsdata["CreatedTime"] . '</td>
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