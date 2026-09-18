<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$StartDate = "2024-11-01";
$EndDate = "2024-11-31";
$core = new Core();
$where = " where PPMDate >= '$StartDate' AND PPMDate < '$EndDate' AND (Status = 'Planned' OR Status = 'Raised')";
$records = $core->_getTableRecords($conn,'ppm_tickets',$where);
foreach($records as $record)
{
  $ID = $record['ID'];
  $AccountBranchManagerID = -1;
  $BranchID = $record['BranchID'];
  echo $ID." - ".$record['PPMDate']." - ".$record['Status']."<br>";
  if($BranchID != -1 && $BranchID != "")
  {

    $branch_details = $core->_getTableDetails($conn,'branch',' where ID = '.$BranchID);
    $AccountBranchManagerID = $branch_details['AccountBranchManager'];
  }
  if($AccountBranchManagerID != -1 && $AccountBranchManagerID != "")
  {
    $sql_update = " Status = 'Assigned',AssignedTo = $AccountBranchManagerID where ID = $ID";
    echo $sql_update."<br><br>";
    $core->_UpdateTableRecords($conn,'ppm_tickets',$sql_update);
  }
}
?>