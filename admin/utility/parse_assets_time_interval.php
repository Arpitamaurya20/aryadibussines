<?php

include('../controllers/common_controllers.php');
$conn = _connectodb();
$where = "Where IsActive=1";
$Branch_array = array();
$uom_array = array();
$category_array = array();
$subcategory_array = array();
setTimeZone();
$CreatedDate = date('Y-m-d');
$dir = fopen("Augasta-Point-Assettes-DATA.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
  if($k==0)
  {
    $k++;
    continue;
  }
  $to_be_inserted = true;
  $BranchName = cleantext($data[0]);
  $EquipmentName = cleantext($data[1]);
  $Make = cleantext($data[2]);
  $Model = cleantext($data[3]);
  $Sno = cleantext($data[4]);
  $Capacity = cleantext($data[5]);
  $Qty = cleantext($data[6]);
  $uomName = cleantext($data[7]);
  $UnitRate = cleantext($data[8]);
  $Amount = cleantext($data[9]);
  $ManufacturingYear = cleantext($data[10]);
  $AgeofEquipment = cleantext($data[11]);

  $ServiceType = cleantext($data[12]);
  $categoriesName = cleantext($data[13]);
  $subCategoriesName = cleantext($data[14]);

  $TAT = cleantext($data[15]);
  $AMCStartDate = cleantext($data[16]);
  $AMCEndDate = cleantext($data[17]);
  $FloorNumber = cleantext($data[18]);
  $EqipmentLocation = cleantext($data[19]);
  $Description = cleantext($data[20]);
  $UniqueID = cleantext($data[21]);

  $BranchID ='';
  $uomID='';
  $categoryID='';
  $subCategoryID='';

  if(isset($Branch_array[$BranchName]['ID']))
  {
    $BranchID = $Branch_array[$BranchName]['ID'];
    // echo $BranchID;

  }
  else
  {

    $to_be_inserted = false;
    // echo $BranchID.'hello';
  }

  if(isset($uom_array[$uomName]['ID']))
  {

    $uomID = $uom_array[$uomName]['ID'];
    // echo $uomID."<br>";
  }
  else
  {
    // echo $uomID."<br>";
    $to_be_inserted = false;
  }

  if(isset($category_array[$categoriesName]['ID']))
  {
    $categoryID = $category_array[$categoriesName]['ID'];
    // echo $categoryID;
  }
  else
  {
      //  echo $categoriesName."<br>";echo $categoriesName."<br>";
    $to_be_inserted = false;
  }

  if(isset($subcategory_array[$subCategoriesName]['ID']))
  {
    $subCategoryID = $subcategory_array[$subCategoriesName]['ID'];
    // echo $subCategoryID;
  }
  else
  {
    // echo $subCategoriesName."<br>";
    $to_be_inserted = false;
  }

  // check for unique ID


  $not_duplicate = true;

  if($Sno != ""){
  $where = " where SNo = $Sno and BranchID = $BranchID";
  $not_duplicate = check_unique_identity_filter($conn,'branch_assets',$where);
  }
  

  if($not_duplicate)
  {

    if($Sno != ""){
      $sql = "INSERT INTO branch_assets (BranchID,EquipmentName,Make,Model,SNo,Capacity,Qty,UoM,UnitRate,Amount,ManufacturingYear,EquipmentAge,ServiceType,Category,SubCategory,TaT,AMCStartDate,AMCEndDate,FloorNumber,EquipmentLocation,Description,UniqueID,CreatedBy,CreatedDate) VALUES ($BranchID,'$EquipmentName','$Make','$Model','$Sno','$Capacity','$Qty','$uomID','$UnitRate','$Amount','$ManufacturingYear','$AgeofEquipment','$ServiceType','$categoryID','$subCategoryID','$TAT','$AMCStartDate','$AMCEndDate','$FloorNumber','$EqipmentLocation','$Description','$UniqueID','System','$CreatedDate')";
      $response = _InsertTableRecords($conn, $sql);
      // echo $sql."<br>";
    }

    
    }

}

fclose($dir);
// mysqli_close($connection);
echo "All data Insert. No one data Left";


?>