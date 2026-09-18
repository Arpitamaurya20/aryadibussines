<?php
include("../../controllers/common_controllers.php");
include('../controller/ppm_controller.php');
$conn = _connectodb();
setTimeZone();

$output ="";
$where = " where IsActive = 1";
$ppm_ticket_details = _getTableRecords($conn,'ppm_tickets', $where);

if ($ppm_ticket_details > 0) {
    $output .= '
   <table class="table" border="1">  
                    <tr>  
                         <th>Ticket ID</th>  
                         <th>Corporate ID</th>  
                         <th>Branch ID</th>  
                         <th>Branch Asset ID</th>  
                         <th>PPMDate</th>  
                         <th>Created Date</th>  
                         <th>Created Time</th>  
                         <th>CreatedBy</th> 
                         <th>DueDate</th>  
                         <th>Assigned To</th>  
                         <th>Status</th>  
                    </tr>
  ';
    foreach ($ppm_ticket_details as $PPMTicketdata) {
        $CorporateID = $PPMTicketdata["CorporateID"];
        $where = " where ID = $CorporateID";
        $CorporateData = _getTableDetails($conn,'corporate', $where);
        $CorporateName = $CorporateData['CorporateName'];

        $BranchID = $PPMTicketdata["BranchID"];
        $where = " where ID = $BranchID";
        $BranchData = _getTableDetails($conn,'branch', $where);
        $BranchName = $BranchData['BranchSite'];

        $BranchAssetID =  $PPMTicketdata["BranchAssetID"] ;
        $where = " where ID = $BranchAssetID";
        $BranchAssetsData = _getTableDetails($conn,'branch_assets', $where);
        $BranchAssetsName = $BranchAssetsData['EquipmentName'];

        $output .= '<tr>  
       <td>' . $PPMTicketdata["TicketID"] . '</td>  
       <td>' . $CorporateName . '</td>  
       <td>' . $BranchName . '</td>  
       <td>' . $BranchAssetsName . '</td>
       <td>' . $PPMTicketdata["PPMDate"] . '</td>
       <td>' . $PPMTicketdata["CreatedDate"] . '</td>
       <td>' . $PPMTicketdata["CreatedTime"] . '</td>
       <td>' . $PPMTicketdata["CreatedBy"] . '</td>
       <td>' . $PPMTicketdata["DueDate"] . '</td>
       <td>' . $PPMTicketdata["AssignedTo"] . '</td>
       <td>' . $PPMTicketdata["Status"] . '</td>
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