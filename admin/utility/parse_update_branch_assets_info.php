<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../includes/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$core->setTimeZone();

$file = fopen("URBAN-CHENNAI-NEW.csv", "r");
$row = 0;

while (($data = fgetcsv($file, 2000, ",")) !== FALSE) {

    // Skip header
    if ($row === 0) {
        $row++;
        continue;
    }

    /* =========================
       CSV → VARIABLES
    ========================= */

    $AssetID           = (int)trim($data[2]); // Equipment Number = branch_assets.ID
    $EquipmentName     = trim($data[1]);
    $Make              = trim($data[3]);
    $Model             = trim($data[4]);
    $SNo               = trim($data[5]);
    $Capacity          = trim($data[6]);
    $Qty               = (int)$data[7];
    $UoM               = (int)$data[9];
    $UnitRate          = $data[10];
    $Amount            = $data[11];
    $ManufacturingYear = trim($data[12]);
    $EquipmentAge      = trim($data[13]);
    $ServiceType       = trim($data[14]);
    $Category          = (int)$data[16];
    $SubCategory       = (int)$data[18];
    $Tat               = trim($data[19]);

    $AMCStartDate = !empty($data[20]) ? date('Y-m-d', strtotime($data[20])) : NULL;
    $AMCEndDate   = !empty($data[21]) ? date('Y-m-d', strtotime($data[21])) : NULL;

    $FloorNumber       = trim($data[22]);
    $EquipmentLocation = trim($data[23]);
    $Description       = trim($data[24]);
    $Interval=trim($data[25]);

    if ($AssetID <= 0) {
        echo "Skipped row $row (Invalid Asset ID)<br>";
        $row++;
        continue;
    }

    /* =========================
       UPDATE ASSET
    ========================= */

    $update_param = "
        EquipmentName = '".addslashes($EquipmentName)."',
        Make = '".addslashes($Make)."',
        Model = '".addslashes($Model)."',
        SNo = '".addslashes($SNo)."',
        Capacity = '".addslashes($Capacity)."',
        Qty = '$Qty',
        UoM = '$UoM',
        UnitRate = '$UnitRate',
        Amount = '$Amount',
        ManufacturingYear = '$ManufacturingYear',
        EquipmentAge = '$EquipmentAge',
        ServiceType = '".addslashes($ServiceType)."',
        Category = '$Category',
        SubCategory = '$SubCategory',
        Tat = '".addslashes($Tat)."',
        AMCStartDate = ".($AMCStartDate ? "'$AMCStartDate'" : "NULL").",
        AMCEndDate = ".($AMCEndDate ? "'$AMCEndDate'" : "NULL").",
        FloorNumber = '".addslashes($FloorNumber)."',
        EquipmentLocation = '".addslashes($EquipmentLocation)."',
        Description = '".addslashes($Description)."',
        PPMInterval= '".addslashes($Interval)."'
        WHERE ID = '$AssetID'
    ";

    $response = $core->_UpdateTableRecords($conn, 'branch_assets', $update_param);

    if ($response['error'] === false) {
        echo "✅ Updated Asset ID: $AssetID<br>";
    } else {
        echo "❌ Failed Asset ID: $AssetID<br>";
    }

    $row++;
}

fclose($file);
?>
