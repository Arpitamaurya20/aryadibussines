<?php
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$currentDate = date("Y-m-d");
$where = " where PPMDate <= DATE_ADD('$currentDate',INTERVAL 30 DAY) AND Status = 'Planned'";
$ppm_tickets = _getTableRecords($conn,'ppm_tickets',$where);
$branch_account_managers_array = array();
foreach($ppm_tickets as $ppm_ticket)
{
    $ID =  $ppm_ticket['ID'];
    $update_query = " Status = 'Raised' where ID = $ID";
    _UpdateTableRecords($conn,'ppm_tickets',$update_query);
}
?>