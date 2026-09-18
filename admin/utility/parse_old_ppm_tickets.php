<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$dir = fopen("PPM_Tickets_Assigned_old.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $PPMTicketID = cleantext($data[0]);
  $filter = " where TicketID = '$PPMTicketID'";
  $core = new Core();
  $details = $core->_getTableDetails($conn,'ppm_tickets',$filter);
  if($details != null)
  {
      $sql_update = "";
      
      $BranchID = $details['BranchID'];
      $where = " where ID = $BranchID";
      $branch_details = $core->_getTableDetails($conn,'branch',$where);
      $Branch_Account_Manager = $branch_details['AccountBranchManager'];
      $sql_update = "AssignedTo = '$Branch_Account_Manager',Status = 'Assigned'";
      $sql_update = $sql_update." where TicketID = '$PPMTicketID'";
      echo "<br><br>".$sql_update;
      $response = $core->_UpdateTableRecords($conn,'ppm_tickets',$sql_update);
      echo "<br>".$response['message'];
      /*$sql = "INSERT INTO `corporate_rate_card`(`CompanyID`, `Type`, `Category`, `SubCategory`, `LineItemName`, `Make`, `HSN`, `ARCCode`, `UoM`, `Price`, `Tax`, `CreatedDate`, `CreatedTime`, `CreatedBy`)VALUES($CorporateID,'$Type','$Category','$SubCategory','$LineItemName','$Make','$HSN','$ARCCode','$UoM','$Price','$Tax','$CreatedDate','$CreatedTime','$CreatedBy')";
      $response = _InsertTableRecords($conn,$sql);
      print_r($response);*/

  }
  else
  {
    echo "<br><br>Ticket ID {$PPMTicketID} not found";
  }

  
  
}
?>