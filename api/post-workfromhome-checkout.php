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
    isset($data['CheckoutLatitude']) &&
    isset($data['CheckoutLongitude'])
) {

    $dbh = new Dbh();
    $core = new Core();
    $conn = $dbh->_connectodb();
    $core->setTimeZone();

    $employeeID = (int)$data['EmployeeID'];
    $recordDate = $data['RecordDate'];
    $outTime = $data['OutTime'];    

    // Check if Employee 

    // Find today's attendance record
    $sql = "SELECT ID, InTime
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

    if ($result->num_rows == 0) {

        $response['error'] = true;
        $response['message'] = "Check-in record not found.";

    } else {

        $attendance = $result->fetch_assoc();

        if (!empty($attendance['OutTime'])) {

            $response['error'] = true;
            $response['message'] = "You have already checked out.";

        } else {

            $updateData = array(
                "OutTime" => $outTime,
                "CheckoutLatitude" => trim($data['CheckoutLatitude']),
                "CheckoutLongitude" => trim($data['CheckoutLongitude'])
            );

            $where = array(
                "ID" => $attendance['ID']
            );

            $response = $core->_UpdateTableRecords_prepare(
                $conn,
                "employee_attendance",
                $updateData,
                $where
            );

            if ($response['error'] == false) {
                $response['message'] = "Checkout successful.";
            }
        }
    }

    $stmt->close();

} else {

    $response['error'] = true;
    $response['message'] = "EmployeeID, CheckoutLatitude and CheckoutLongitude are required.";

}

echo json_encode($response);
exit;

?>