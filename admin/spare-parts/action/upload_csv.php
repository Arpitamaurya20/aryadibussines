<?php
include("../../controllers/common_controllers.php");
include('../controller/spare_part_controller.php');

$conn = _connectodb();
setTimeZone();
@session_start();

$response = array();

if (!empty($_FILES["csvFile"]["tmp_name"])) {

    $file = $_FILES["csvFile"]["tmp_name"];

    $CreatedDate = date("Y-m-d");
    $CreatedTime = date("H:i:s");
    $CreatedBy   = $_SESSION['pb_username'];

    $handle = fopen($file, "r");

    // Skip header
    fgetcsv($handle, 1000);

    while (($data = fgetcsv($handle, 1000)) !== false) {

        $SparePartCode = trim($data[0]);
        $SparePart     = trim($data[1]);
        $Price         = trim($data[2]);
        $Categories    = trim($data[3]);
        $UOM           = trim($data[4]);

        // 🔍 Check if SparePartCode exists
        $checkSql = "SELECT ID FROM sparepartlist WHERE SparePartCode = '$SparePartCode' LIMIT 1";
        $checkRes = mysqli_query($conn, $checkSql);

        if ($SparePartCode != '' && mysqli_num_rows($checkRes) > 0) {

            // 🔄 UPDATE EXISTING
            $updateSql = "
                UPDATE sparepartlist SET
                    SparePart  = '$SparePart',
                    Categories = '$Categories',
                    UOM        = '$UOM',
                    Price      = '$Price',
                    IsActive   = 1
                WHERE SparePartCode = '$SparePartCode'
            ";
            mysqli_query($conn, $updateSql);

        } else {

            // ➕ INSERT NEW (New = 1)
            $insertSql = "
                INSERT INTO sparepartlist
                (SparePart, Categories, UOM, Price, CreatedBy, CreatedDate, CreatedTime, New, IsActive)
                VALUES
                ('$SparePart', '$Categories', '$UOM', '$Price',
                 '$CreatedBy', '$CreatedDate', '$CreatedTime', 1, 1)
            ";

            mysqli_query($conn, $insertSql);

            // 🔢 Generate SparePartCode = PRD{ID}
            $lastId = mysqli_insert_id($conn);
            $generatedCode = "PRD" . $lastId;

            $updateCodeSql = "
                UPDATE sparepartlist
                SET SparePartCode = '$generatedCode'
                WHERE ID = $lastId
            ";
            mysqli_query($conn, $updateCodeSql);
        }
    }

    fclose($handle);

    $response = [
        "error" => false,
        "message" => "CSV imported successfully (Insert + Update done)"
    ];

} else {
    $response = [
        "error" => true,
        "message" => "No file uploaded"
    ];
}

echo json_encode($response);
?>
