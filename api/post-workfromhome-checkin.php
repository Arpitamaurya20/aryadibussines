<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

if (
    isset($data['EmployeeID']) &&
    isset($data['Latitude']) &&
    isset($data['Longitude'])
) {

    $dbh  = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    $employeeID = (int)$data['EmployeeID'];

    // Current Date & Time
    $recordDate = date("Y-m-d");
    $inTime     = date("H:i:s");

    // Check if already checked in today
    $sql = "SELECT ID
            FROM employee_attendance
            WHERE EmployeeID = ?
            AND RecordDate = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $response['error'] = true;
        $response['message'] = $conn->error;

        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("is", $employeeID, $recordDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $response['error'] = true;
        $response['message'] = "You have already checked in today.";

    } else {

        $insertData = array(
            "EmployeeID" => $employeeID,
            "RecordDate" => $recordDate,
            "InTime"     => $inTime,
            "Latitude"   => trim($data['Latitude']),
            "Longitude"  => trim($data['Longitude'])
        );

        $response = $core->_InsertTableRecords_prepare(
            $conn,
            "employee_attendance",
            $insertData
        );

        if ($response['error'] == false) {
            $response['message'] = "Check-in successful.";
        }
    }

    $stmt->close();

} else {

    $response['error'] = true;
    $response['message'] = "EmployeeID, Latitude and Longitude are required.";

}

echo json_encode($response);
exit;

?>