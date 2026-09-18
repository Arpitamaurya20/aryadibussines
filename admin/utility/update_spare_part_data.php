<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('../controllers/common_controllers.php');

$conn = _connectodb();
setTimeZone();

$TodayDate = date('Y-m-d');
$TimeNow   = date('H:i:s');
$User      = 'Ashu@123';

$csvFile = "sahihai.csv";

if (!file_exists($csvFile)) {
    echo json_encode(["error" => true, "message" => "CSV file not found"]);
    exit;
}

$handle = fopen($csvFile, "r");

$row = 0;
$updated = 0;
$inserted = 0;

while (($data = fgetcsv($handle, 2000, ",")) !== false) {

    // Skip header
    if ($row++ == 0) continue;

    // CSV FORMAT:
    // 0 = SparePartCode
    // 1 = SparePartName
    // 2 = Price
    // 3 = Category
    // 4 = UOM

    $SparePartCode = ($data[0]);
    $SparePartName = cleantext($data[1]);
    $Price         = cleantext($data[2]);
    $CategoryName  = cleantext($data[3]);
    $UomName       = cleantext($data[4]);

    if ($SparePartCode == '') continue;

    /* ---------- CHECK EXISTENCE ---------- */

    $checkSql = "
        SELECT ID FROM sparepartlist
        WHERE SparePartCode = TRIM(UPPER('$SparePartCode'))
    ";

    $checkRes = mysqli_query($conn, $checkSql);

    if (mysqli_num_rows($checkRes) > 0) {
       
    echo 'true';
        /* ---------- UPDATE ---------- */

        $updateSql = "
            UPDATE sparepartlist SET
                Categories  = '$CategoryName',
                UOM         = '$UomName',
                Price       = '$Price',
                UpdatedBy   = '$User',
                UpdatedDate = '$TodayDate',
                UpdatedTime = '$TimeNow'
            WHERE TRIM(UPPER(SparePartCode)) = TRIM(UPPER('$SparePartCode'))
        ";

        mysqli_query($conn, $updateSql);
        $updated++;

    } else {

        echo 'false';
}

}

fclose($handle);

/* ---------- RESPONSE ---------- */

echo json_encode([
    "error"    => false,
    "updated"  => $updated,
    "inserted" => $inserted,
    "message"  => "Sparepart CSV fully synced (update + insert completed)"
]);
