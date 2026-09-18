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
    isset($data['AttendanceID']) &&
    isset($data['ApprovalStatus']) 
   
)
 {
     $dbh = new Dbh();
    $conn = $dbh->_connectodb();

    $attendanceID = (int)$data['AttendanceID'];
$approvalStatus = trim($data['ApprovalStatus']);
$approvedBy = (int)$data['ApprovedBy'];
$supervisorApprovedBy = (int)$data['SupervisorApprovedBy'];
$approvedAt = date("Y-m-d H:i:s");
$supervisorApprovedAt= date("Y-m-d H:i:s");
$rejectionReason = "";

    if (
        $approvalStatus == "Rejected" &&
        isset($data['RejectionReason'])
    ) {
        $rejectionReason = trim($data['RejectionReason']);
    }

    // Check attendance exists
    $check = $conn->prepare("SELECT ID FROM employee_attendance WHERE ID=?");
    $check->bind_param("i", $attendanceID);
    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows == 0) {

        $response['status'] = false;
        $response['message'] = "Attendance record not found.";

    } else {

       $stmt = $conn->prepare("
    UPDATE employee_attendance
    SET
        ApprovalStatus=?,
        ApprovedBy=?,
        SupervisorApprovedBy=?,
        ApprovedAt=?,
        SupervisorApprovedAt=?,
        RejectionReason=?
    WHERE ID=?
");

        $stmt->bind_param(
    "siisssi",
    $approvalStatus,
    $approvedBy,
    $supervisorApprovedBy,
    $approvedAt,
    $supervisorApprovedAt,
    $rejectionReason,
    $attendanceID
);

        if ($stmt->execute()) {

            $response['status'] = true;
            $response['message'] = "Attendance updated successfully.";

        } else {

            $response['status'] = false;
            $response['message'] = "Database update failed.";
        }
    }

} else {

    $response['status'] = false;
    $response['message'] = "Required parameters are missing.";

}

echo json_encode($response);
    

 
?>