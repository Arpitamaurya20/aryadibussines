<?php
include('../controllers/common_controllers.php');
$conn = _connectodb();
setTimeZone();

$dir = fopen("PPM1.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  print_r($data);
  $ParserUniqueID = $data[0];
  $where = " where ParserUniqueID = '$ParserUniqueID'";
  $not_duplicate = check_unique_identity_filter($conn,'temp_ppm_tickets_uploader',$where);
  if($not_duplicate)
  {
    $CompanyName = cleantext($data[1]);
    $BranchName = cleantext($data[2]);
    $PPMQTR1 = cleantext($data[3]);
    $PPMQTR2 = cleantext($data[4]);
    $PPMQTR3 = cleantext($data[5]);
    $PPMQTR4 = cleantext($data[6]);
    $sql = "INSERT INTO temp_ppm_tickets_uploader(ParserUniqueID,CompanyName,BranchName,PPMQTR1,PPMQTR2,PPMQTR3,PPMQTR4) VALUES ('$ParserUniqueID','$CompanyName','$BranchName','$PPMQTR1','$PPMQTR2','$PPMQTR3','$PPMQTR4')";
    echo $sql."<br>";
    _InsertTableRecords($conn,$sql);
  }
}
?>