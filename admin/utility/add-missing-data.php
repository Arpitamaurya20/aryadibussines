<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../controllers/common_controllers.php');

$conn = _connectodb();

set_time_limit(0);
ini_set('memory_limit', '-1');

$file = "branch_assets_data_1.csv";

if (!file_exists($file)) {
    die("CSV file not found.");
}

$handle = fopen($file, "r");

if (!$handle) {
    die("Unable to open CSV file.");
}

// Skip Header
$header = fgetcsv($handle);

$inserted = 0;
$updated = 0;
$failed = 0;

while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {

    $ID                 = mysqli_real_escape_string($conn, trim($data[0]));
    $BranchID           = mysqli_real_escape_string($conn, trim($data[1]));
    $EquipmentName      = mysqli_real_escape_string($conn, trim($data[2]));
    $Make               = mysqli_real_escape_string($conn, trim($data[3]));
    $Model              = mysqli_real_escape_string($conn, trim($data[4]));
    $SNo                = mysqli_real_escape_string($conn, trim($data[5]));
    $Capacity           = mysqli_real_escape_string($conn, trim($data[6]));
    $Qty                = mysqli_real_escape_string($conn, trim($data[7]));
    $UoM                = mysqli_real_escape_string($conn, trim($data[8]));
    $UnitRate           = mysqli_real_escape_string($conn, trim($data[9]));
    $Amount             = mysqli_real_escape_string($conn, trim($data[10]));
    $ManufacturingYear  = mysqli_real_escape_string($conn, trim($data[11]));
    $EquipmentAge       = mysqli_real_escape_string($conn, trim($data[12]));
    $ServiceType        = mysqli_real_escape_string($conn, trim($data[13]));
    $Category           = mysqli_real_escape_string($conn, trim($data[14]));
    $SubCategory        = mysqli_real_escape_string($conn, trim($data[15]));
    $Tat                = mysqli_real_escape_string($conn, trim($data[16]));
    $AMCStartDate = trim($data[17]);
    $AMCEndDate   = trim($data[18]);

    if(!empty($AMCStartDate)){
        $AMCStartDate = DateTime::createFromFormat('d-m-Y', $AMCStartDate);
        $AMCStartDate = $AMCStartDate ? $AMCStartDate->format('Y-m-d') : NULL;
    }

    if(!empty($AMCEndDate)){
        $AMCEndDate = DateTime::createFromFormat('d-m-Y', $AMCEndDate);
        $AMCEndDate = $AMCEndDate ? $AMCEndDate->format('Y-m-d') : NULL;
    }
    $SOW                = mysqli_real_escape_string($conn, trim($data[19]));
    $FloorNumber        = mysqli_real_escape_string($conn, trim($data[20]));
    $EquipmentLocation  = mysqli_real_escape_string($conn, trim($data[21]));
    $Description        = mysqli_real_escape_string($conn, trim($data[22]));
    $PPMInterval        = mysqli_real_escape_string($conn, trim($data[23]));
    $UniqueID           = mysqli_real_escape_string($conn, trim($data[24]));
    $CreatedBy          = mysqli_real_escape_string($conn, trim($data[25]));
    $CreatedDate        = mysqli_real_escape_string($conn, trim($data[26]));
    $UpdatedDate        = date('Y-m-d');
    $IsActive           = mysqli_real_escape_string($conn, trim($data[28]));

    // Check if asset already exists
    $checkSql = "SELECT ID FROM branch_assets WHERE ID='$ID'";
    $checkRes = mysqli_query($conn, $checkSql);

    if (mysqli_num_rows($checkRes) > 0) {

        $sql = "
        UPDATE branch_assets SET
            BranchID='$BranchID',
            EquipmentName='$EquipmentName',
            Make='$Make',
            Model='$Model',
            SNo='$SNo',
            Capacity='$Capacity',
            Qty='$Qty',
            UoM='$UoM',
            UnitRate='$UnitRate',
            Amount='$Amount',
            ManufacturingYear='$ManufacturingYear',
            EquipmentAge='$EquipmentAge',
            ServiceType='$ServiceType',
            Category='$Category',
            SubCategory='$SubCategory',
            Tat='$Tat',
            AMCStartDate='$AMCStartDate',
            AMCEndDate='$AMCEndDate',
            SOW='$SOW',
            FloorNumber='$FloorNumber',
            EquipmentLocation='$EquipmentLocation',
            Description='$Description',
            PPMInterval='$PPMInterval',
            UniqueID='$UniqueID',
            CreatedBy='$CreatedBy',
            CreatedDate='$CreatedDate',
            UpdatedDate='$UpdatedDate',
            IsActive='$IsActive'
        WHERE ID='$ID'
        ";

        if (mysqli_query($conn, $sql)) {
            $updated++;
        } else {
            $failed++;
            echo mysqli_error($conn)."<br>";
        }

    } else {

        $sql = "
        INSERT INTO branch_assets
        (
            ID, BranchID, EquipmentName, Make, Model, SNo,
            Capacity, Qty, UoM, UnitRate, Amount,
            ManufacturingYear, EquipmentAge, ServiceType,
            Category, SubCategory, Tat, AMCStartDate,
            AMCEndDate, SOW, FloorNumber, EquipmentLocation,
            Description, PPMInterval, UniqueID, CreatedBy,
            CreatedDate, UpdatedDate, IsActive
        )
        VALUES
        (
            '$ID','$BranchID','$EquipmentName','$Make','$Model','$SNo',
            '$Capacity','$Qty','$UoM','$UnitRate','$Amount',
            '$ManufacturingYear','$EquipmentAge','$ServiceType',
            '$Category','$SubCategory','$Tat','$AMCStartDate',
            '$AMCEndDate','$SOW','$FloorNumber','$EquipmentLocation',
            '$Description','$PPMInterval','$UniqueID','$CreatedBy',
            '$CreatedDate','$UpdatedDate','$IsActive'
        )";

        if (mysqli_query($conn, $sql)) {
            $inserted++;
        } else {
            $failed++;
            echo mysqli_error($conn)."<br>";
        }
    }
}

fclose($handle);

// Reset AUTO_INCREMENT
$res = mysqli_query($conn, "SELECT MAX(ID) AS MaxID FROM branch_assets");
$row = mysqli_fetch_assoc($res);

$nextID = $row['MaxID'] + 1;

mysqli_query($conn, "ALTER TABLE branch_assets AUTO_INCREMENT = $nextID");

echo "<h3>Import Completed</h3>";
echo "Inserted : ".$inserted."<br>";
echo "Updated : ".$updated."<br>";
echo "Failed : ".$failed."<br>";
echo "Next Auto Increment : ".$nextID."<br>";

?>