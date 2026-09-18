<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("../../controllers/common_controllers.php");
include('../controller/branch_assets_controller.php');
require_once("../../branch/controller/branch_controller.php");

$conn = _connectodb();
setTimeZone();
@session_start();

$data = $_POST;

// 1️⃣ Get POST data
$bulkDate = isset($data['bulk_ticket_date']) ? $data['bulk_ticket_date'] : null;
$branchID = isset($data['branch_id']) ? $data['branch_id'] : null;

if (!$bulkDate || !$branchID) {
    echo json_encode([
        'success' => false,
        'message' => 'Branch ID or Bulk Date missing'
    ]);
    exit;
}

// 2️⃣ Get CorporateID for this branch
$sqlBranch = "SELECT CompanyID AS CorporateID FROM branch WHERE ID = ?";
$stmt = $conn->prepare($sqlBranch);
$stmt->bind_param("i", $branchID);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Branch not found']);
    exit;
}
$branchRow = $result->fetch_assoc();
$corporateID = $branchRow['CorporateID'];

// 3️⃣ Get all branch assets for this branch
$sqlAssets = "SELECT ID AS BranchAssetID, CreatedBy FROM branch_assets WHERE BranchID = ? AND IsActive = 1";
$stmtAssets = $conn->prepare($sqlAssets);
$stmtAssets->bind_param("i", $branchID);
$stmtAssets->execute();
$resultAssets = $stmtAssets->get_result();

if ($resultAssets->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'No assets found for this branch']);
    exit;
}

// 4️⃣ Insert PPM ticket for each asset
$successCount = 0;
while ($asset = $resultAssets->fetch_assoc()) {
    $branchAssetID = $asset['BranchAssetID'];
    $createdBy = $asset['CreatedBy'];
    $createdDate = date('Y-m-d');
    $createdTime = date('H:i:s');

    // Insert without TicketID first
    $insertSQL = "INSERT INTO ppm_tickets 
        (CorporateID, BranchID, BranchAssetID, PPMDate, CreatedDate, CreatedTime, CreatedBy, Status, IsActive) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, 'Raised', 1)";
    $stmtInsert = $conn->prepare($insertSQL);
    $stmtInsert->bind_param("iiissss", $corporateID, $branchID, $branchAssetID, $bulkDate, $createdDate, $createdTime, $createdBy);

    if ($stmtInsert->execute()) {
        // Get the auto-increment ID
        $insertedID = $conn->insert_id;
        $ticketID = 'CS-PPM-' . $insertedID;

        // Update the TicketID
        $updateSQL = "UPDATE ppm_tickets SET TicketID = ? WHERE ID = ?";
        $stmtUpdate = $conn->prepare($updateSQL);
        $stmtUpdate->bind_param("si", $ticketID, $insertedID);
        $stmtUpdate->execute();

        $successCount++;
    }
}

echo json_encode([
    'success' => true,
    'message' => "$successCount PPM tickets raised successfully!"
]);
?>
