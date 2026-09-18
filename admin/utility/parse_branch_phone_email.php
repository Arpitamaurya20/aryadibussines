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
$dir = fopen("IDFC_Branch_update.csv", "r");
$CorporateID = 152;
$core = new Core();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$CreatedBy = "parser";
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $BranchCode = cleantext($data[0]);
  $filter = " where BranchCode = '$BranchCode'";
  $rows = $core->_getTotalRows($conn,'branch',$filter);
  if($rows>0)
  {
      $sql_update = "";
      $MobileNumber = cleantext($data[2]);
      if(strpos($MobileNumber,',') !== false)
      {
          $MobileNumber_array = explode(",",$MobileNumber);
          $BranchMobile = $MobileNumber_array[0];
          $sql_update = $sql_update."BranchMobile = '$BranchMobile'";
          $BranchLandline = $MobileNumber_array[1]; 
          $sql_update = $sql_update.",BranchLandline = '$BranchLandline'";
      }
      else
      {
          $BranchMobile = $MobileNumber;
          $sql_update = $sql_update."BranchMobile = '$BranchMobile'";
      }
      $ContactEmail = cleantext($data[1]);
      if(strpos($ContactEmail,',') !== false)
      {
          $ContactEmail_array = explode(",",$ContactEmail);
          $BranchEmail = $ContactEmail_array[0];
          $sql_update = $sql_update.",BranchEmail = '$BranchEmail'";
          $AlternateBranchEmail = $ContactEmail_array[1]; 
           $sql_update = $sql_update.",AlternateBranchEmail = '$AlternateBranchEmail'";
      }
      else
      {
          $BranchEmail = $ContactEmail;
          $sql_update = $sql_update.",BranchEmail = '$BranchEmail'";
      }
      $sql_update = $sql_update." where BranchCode = '$BranchCode'";
      echo "<br><br>".$sql_update;
      $response = $core->_UpdateTableRecords($conn,'branch',$sql_update);
      echo "<br>".$response['message'];
      /*$sql = "INSERT INTO `corporate_rate_card`(`CompanyID`, `Type`, `Category`, `SubCategory`, `LineItemName`, `Make`, `HSN`, `ARCCode`, `UoM`, `Price`, `Tax`, `CreatedDate`, `CreatedTime`, `CreatedBy`)VALUES($CorporateID,'$Type','$Category','$SubCategory','$LineItemName','$Make','$HSN','$ARCCode','$UoM','$Price','$Tax','$CreatedDate','$CreatedTime','$CreatedBy')";
      $response = _InsertTableRecords($conn,$sql);
      print_r($response);*/

  }
  else
  {
    echo "<br><br>Branch Code Not Found";
  }

  
  
}
?>