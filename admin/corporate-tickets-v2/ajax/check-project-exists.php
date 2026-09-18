<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../../includes/autoloader.inc.php');
@session_start();
$core = new Core();
$UserType = $core->SessionCheck();
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$TicketID = $_POST['TicketID'] ?? '';
$response = ['exists' => false];
if (!empty($TicketID)) {
    $stmt = $conn->prepare("SELECT ID FROM projects WHERE TicketID = ? AND IsActive = 1 LIMIT 1");
    $stmt->bind_param("s", $TicketID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['exists'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
