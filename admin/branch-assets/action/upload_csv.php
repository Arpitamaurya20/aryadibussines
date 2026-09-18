<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isDateFormatValid($dateString) {
    $format = 'Y-m-d';
    $dateTimeObject = DateTime::createFromFormat($format, $dateString);

    return $dateTimeObject && $dateTimeObject->format($format) === $dateString;
}
include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();

$where = " where 1";
$categories_array_temp = _getTableRecords($conn,'manage_categories', $where);
$categories = array();
foreach($categories_array_temp as $category)
{
    $CategoriesName = cleantext($category['CategoriesName']);
    $categories[$CategoriesName] = $category['ID'];
}

$sub_categories_array_temp = _getTableRecords($conn,'manage_subcategories', $where);
$sub_categories = array();
foreach($sub_categories_array_temp as $sub_category)
{
    $SubCategoriesName = cleantext($sub_category['SubCategoriesName']);
    $sub_categories[$SubCategoriesName] = $sub_category['ID'];
}

$uom_array_temp = _getTableRecords($conn,'manage_uom', $where);
$uom_array = array();
foreach($uom_array_temp as $uom)
{
    $uomname = $uom['UOMName'];
    $uom_array[$uomname] = $uom['ID'];
}


$response = array();
$response_message = "";

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
    $i=1;
    while (($data = fgetcsv($handle, 1000)) !== false) 
    {
        $Branch = cleantext($data[0]); 
        $where = " where BranchSite = '$Branch'";
        $branch_details = _getTableDetails($conn,'branch',$where);
        if(isset($branch_details['ID']))
        {
            $BranchID = $branch_details['ID'];
        }
        else
        {
            $response_message = $response_message."<br>Row ".$i." has invalid Branch";
            $i++;
            continue;
        }
        $EquipmentName = cleantext($data[1]); 
        $Make = cleantext($data[2]);
        $Model = cleantext($data[3]);
        $Sno = cleantext($data[4]);
        $Capacity = cleantext($data[5]);
        $Qty = cleantext($data[6]);
        $uom = cleantext($data[7]);
        if(isset($uom_array[$uom]))
        {
            $uomID = $uom_array[$uom];
        }
        else
        {
            $response_message = $response_message."<br>Row ".$i." has invalid uom - ".$uom;
            $i++;
            continue;
        }
        $UnitRate = cleantext($data[8]);
        $Amount = cleantext($data[9]);
        $ManufacturingYear = cleantext($data[10]);
        $AgeofEquipment = cleantext($data[11]);
        $ServiceType = cleantext($data[12]);
        $category = cleantext($data[13]);
        if(isset($categories[$category]))
        {
            $categoryID = $categories[$category];
        }
        else
        {
            $response_message = $response_message."<br>Row ".$i." has invalid category - ".$category;
            $i++;
            continue;
        }
        $subCategory = cleantext($data[14]);
        if(isset($sub_categories[$subCategory]))
        {
            $subCategoryID = $sub_categories[$subCategory];
        }
        else
        {
            $response_message = $response_message."<br>Row ".$i." has invalid subcategory - ".$subCategory;
            $i++;
            continue;
        }
        $TAT = cleantext($data[15]);
        $AMCStartDate =cleantext($data[16]);
        if(!isDateFormatValid($AMCStartDate))
        {
            $response_message = $response_message."<br>Row ".$i." has invalid AMC Start Date(Format must be yyyy-mm-dd)";
            $i++;
            continue;
        }
        $AMCEndDate = $data[17];
        if(!isDateFormatValid($AMCEndDate))
        {
            $response_message = $response_message."<br>Row ".$i." has invalid AMC End Date(Format must be yyyy-mm-dd)";
            $i++;
            continue;
        }
        $FloorNumber = cleantext($data[18]);
        $EqipmentLocation = cleantext($data[19]);
        $Description = cleantext($data[20]);
        $UniqueID = cleantext($data[21]);
        $filter = " where UniqueID = '$UniqueID'";
        if(!check_unique_identity_filter($conn,'branch_assets', $filter))
        {
            $response_message = $response_message."<br>Row ".$i." is already parsed";
            $i++;
            continue;
        }

  
        $sql = "INSERT INTO branch_assets (BranchID,EquipmentName,Make,Model,SNo,Capacity,Qty,UoM,UnitRate,Amount,ManufacturingYear,EquipmentAge,ServiceType,Category,SubCategory,TaT,AMCStartDate,AMCEndDate,FloorNumber,EquipmentLocation,Description,UniqueID,CreatedBy,CreatedDate) VALUES ('$BranchID','$EquipmentName','$Make','$Model','$Sno','$Capacity','$Qty','$uomID','$UnitRate','$Amount','$ManufacturingYear','$AgeofEquipment','$ServiceType','$categoryID','$subCategoryID','$TAT','$AMCStartDate','$AMCEndDate','$FloorNumber','$EqipmentLocation','$Description','$UniqueID','System','$CreatedDate')";
        $response_insert = _InsertTableRecords($conn, $sql);
        if($response_insert['error'] == false)
        {
            $response_message = $response_message."<br>Row ".$i." inserted";
        }
        else
        {
            $response_message = $response_message."<br>Row ".$i." can't be inserted (Query Error)";
        }
        $i++;
    }
   
}
else    
{
    //echo "string";
    $response['error'] = true;
    $response['message'] = "No file uploaded.";

}
$response['message'] = $response_message;
fclose($handle);
echo json_encode($response);
?>