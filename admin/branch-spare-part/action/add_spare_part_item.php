<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_spare_part_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();
setTimeZone();

$response = array();
$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$PriceArr = $_POST['spare_part_price'];
$CategoriesArr = $_POST['CategoriesID'];
$BranchIDArr = $_POST['BranchID'];
$SpareIDArrr = $_POST['SparePartID'];
    
foreach ($PriceArr as $key => $val) {
	   $SparePartID = $SpareIDArrr[$key];
	   $items_name = "item_".$SparePartID;
       if(isset($_POST[$items_name]) == "on"){
		$SparePrice = $val;
		$CategoriesID = $CategoriesArr[$key];
		$BranchID = $BranchIDArr;
		

		if ($CategoriesID != '' && $SparePrice != '') {
			// mysqli_query($conn, "INSERT INTO branch_arc (BranchID,CategoriesID,ARCItemID,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$BranchID','$CategoriesID','$ARCID','$ArcPrice','System','$CreatedDate','$CreatedTime')");
			$branch_spare_query = "INSERT INTO branch_spare_part (BranchID,CategoriesID,SparePartID,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$BranchID','$CategoriesID','$SparePartID','$SparePrice','System','$CreatedDate','$CreatedTime')";
			$response = _InsertTableRecords($conn, $branch_spare_query);
            $response['error'] = false;
            $response['message'] = "Branch Spare Part Added to the System";

		}else{
			$response['error'] = true;
            $response['message'] = "Technical Problem, Please try again later !";
		}
	}
 
}
echo json_encode($response);

?>
