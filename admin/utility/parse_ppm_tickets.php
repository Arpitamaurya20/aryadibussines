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
$dir = fopen("PPM_SHEET.csv", "r");
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
  $rows = $core->_getTotalRows($conn,'ppm_tickets',$filter);
  if($rows>0)
  {
      $sql_update = "";
      $PPMDate = cleantext($data[4]);
      $DueDate = cleantext($data[6]);
      $AssignedTo_Name = cleantext($data[5]);
      $where = " where Name = '$AssignedTo_Name'";
      $employeedetails = $core->_getTableDetails($conn,'employees',$where);
      $AssignedTo = $employeedetails['ID'];
      $sql_update = "PPMDate = '$PPMDate',DueDate = '$DueDate',AssignedTo=$AssignedTo,Status='Assigned'";
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