<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();
setTimeZone();

$response = array();

$CreatedDate = date("Y-m-d");
$CreatedTime = date("H:i:s");
$PriceArr = $_POST['arc_price'];
$CategoriesArr = $_POST['CategoriesID'];
$BranchIDArr = $_POST['BranchID'];
$ArcIDArrr = $_POST['ArcID'];

    
foreach ($PriceArr as $key => $val) {
	   $ARCID = $ArcIDArrr[$key];
	   $items_name = "item_".$ARCID;
       if(isset($_POST[$items_name]) == "on"){
		$ArcPrice = $val;
		$CategoriesID = $CategoriesArr[$key];
		$BranchID = $BranchIDArr;
		

		if ($CategoriesID != '' && $ArcPrice != '') {
			// mysqli_query($conn, "INSERT INTO branch_arc (BranchID,CategoriesID,ARCItemID,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$BranchID','$CategoriesID','$ARCID','$ArcPrice','System','$CreatedDate','$CreatedTime')");
			$branch_arc_query = "INSERT INTO branch_arc (BranchID,CategoriesID,ARCItemID,Price,CreatedBy,CreatedDate,CreatedTime ) VALUES('$BranchID','$CategoriesID','$ARCID','$ArcPrice','System','$CreatedDate','$CreatedTime')";
			$response = _InsertTableRecords($conn, $branch_arc_query);

            $response['error'] = false;
            $response['message'] = "Branch ARC Item Added to the System";

		}else{
            $response['error'] = true;
            $response['message'] = "Technical Problem, Please try again later !";
		}
	}
 
}

echo json_encode($response);

?>
