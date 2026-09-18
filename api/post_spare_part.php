<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (isset($data['EmployeeID']) && isset($data['TicketID']) && isset($data['SparePartIDs']) && is_array($data['SparePartIDs'])) {

    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    $TicketID = intval($data['TicketID']);
    $SparePartIDs = $data['SparePartIDs'];

    // Optional input values
    $CreatedBy  = isset($data['CreatedBy']) ? ($data['CreatedBy']) : 'APPSYSTEM';
    $EmployeeID = isset($data['EmployeeID']) ? intval($data['EmployeeID']) : 0;
    $Remarks    = isset($data['Remarks']) ? trim($data['Remarks']) : "";

    $CreatedDate = date("Y-m-d");
    $CreatedTime = date("H:i:s");

    $inserted = 0;
    $errors = [];

    foreach ($SparePartIDs as $SparePartID) {
        if (!is_numeric($SparePartID)) {
            $errors[] = "Invalid SparePartID: $SparePartID";
            continue;
        }

        // Insert each spare part
        $insertData = [
            "TicketID"    => $TicketID,
            "SparePart"   => $SparePartID,
            "CreatedDate" => $CreatedDate,
            "CreatedTime" => $CreatedTime,
            "IsActive"    => 1,
        ];

        $insertResponse = $core->_InsertTableRecords_prepare($conn, "post_spare_part", $insertData);

        if ($insertResponse['error'] === false) {
            $inserted++;
        } else {
            $errors[] = $insertResponse['message'];
        }
    }

    // ✅ Only insert into history & update ticket if all spare parts inserted successfully
    if ($inserted === count($SparePartIDs)) {

        $statusValue = "Spare Part Sent for Approval";

        // 1️⃣ Insert into corporate_ticket_status_history
        $historyData = [
            "TicketID"    => $TicketID,
            "AssignedTo"  => $EmployeeID,
            "Status"      => $statusValue,
            "Remarks"     => $Remarks,
            "CreatedDate" => $CreatedDate,
            "CreatedTime" => $CreatedTime,
            "CreatedBy"   => $CreatedBy,
        ];

        $core->_InsertTableRecords_prepare($conn, "corporate_ticket_status_history", $historyData);

        // 2️⃣ Update corporate_tickets table with same Status
        $updateData = [
            "Status" => $statusValue
        ];

        $whereData = [
            "ID" => $TicketID
        ];

        $updateResponse = $core->_UpdateTableRecords_prepare($conn, "corporate_tickets", $updateData, $whereData);

        // Combine responses
        if ($updateResponse['error'] === false) {
            $response['error'] = false;
            $response['message'] = "$inserted Spare Part(s) added successfully, status updated, and sent for approval.";
        } else {
            $response['error'] = true;
            $response['message'] = "Spare parts inserted but failed to update ticket status: " . $updateResponse['message'];
        }

    } else {
        $response['error'] = true;
        $response['message'] = "Only $inserted of " . count($SparePartIDs) . " Spare Part(s) added. Errors: " . implode(", ", $errors);
    }

} else {
    $response['error'] = true;
    $response['message'] = "Missing required fields: TicketID or SparePartIDs array.";
}

echo json_encode($response);
?>
