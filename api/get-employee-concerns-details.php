<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

try {

    if (empty($data['Id'])) {
        echo json_encode([
            'error' => true,
            'message' => 'Concern Id is required'
        ]);
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    $id = (int)$data['Id'];

    $base_url = "https://techxpertindia.in/admin/media/employee_concern/";

    $sql = "SELECT * FROM employee_concerns WHERE Id = $id LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 0) {
        echo json_encode([
            'error' => true,
            'message' => 'Concern not found'
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);

    $attachmentUrl = null;

    if (!empty($row['Attachment'])) {
        $attachmentUrl = $base_url . $row['Attachment'];
    }

    $responseData = [
        "Id" => (int)$row['Id'],
        "Name" => $row['Name'],
        "Mobile" => $row['Mobile'],
        "Issue" => $row['Issue'],
        "Attachment" => $attachmentUrl,
        "IsAnonymous" => (int)$row['IsAnonymous'],
        "CreatedBy" => (int)$row['CreatedBy'],
        "Status" => $row['Status'],
        "CreatedAt" => $row['CreatedAt']
    ];

    echo json_encode([
        'error' => false,
        'message' => 'Concern details fetched successfully',
        'data' => $responseData
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>