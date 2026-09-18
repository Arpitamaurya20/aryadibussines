<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$base_url = 'https://techxpertindia.in/admin/media/vendor_registration/';

try {

    if (empty($data['EmployeeID'])) {
        echo json_encode(array(
            'error' => true,
            'message' => 'EmployeeID is required'
        ));
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    $employeeId = (int) $data['EmployeeID'];
    $statusFilter = isset($data['Status']) ? trim($data['Status']) : '';

    $sql = "SELECT
                ID,
                RegistrationCode,
                EmployeeID,
                Name,
                BusinessName,
                Mobile,
                Email,
                ProfileImage,
                VendorType,
                VendorCategory,
                City,
                StateName,
                GstNumber,
                PanNumber,
                Status,
                StateManagerApprovedAt,
                HrApprovedAt,
                RejectedAt,
                RejectionReason,
                CreatedDate,
                CreatedTime
            FROM vendor_registration
            WHERE EmployeeID = $employeeId
            AND IsActive = 1";

    if ($statusFilter !== '') {
        $statusEsc = $conn->real_escape_string($statusFilter);
        $sql .= " AND Status = '$statusEsc'";
    }

    $sql .= " ORDER BY ID DESC";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $list = array();

    while ($row = $result->fetch_assoc()) {
        $profileImageUrl = !empty($row['ProfileImage'])
            ? $base_url . $row['ProfileImage']
            : null;

        $list[] = array(
            'ID' => (int) $row['ID'],
            'RegistrationCode' => $row['RegistrationCode'],
            'EmployeeID' => (int) $row['EmployeeID'],
            'Name' => $row['Name'],
            'BusinessName' => $row['BusinessName'],
            'Mobile' => $row['Mobile'],
            'Email' => $row['Email'],
            'ProfileImage' => $profileImageUrl,
            'VendorType' => $row['VendorType'],
            'VendorCategory' => $row['VendorCategory'],
            'City' => $row['City'],
            'StateName' => $row['StateName'],
            'GstNumber' => $row['GstNumber'],
            'PanNumber' => $row['PanNumber'],
            'Status' => $row['Status'],
            'StatusLabel' => getVendorStatusLabel($row['Status']),
            'StateManagerApprovedAt' => $row['StateManagerApprovedAt'],
            'HrApprovedAt' => $row['HrApprovedAt'],
            'RejectedAt' => $row['RejectedAt'],
            'RejectionReason' => $row['RejectionReason'],
            'CreatedDate' => $row['CreatedDate'],
            'CreatedTime' => $row['CreatedTime'],
            'SubmittedAt' => trim(($row['CreatedDate'] ?? '') . ' ' . ($row['CreatedTime'] ?? ''))
        );
    }

    echo json_encode(array(
        'error' => false,
        'message' => 'Vendor registration list fetched successfully',
        'count' => count($list),
        'data' => $list
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

?>
