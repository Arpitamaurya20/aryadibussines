<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = stripos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;
$base_url = $isLocal
    ? 'http://' . $host . '/Projects/aryadibussines/admin/media/vendor_registration/'
    : 'https://techxpertindia.in/admin/media/vendor_registration/';

try {

    if (empty($data['EmployeeID'])) {
        echo json_encode(array(
            'error' => true,
            'message' => 'EmployeeID is required'
        ));
        exit;
    }

    if (empty($data['ID']) && empty($data['RegistrationCode'])) {
        echo json_encode(array(
            'error' => true,
            'message' => 'ID or RegistrationCode is required'
        ));
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    $employeeId = (int) $data['EmployeeID'];

    $where = "vr.EmployeeID = $employeeId AND vr.IsActive = 1";

    if (!empty($data['ID'])) {
        $id = (int) $data['ID'];
        $where .= " AND vr.ID = $id";
    } else {
        $registrationCode = $conn->real_escape_string(trim($data['RegistrationCode']));
        $where .= " AND vr.RegistrationCode = '$registrationCode'";
    }

    $sql = "SELECT
                vr.*,
                sm.Name AS StateManagerApproverName,
                hr.Name AS HrApproverName,
                rej.Name AS RejectedByName
            FROM vendor_registration vr
            LEFT JOIN employees sm ON sm.ID = vr.StateManagerApprovedBy
            LEFT JOIN employees hr ON hr.ID = vr.HrApprovedBy
            LEFT JOIN employees rej ON rej.ID = vr.RejectedBy
            WHERE $where
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception($conn->error);
    }

    if ($result->num_rows === 0) {
        echo json_encode(array(
            'error' => true,
            'message' => 'Vendor registration not found'
        ));
        exit;
    }

    $row = $result->fetch_assoc();

    $documentFields = array(
        'ProfileImage',
        'GstImage',
        'PanImage',
        'AadharImage',
        'CancelCheckImage'
    );

    $documents = array();
    foreach ($documentFields as $field) {
        $documents[$field] = !empty($row[$field]) ? $base_url . $row[$field] : null;
    }

    $responseData = array(
        'ID' => (int) $row['ID'],
        'RegistrationCode' => $row['RegistrationCode'],
        'EmployeeID' => (int) $row['EmployeeID'],
        'Name' => $row['Name'],
        'Mobile' => $row['Mobile'],
        'Email' => $row['Email'],
        'ProfileImage' => $documents['ProfileImage'],
        'BusinessName' => $row['BusinessName'],
        'VendorType' => $row['VendorType'],
        'VendorCategory' => $row['VendorCategory'],
        'Pincode' => $row['Pincode'],
        'City' => $row['City'],
        'StateID' => $row['StateID'] !== null ? (int) $row['StateID'] : null,
        'StateName' => $row['StateName'],
        'GstNumber' => $row['GstNumber'],
        'GstImage' => $documents['GstImage'],
        'PanNumber' => $row['PanNumber'],
        'PanImage' => $documents['PanImage'],
        'AadharNumber' => $row['AadharNumber'],
        'AadharImage' => $documents['AadharImage'],
        'BankName' => $row['BankName'],
        'AccountName' => $row['AccountName'],
        'AccountNumber' => $row['AccountNumber'],
        'IfscCode' => $row['IfscCode'],
        'CancelCheckImage' => $documents['CancelCheckImage'],
        'CurrentAddress' => $row['CurrentAddress'],
        'PermanentAddress' => $row['PermanentAddress'],
        'Remarks' => $row['Remarks'],
        'Status' => $row['Status'],
        'StatusLabel' => getVendorStatusLabel($row['Status']),
        'CreatedDate' => $row['CreatedDate'],
        'CreatedTime' => $row['CreatedTime'],
        'UpdatedDate' => $row['UpdatedDate'],
        'UpdatedTime' => $row['UpdatedTime'],
        'SubmittedAt' => trim(($row['CreatedDate'] ?? '') . ' ' . ($row['CreatedTime'] ?? '')),
        'approval' => array(
            'state_manager' => array(
                'ApprovedBy' => $row['StateManagerApprovedBy'] !== null ? (int) $row['StateManagerApprovedBy'] : null,
                'ApproverName' => $row['StateManagerApproverName'],
                'ApprovedAt' => $row['StateManagerApprovedAt'],
                'Remarks' => $row['StateManagerRemarks']
            ),
            'hr' => array(
                'ApprovedBy' => $row['HrApprovedBy'] !== null ? (int) $row['HrApprovedBy'] : null,
                'ApproverName' => $row['HrApproverName'],
                'ApprovedAt' => $row['HrApprovedAt'],
                'Remarks' => $row['HrRemarks']
            ),
            'rejection' => array(
                'RejectedBy' => $row['RejectedBy'] !== null ? (int) $row['RejectedBy'] : null,
                'RejectedByName' => $row['RejectedByName'],
                'RejectedAt' => $row['RejectedAt'],
                'RejectionReason' => $row['RejectionReason'],
                'RejectedAtStage' => $row['RejectedAtStage']
            )
        ),
        'status_timeline' => buildVendorStatusTimeline($row)
    );

    echo json_encode(array(
        'error' => false,
        'message' => 'Vendor registration details fetched successfully',
        'data' => $responseData
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Server Error: ' . $e->getMessage()
    ));
}

function getVendorStatusLabel($status)
{
    $labels = array(
        'Pending' => 'Pending State Manager Verification',
        'StateManagerApproved' => 'Pending HR Verification',
        'Approved' => 'Approved',
        'Rejected' => 'Rejected'
    );

    return $labels[$status] ?? $status;
}

function buildVendorStatusTimeline($row)
{
    $submittedAt = trim(($row['CreatedDate'] ?? '') . ' ' . ($row['CreatedTime'] ?? ''));
    $currentStatus = $row['Status'];

    $stateManagerStep = array(
        'stage' => 'State Manager Verification',
        'key' => 'state_manager',
        'status' => 'Pending',
        'datetime' => null,
        'remarks' => null,
        'approver_name' => null
    );

    $hrStep = array(
        'stage' => 'HR Verification',
        'key' => 'hr',
        'status' => 'Waiting',
        'datetime' => null,
        'remarks' => null,
        'approver_name' => null
    );

    if ($currentStatus === 'Rejected' && $row['RejectedAtStage'] === 'StateManager') {
        $stateManagerStep['status'] = 'Rejected';
        $stateManagerStep['datetime'] = $row['RejectedAt'];
        $stateManagerStep['remarks'] = $row['RejectionReason'];
        $stateManagerStep['approver_name'] = $row['RejectedByName'];
        $hrStep['status'] = 'Not Required';
    } elseif ($currentStatus === 'Rejected' && $row['RejectedAtStage'] === 'HR') {
        $stateManagerStep['status'] = 'Approved';
        $stateManagerStep['datetime'] = $row['StateManagerApprovedAt'];
        $stateManagerStep['remarks'] = $row['StateManagerRemarks'];
        $stateManagerStep['approver_name'] = $row['StateManagerApproverName'];
        $hrStep['status'] = 'Rejected';
        $hrStep['datetime'] = $row['RejectedAt'];
        $hrStep['remarks'] = $row['RejectionReason'];
        $hrStep['approver_name'] = $row['RejectedByName'];
    } elseif ($currentStatus === 'Pending') {
        $stateManagerStep['status'] = 'Pending';
        $hrStep['status'] = 'Waiting';
    } elseif ($currentStatus === 'StateManagerApproved') {
        $stateManagerStep['status'] = 'Approved';
        $stateManagerStep['datetime'] = $row['StateManagerApprovedAt'];
        $stateManagerStep['remarks'] = $row['StateManagerRemarks'];
        $stateManagerStep['approver_name'] = $row['StateManagerApproverName'];
        $hrStep['status'] = 'Pending';
    } elseif ($currentStatus === 'Approved') {
        $stateManagerStep['status'] = 'Approved';
        $stateManagerStep['datetime'] = $row['StateManagerApprovedAt'];
        $stateManagerStep['remarks'] = $row['StateManagerRemarks'];
        $stateManagerStep['approver_name'] = $row['StateManagerApproverName'];
        $hrStep['status'] = 'Approved';
        $hrStep['datetime'] = $row['HrApprovedAt'];
        $hrStep['remarks'] = $row['HrRemarks'];
        $hrStep['approver_name'] = $row['HrApproverName'];
    }

    return array(
        array(
            'stage' => 'Submitted',
            'key' => 'submitted',
            'status' => 'Completed',
            'datetime' => $submittedAt !== '' ? $submittedAt : null,
            'remarks' => $row['Remarks'],
            'approver_name' => null
        ),
        $stateManagerStep,
        $hrStep,
        array(
            'stage' => 'Final Status',
            'key' => 'final',
            'status' => getVendorStatusLabel($currentStatus),
            'datetime' => $currentStatus === 'Approved'
                ? $row['HrApprovedAt']
                : ($currentStatus === 'Rejected' ? $row['RejectedAt'] : null),
            'remarks' => $currentStatus === 'Rejected' ? $row['RejectionReason'] : null,
            'approver_name' => null
        )
    );
}

?>
