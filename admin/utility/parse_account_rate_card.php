<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
$conn->set_charset('utf8mb4');
setTimeZone();
$dir = fopen("IDFCRateCardProduct.csv", "r");
$CorporateID = 158;
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
  $SNo = cleantext($data[0]);
  $Type = cleantext($data[1]);
  $Category = cleantext($data[2]);
  $SubCategory = cleantext($data[3]);
  $LineItemName = cleantext($data[4]);
  if(strlen($LineItemName) < 3)
  {
    continue;
  }
  $Make = cleantext($data[5]);
  $HSN = cleantext($data[6]);
  $ARCCode = cleantext($data[7]);
  $UoM = cleantext($data[8]);
  if($UoM == "1No")
  {
    $UoM = "No";
  }
  $Price = cleantext($data[9]);
  $Tax = cleantext($data[10]);
  if (strpos($Tax, "%") !== false) {
    $Tax = str_replace("%", "", $Tax);
  }
  
  $where = " where SNo = '$SNo' and Tag = '17082024158'";
  $number_rows = $core->_getTotalRows($conn,'corporate_rate_card',$where);
  if($number_rows == 0)
  {
    echo $SNo."<br>";
    /* $sql = "INSERT INTO `corporate_rate_card`(`CompanyID`, `Type`, `Category`, `SubCategory`, `LineItemName`, `Make`, `HSN`, `ARCCode`, `UoM`, `Price`, `Tax`, `CreatedDate`, `CreatedTime`, `CreatedBy`)VALUES($CorporateID,'$Type','$Category','$SubCategory','$LineItemName','$Make','$HSN','$ARCCode','$UoM','$Price','$Tax','$CreatedDate','$CreatedTime','$CreatedBy')";*/
    $rowData = [
                'CompanyID' => $CorporateID,
                'Type' => $Type,
                'Category' => $Category,
                'SubCategory' => $SubCategory,
                'LineItemName' => $LineItemName,
                'Make' => $Make,
                'HSN' => $HSN,
                'ARCCode' => $ARCCode,
                'UoM' => $UoM,
                'Price' => $Price,
                'Tax' => $Tax,
                'CreatedDate' => $CreatedDate,
                'CreatedTime' => $CreatedTime,
                'CreatedBy' => $CreatedBy,
                'SNo' => $SNo,
                'Tag' => '17082024158'
            ];
    $response = $core->_InsertTableRecords_prepare($conn, 'corporate_rate_card', $rowData);
    if($response['error'] == true)
    {
      echo "<br>".$SNo." - ".$response['message'];
    }
  }
    
  
}
?>