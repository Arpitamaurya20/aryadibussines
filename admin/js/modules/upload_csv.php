<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();

$response = array();


// Check if a file was uploaded
 if ($_FILES["csvFile"]["size"] > 0) {
    $file = $_FILES["csvFile"]["tmp_name"];

    $CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	//$CreatedBy = $_SESSION['pb_username'];
    
    // Read the file
    $handle = fopen($file, "r");
    
    // Skip the header row
    $data = fgetcsv($handle, 1000);
    
    // Process the remaining rows
    while (($data = fgetcsv($handle, 1000)) !== false) {
        $BranchID = $data[0]; // Assuming the CSV has a column named 'column1'
        $EquipmentName = $data[1]; // Assuming the CSV has a column named 'column2'
        $Make = $data[2];
        $Model = $data[3];
        $Sno = $data[4];
        $Capacity = $data[5];
        $Qty = $data[6];
        $uomID = $data[7];
        $UnitRate = $data[8];
        $Amount = $data[9];
        $ManufacturingYear = $data[10];
        $AgeofEquipment = $data[11];
        $ServiceType = $data[12];
        $categoryID = $data[13];
        $subCategoryID = $data[14];
        $TAT = $data[15];
        $AMCStartDate = $data[16];
        $AMCEndDate = $data[17];
        $FloorNumber = $data[8];
        $EqipmentLocation = $data[9];
        $Description = $data[10];
        $UniqueID = $data[11];


        $not_duplicate = true;

  if($Sno != ""){
  $filter = " where SNo = $Sno and BranchID = $BranchID";
  $not_duplicate = check_unique_identity_filter($conn,'branch_assets',$filter);
  }
  if($not_duplicate)
  {

    if($Sno != ""){
      $sql = "INSERT INTO branch_assets (BranchID,EquipmentName,Make,Model,SNo,Capacity,Qty,UoM,UnitRate,Amount,ManufacturingYear,EquipmentAge,ServiceType,Category,SubCategory,TaT,AMCStartDate,AMCEndDate,FloorNumber,EquipmentLocation,Description,UniqueID,CreatedBy,CreatedDate) VALUES ($BranchID,'$EquipmentName','$Make','$Model','$Sno','$Capacity','$Qty','$uomID','$UnitRate','$Amount','$ManufacturingYear','$AgeofEquipment','$ServiceType','$categoryID','$subCategoryID','$TAT','$AMCStartDate','$AMCEndDate','$FloorNumber','$EqipmentLocation','$Description','$UniqueID','System','$CreatedDate')";
      $response = _InsertTableRecords($conn, $sql);
      // echo $sql."<br>";
    }

    
    }
    else {
           //echo "string";
            $response['error'] = true;
            $response['message'] = "No file uploaded.";

    }
}
    
    // Close the file handle
    fclose($handle);
    // $response[] = array(
    //     'error' => false,
    //     'message' => "File uploaded successfully!"
    // );
    
    //echo "File uploaded successfully.";
}
    // $response[] = array(
    //     'error' => true,
    //     'message' => "No file uploaded."
    // );


echo json_encode($response);
?>