<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

header('Content-Type: application/json; charset=utf-8');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

$response = array();

$upload_dir = '../admin/media/vendor_registration/';
$base_url   = 'https://techxpertindia.in/admin/media/vendor_registration/';

/**
 * Save base64 image/document (raw base64 or data URI).
 */
function saveVendorDocument($base64Data, $uploadDir, $prefix, $allowedExtensions = array('jpg', 'jpeg', 'png', 'pdf'))
{
    if (empty($base64Data)) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = 'jpg';

    if (strpos($base64Data, 'base64,') !== false) {
        $parts = explode(';base64,', $base64Data);
        $mimePart = strtolower($parts[0]);

        if (strpos($mimePart, 'pdf') !== false) {
            $extension = 'pdf';
        } elseif (strpos($mimePart, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($mimePart, 'jpeg') !== false || strpos($mimePart, 'jpg') !== false) {
            $extension = 'jpg';
        }

        $fileContent = base64_decode($parts[1]);
    } else {
        $fileContent = base64_decode($base64Data);
    }

    if ($fileContent === false || strlen($fileContent) === 0) {
        return false;
    }

    if (strlen($fileContent) > 5 * 1024 * 1024) {
        return false;
    }

    if (!in_array($extension, $allowedExtensions, true)) {
        return false;
    }

    $fileName = $prefix . '_' . time() . '_' . uniqid() . '.' . $extension;
    file_put_contents($uploadDir . $fileName, $fileContent);

    return $fileName;
}

function generateVendorRegistrationCode($conn)
{
    $datePart = date('Ymd');
    $prefix = 'VND-' . $datePart . '-';

    $sql = "SELECT RegistrationCode FROM vendor_registration
            WHERE RegistrationCode LIKE '" . $conn->real_escape_string($prefix) . "%'
            ORDER BY ID DESC LIMIT 1";

    $result = $conn->query($sql);
    $nextNumber = 1;

    if ($result && $row = $result->fetch_assoc()) {
        $lastCode = $row['RegistrationCode'];
        $lastNumber = (int) substr($lastCode, strrpos($lastCode, '-') + 1);
        $nextNumber = $lastNumber + 1;
    }

    return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
}

function hasDuplicateVendorRegistration($conn, $mobile, $email, $gstNumber, $panNumber)
{
    $mobile = $conn->real_escape_string($mobile);
    $email = $conn->real_escape_string($email);
    $gstNumber = $conn->real_escape_string(strtoupper($gstNumber));
    $panNumber = $conn->real_escape_string(strtoupper($panNumber));

    $sql = "SELECT ID, RegistrationCode, Status FROM vendor_registration
            WHERE IsActive = 1
            AND Status IN ('Pending', 'StateManagerApproved', 'Approved')
            AND (
                Mobile = '$mobile'
                OR Email = '$email'
                OR GstNumber = '$gstNumber'
                OR PanNumber = '$panNumber'
            )
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row;
    }

    return null;
}

$requiredFields = array(
    'EmployeeID',
    'name',
    'mobile',
    'email',
    'gst_number',
    'pan_number',
    'aadhar_number',
    'account_name',
    'account_number',
    'ifsc_code',
    'current_address',
    'permanent_address'
);

$missingFields = array();

foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ));
    exit;
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid email format'
    ));
    exit;
}

if (!preg_match('/^[0-9]{10,15}$/', preg_replace('/\D/', '', $data['mobile']))) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid mobile number'
    ));
    exit;
}

if (!preg_match('/^[A-Z0-9]{15}$/i', trim($data['gst_number']))) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid GST number format'
    ));
    exit;
}

if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/i', trim($data['pan_number']))) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid PAN number format'
    ));
    exit;
}

if (!preg_match('/^[0-9]{12}$/', preg_replace('/\D/', '', $data['aadhar_number']))) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid Aadhar number format'
    ));
    exit;
}

if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/i', trim($data['ifsc_code']))) {
    echo json_encode(array(
        'error' => true,
        'message' => 'Invalid IFSC code format'
    ));
    exit;
}

$dbh  = new Dbh();
$core = new Core();
$conn = $dbh->_connectodb();
$core->setTimeZone();

$duplicate = hasDuplicateVendorRegistration(
    $conn,
    trim($data['mobile']),
    trim($data['email']),
    trim($data['gst_number']),
    trim($data['pan_number'])
);

if ($duplicate) {
    echo json_encode(array(
        'error' => true,
        'message' => 'A vendor registration already exists with the same mobile, email, GST, or PAN.',
        'existing_registration_code' => $duplicate['RegistrationCode'],
        'existing_status' => $duplicate['Status']
    ));
    exit;
}

$documentFields = array(
    'profile_image'    => 'vendor_profile',
    'gst_image'        => 'vendor_gst',
    'pan_image'        => 'vendor_pan',
    'aadhar_image'     => 'vendor_aadhar',
    'cancel_check_image' => 'vendor_cancel_check'
);

$savedDocuments = array();

foreach ($documentFields as $payloadKey => $filePrefix) {
    if (empty($data[$payloadKey])) {
        continue;
    }

    $savedFile = saveVendorDocument($data[$payloadKey], $upload_dir, $filePrefix);

    if ($savedFile === false) {
        echo json_encode(array(
            'error' => true,
            'message' => 'Invalid or oversized file for ' . $payloadKey . ' (max 5MB, jpg/png/pdf only)'
        ));
        exit;
    }

    $savedDocuments[$payloadKey] = $savedFile;
}

$stateId = !empty($data['state_id']) ? (int) $data['state_id'] : null;
$stateName = trim($data['state_name'] ?? '');
$city = trim($data['city'] ?? '');
$pincode = trim($data['pincode'] ?? '');

$registrationCode = generateVendorRegistrationCode($conn);
$createdDate = date('Y-m-d');
$createdTime = date('H:i:s');

$insertData = array(
    'RegistrationCode'   => $registrationCode,
    'EmployeeID'         => (int) $data['EmployeeID'],
    'Name'               => trim($data['name']),
    'Mobile'             => preg_replace('/\D/', '', $data['mobile']),
    'Email'              => trim($data['email']),
    'ProfileImage'       => $savedDocuments['profile_image'] ?? null,
    'BusinessName'       => trim($data['business_name'] ?? ''),
    'VendorType'         => trim($data['vendor_type'] ?? ''),
    'VendorCategory'     => trim($data['vendor_category'] ?? ''),
    'Pincode'            => $pincode,
    'City'               => $city,
    'StateID'            => $stateId ?: null,
    'StateName'          => $stateName,
    'GstNumber'          => strtoupper(trim($data['gst_number'])),
    'GstImage'           => $savedDocuments['gst_image'] ?? null,
    'PanNumber'          => strtoupper(trim($data['pan_number'])),
    'PanImage'           => $savedDocuments['pan_image'] ?? null,
    'AadharNumber'       => preg_replace('/\D/', '', $data['aadhar_number']),
    'AadharImage'        => $savedDocuments['aadhar_image'] ?? null,
    'BankName'           => trim($data['bank_name'] ?? ''),
    'AccountName'        => trim($data['account_name']),
    'AccountNumber'      => trim($data['account_number']),
    'IfscCode'           => strtoupper(trim($data['ifsc_code'])),
    'CancelCheckImage'   => $savedDocuments['cancel_check_image'] ?? null,
    'CurrentAddress'     => trim($data['current_address']),
    'PermanentAddress'   => trim($data['permanent_address']),
    'Remarks'            => trim($data['remarks'] ?? ''),
    'Status'             => 'Pending',
    'CreatedDate'        => $createdDate,
    'CreatedTime'        => $createdTime,
    'IsActive'           => 1
);

$response = $core->_InsertTableRecords_prepare($conn, 'vendor_registration', $insertData);

if (!empty($response['error']) && $response['error'] === true) {
    $response['message'] = 'Technical problem, please try again later. ' . ($response['message'] ?? '');
} else {
    $response['error'] = false;
    $response['message'] = 'Vendor registration submitted successfully. Pending State Manager verification.';
    $response['registration_code'] = $registrationCode;
    $response['status'] = 'Pending';
    $response['document_urls'] = array();

    foreach ($savedDocuments as $key => $fileName) {
        $response['document_urls'][$key] = $base_url . $fileName;
    }
}

echo json_encode($response);
exit;

?>
