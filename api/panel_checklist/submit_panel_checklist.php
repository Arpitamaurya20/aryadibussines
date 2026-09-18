<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost"; // or your DB host
$dbname = "techxpertindia";
$username = "root";
$password = "TechXpert@123";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON"]);
    exit;
}

// Prepare SQL
$sql = "INSERT INTO panel_list 
    (Site, Asset, Location, Frequency, WorkGroup, SafetyPrecautions, PerformanceChecklist, ChecklistForSafety, 
     InspectionTestResults, MaterialRequired, SparesUsed, TestEquipmentRequired, Remark) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssssssi",
    $data['Site'],
    $data['Asset'],
    $data['Location'],
    $data['Frequency'],
    $data['WorkGroup'],
    $data['lockTagOutNotice'],
    $data['usePersonalProtectiveDevice'],
    $data['checkIndicationLamp'],
    $data['checkMetersAndSwitches'],
    $data['checkLooseConnections'],
    $data['checkSurroundingCleaning'],
    $data['sparesUsed'],
    $data['Remark']
);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Data saved successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
