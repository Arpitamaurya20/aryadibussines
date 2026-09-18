<?php
include("../../controllers/common_controllers.php");
include('../controller/branch_arc_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();

$response = array();


// Check if a file was uploaded
 if ($_FILES["csvFile"]["size"] > 0) {
    $file = $_FILES["csvFile"]["tmp_name"];

    $CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	$CreatedBy = $_SESSION['pb_username'];
    
    // Read the file
    $handle = fopen($file, "r");
    
    // Skip the header row
    $data = fgetcsv($handle, 1000);
    
    // Process the remaining rows
    while (($data = fgetcsv($handle, 1000)) !== false) {
        $BranchID = $data[0]; // Assuming the CSV has a column named 'column1'
        $Item_name = $data[1]; // Assuming the CSV has a column named 'column2'
        $Item_decs = $data[2];
        $Item_Code = $data[3];
        $Item_categories = $data[4];
        $Item_uom = $data[5];
        $Item_price = $data[6];

        $not_duplicate = true;

  if($Item_Code != ""){
  $filter = " where ItemCode = '$Item_Code'";
  $not_duplicate = check_unique_identity_filter($conn,'branch_arc',$filter);
  }
  if($not_duplicate){
        // Insert the data into the database
        $arc_query = "INSERT INTO branch_arc(BranchID,ItemName,ItemDescription,ItemCode,ItemCategories,ItemUOM,ItemPrice, CreatedBy, CreatedDate) VALUES ('$BranchID','$Item_name', '$Item_decs', '$Item_Code', '$Item_categories','$Item_uom', '$Item_price', '$CreatedBy', '$CreatedDate')";
	    $response = _InsertTableRecords($conn, $arc_query);
        $response['error'] = false;
        $response['message'] = "ARC Item Added to the System";
        
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