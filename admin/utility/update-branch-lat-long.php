<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$UpdatedDate = date('Y-m-d');

$file = fopen("branch-lat-long.csv", "r");

if (!$file) {
    die("Error: Unable to open CSV file.");
}

$k = 0;

while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {

    // Skip header
    if ($k == 0) {
        $k++;
        continue;
    }

    // DEBUG: Print row
    // echo "<pre>";
    // print_r($data);
    // echo "</pre>";

    $branchID   = trim($data[1]);

    // CHECK YOUR CSV INDEXES HERE
    $branchLat  = trim($data[14]);
    $branchLong = trim($data[15]);

    if ($branchID == '') {
        continue;
    }

    // Validate numeric values
    if (!is_numeric($branchLat) || !is_numeric($branchLong)) {

        echo "❌ Invalid Lat/Long for Branch ID $branchID ";
        echo "(Lat: $branchLat , Long: $branchLong)<br>";

        continue;
    }

    $checkQuery = "SELECT ID FROM branch WHERE ID = '$branchID'";
    $result = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($result) > 0) {

        $updateQuery = "
            UPDATE branch 
            SET 
                Latitude = '$branchLat',
                Longitude = '$branchLong'
            WHERE ID = '$branchID'
        ";

        if (mysqli_query($conn, $updateQuery)) {

            echo "✅ Updated: $branchID<br>";

        } else {

            echo "❌ Error updating $branchID: " . mysqli_error($conn) . "<br>";
        }

    } else {

        echo "⚠️ Skipped (not found): $branchID<br>";
    }
}

fclose($file);

echo "<br><b>Branch update process completed.</b>";
?>