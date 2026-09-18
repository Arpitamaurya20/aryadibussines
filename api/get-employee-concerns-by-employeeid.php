<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);

try {

    if (empty($data['EmployeeID'])) {
        echo json_encode([
            'error' => true,
            'message' => 'EmployeeID is required'
        ]);
        exit;
    }

    $dbh  = new Dbh();
    $conn = $dbh->_connectodb();

    $employeeId = (int)$data['EmployeeID'];

    $base_url = "https://techxpertindia.in/admin/media/employee_concern/";

    $sql = "SELECT Id, Name, Mobile, Issue, Attachment, IsAnonymous, Status, CreatedAt 
            FROM employee_concerns 
            WHERE CreatedBy = $employeeId
            ORDER BY Id DESC";

    $result = mysqli_query($conn, $sql);

    $list = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $attachmentUrl = null;

        if (!empty($row['Attachment'])) {
            $attachmentUrl = $base_url . $row['Attachment'];
        }

        $list[] = [
            "Id" => (int)$row['Id'],
            "Name" => $row['Name'],
            "Mobile" => $row['Mobile'],
            "Issue" => $row['Issue'],
            "Attachment" => $attachmentUrl,
            "IsAnonymous" => (int)$row['IsAnonymous'],
            "Status" => $row['Status'],
            "CreatedAt" => $row['CreatedAt']
        ];
    }

    echo json_encode([
        'error' => false,
        'message' => 'Concern list fetched successfully',
        'data' => $list
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>