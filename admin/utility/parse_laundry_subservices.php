<?php
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();
$dir = fopen("SubServices_Laundry.csv", "r");
$ServiceID = 30;
$core = new Core();
$subservices = array();
$subservices_array = $core->_getTableRecords($conn,'subservice',' where service_id = 30');
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
foreach($subservices_array as $subservice)
{
  $title = $subservice['title'];
  $ID = $subservice['ID'];
  $subservices[$title]['ID'] = $ID;
}
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  /*if($k==0)
  {
    $k++;
    continue;
  }*/
  $ClothType = cleantext($data[2]);
  $Subservice_name = cleantext($data[1]);
  $Price = cleantext($data[3]);
  $SubserviceID = -1;
  if(isset($subservices[$Subservice_name]))
  {
    $SubserviceID = $subservices[$Subservice_name]['ID'];
  }
  if($SubserviceID != -1)
  {
  
    $sql = "INSERT INTO laundry_sub_service(ServiceID,SubserviceID,TypeOfClothes,Price,CreatedBy,CreatedDate,CreatedTime) VALUES ($ServiceID,$SubserviceID,'$ClothType','$Price','system','$CreatedDate','$CreatedTime')";
    echo $sql."<br>";
    $response = _InsertTableRecords($conn,$sql);
  print_r($response);
  }
  else
  {
    echo $Subservice_name." - Subservice not found<br>";
  }
}
?>