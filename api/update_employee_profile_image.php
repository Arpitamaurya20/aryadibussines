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

    $EmployeeID = $data['EmployeeID'];
    $UpdatedBy = $data['EmployeeID']; ?? null;

    /* ---------- IMAGE HANDLING ---------- */

    $imageData = base64_decode($data['imageData']);

    if ($imageData === false) {
        $response['error'] = true;
        $response['message'] = "Invalid image data";
        echo json_encode($response);
        exit;
    }

    $uploadDir = "../admin/employee/media/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = "emp_profile_" . $EmployeeID . "_" . uniqid() . ".jpg";
    file_put_contents($uploadDir . $fileName, $imageData);

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

    if ($result) {
        $response['error'] = false;
        $response['message'] = "Profile image updated successfully";
        $response['ProfileImage'] = $fileName;
    } else {
        $response['error'] = true;
        $response['message'] = mysqli_error($conn);
    }

} else {
    $response['error'] = true;
    $response['message'] = "EmployeeID and imageData are required";
}

echo json_encode($response);
