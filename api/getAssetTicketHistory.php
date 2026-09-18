<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../../controllers/common_controllers.php');
include("../../corporate-tickets/controller/corporate_tickets_controller.php");
require_once("../../branch/controller/branch_controller.php");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
$assetID = $_GET['asset_id'] ?? 0;
$assetID = (int)$assetID;
$response = ["status" => "error", "data" => []];

if ($assetID > 0) {
    // --- PPM Tickets ---
    $sqlPPM = "
        SELECT 
            'PPM' AS TicketType,
            pt.ID,
            pt.TicketID,
            pt.CreatedDate,
            pt.CreatedTime,
            pt.CloseDate,
            pt.CloseTime,
            pt.Status,
            pt.DueDate,
            pt.AssignedTo
        FROM ppm_tickets pt
        WHERE pt.BranchAssetID = $assetID
        ORDER BY pt.ID DESC
    ";
    $resPPM = mysqli_query($conn, $sqlPPM);
    while ($row = $resPPM->fetch_assoc()) {
        $response['data'][] = $row;
    }

    // --- Corporate Tickets (AMC / Complaint / Other) ---
    $sqlCorp = "
        SELECT 
            ct.Type AS TicketType,
            ct.ID,
            ct.TicketID,
            ct.CreatedDate,
            ct.CreatedTime,
            ct.CloseDate,
            ct.CloseTime,
            ct.Status,
            ct.DueDate,
            ct.AssignedTo
        FROM corporate_tickets ct
        WHERE ct.BranchAssetID = $assetID
        ORDER BY ct.ID DESC
    ";
    $resCorp = mysqli_query($conn, $sqlCorp);
    while ($row = $resCorp->fetch_assoc()) {
        $response['data'][] = $row;
    }

    // Final response
    $response['status'] = "success";
    $response['message'] = "Asset history fetched successfully";
}

echo json_encode($response);
