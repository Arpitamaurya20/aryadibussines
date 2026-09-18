<?php
include("../../controllers/common_controllers.php");
//include('../controller/branch_arc_controller.php');
$conn = _connectodb();
setTimeZone();
@session_start();
setTimeZone();

$response = array();

// Check if a file was uploaded

// if ($_FILES["csvFile"]["size"] > 0) 
if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["size"] > 0)
{
    
$file = $_FILES["csvFile"]["tmp_name"];
//$handle = fopen($file, "r");
    $CreatedDate = date("Y-m-d");
    $CreatedTime = date("H:i:s");
    $CreatedBy = $_SESSION['pb_username'];
    // Read the file
    $handle = fopen($file, "r");

    // Skip the header row
    fgetcsv($handle, 1000, ",");

    // Process the remaining rows
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $CorporateName = $data[0]; // Assuming the CSV has a column named 'column1'
        $CorporateGST = $data[1]; // Assuming the CSV has a column named 'column2'
        $CoporateAddress = $data[2];

        // Check if the record already exists
        $filter = "WHERE CorporateName = '$CorporateName'";

        //$table = 'corporate2';
        $result = check_unique_identity_filter($conn, 'corporate2', $filter);
        
        // Check if a valid result was returned
        if ($result !== false) {
            // Retrieve the count from the response

            $row = mysqli_fetch_assoc($result);
            $count = $row['count'];

            if ($count == 0) {
                // Insert the data into the database
                $sql = "INSERT INTO corporate2 (CorporateName, CorporateGST, CoporateAddress) VALUES ('$CorporateName', '$CorporateGST', '$CoporateAddress')";
                $response[] = _InsertTableRecords($conn, $sql);
                
            } 
            else {
                $response[] = array(
                    'error' => true,
                    'message' => "Duplicate record found: $CorporateName, $CorporateGST, $CoporateAddress"
                );
            }
        }
    } 

    // Close the file handle
    fclose($handle);

    $response[] = array(
        'error' => false,
        'message' => "File uploaded successfully!"
    );
} 
else {
    $response[] = array(
        'error' => true,
        'message' => "No file uploaded."
    );
}
echo json_encode($response);
$header=("location:../view-corporate");
exit();
?>
