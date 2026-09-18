<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

if (!$data) {
    die("Invalid input");
}

$TaskProgressID = $data['TaskProgressID'];
$TaskID = $data['TaskID'];
$Status = $data['Status'];
$Remarks = $data['Remarks'];

// Your mail logic here
// Example:
$subject = "Task Updated (#$TaskID)";
$message = "Task Progress Updated\nStatus: $Status\nRemarks: $Remarks";

mail("receiver@domain.com", $subject, $message);

echo "Mail Sent";
