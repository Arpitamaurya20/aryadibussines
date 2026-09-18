<?php
include("../../controllers/common_controllers.php");

$conn = _connectodb();
setTimeZone();

// Fetch records where SparePartCode is blank or NULL
$sql = "
    SELECT ID 
    FROM sparepartlist
    WHERE SparePartCode IS NULL 
       OR SparePartCode = ''
";

$result = mysqli_query($conn, $sql);

$updated = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $id = $row['ID'];
    $generatedCode = "PRD" . $id;

    $updateSql = "
        UPDATE sparepartlist
        SET SparePartCode = '$generatedCode'
        WHERE ID = $id
    ";

    if (mysqli_query($conn, $updateSql)) {
        $updated++;
    }
}

echo json_encode([
    "error"   => false,
    "updated" => $updated,
    "message" => "Blank SparePartCode updated successfully"
]);
?>
