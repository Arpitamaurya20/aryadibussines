<?php
require_once('common_api_header.php');
require_once('../admin/controllers/common_controllers.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
$response = array();

if (
    isset($data['EmployeeID']) &&
    isset($data['imageData'])
) {
    $conn = _connectodb();
    setTimeZone();

    $EmployeeID = (int) $data['EmployeeID'];
    $UpdatedBy = (string) $EmployeeID;

    if ($EmployeeID <= 0) {
        $response['error'] = true;
        $response['message'] = "EmployeeID is required";
        echo json_encode($response);
        exit;
    }

    /* ---------- IMAGE HANDLING ---------- */

    $rawImage = $data['imageData'];
    if (!is_string($rawImage) || $rawImage === '') {
        $response['error'] = true;
        $response['message'] = "Invalid image data";
        echo json_encode($response);
        exit;
    }
    $comma = strpos($rawImage, ',');
    if ($comma !== false && stripos(substr($rawImage, 0, $comma), 'base64') !== false) {
        $rawImage = substr($rawImage, $comma + 1);
    }
    $rawImage = preg_replace('/\s+/', '', $rawImage);
    $imageData = base64_decode($rawImage, true);

    if ($imageData === false || $imageData === '') {
        $response['error'] = true;
        $response['message'] = "Invalid image data";
        echo json_encode($response);
        exit;
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'employees' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = "emp_profile_" . $EmployeeID . "_" . uniqid() . ".jpg";
    $written = file_put_contents($uploadDir . $fileName, $imageData);
    if ($written === false) {
        $response['error'] = true;
        $response['message'] = "Unable to save profile image";
        echo json_encode($response);
        exit;
    }

    /* ---------- UPDATE EMPLOYEE TABLE ---------- */

    $UpdatedDate = date('Y-m-d');
    $UpdatedTime = date('H:i:s');

    $sql = "
        UPDATE employees 
        SET 
            ProfileImage = '$fileName',
            UpdatedDate = '$UpdatedDate',
            UpdatedBy = '$UpdatedBy'
        WHERE ID = '$EmployeeID'
        AND IsActive = 1
    ";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_affected_rows($conn) > 0) {
        $response['error'] = false;
        $response['message'] = "Profile image updated successfully";
        $response['ProfileImage'] = $fileName;
    } else if ($result) {
        $response['error'] = true;
        $response['message'] = "Employee was not updated. Check EmployeeID.";
        @unlink($uploadDir . $fileName);
    } else {
        $response['error'] = true;
        $response['message'] = mysqli_error($conn);
    }

} else {
    $response['error'] = true;
    $response['message'] = "EmployeeID and imageData are required";
}

echo json_encode($response);
