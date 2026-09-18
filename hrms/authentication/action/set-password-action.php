<?php
@session_start();
require_once('../../include/autoloader.inc.php');
$response = array();
if(isset($_POST))
{
    $dbh = new Dbh();
    $conn = $dbh->_connectodb();
    $authentication = new Authentication($conn);
    $logPath = dirname(__FILE__) . '/../../logs/password_change.log';
    $data = $_POST;
    $response = $authentication->UpdatePasswordwithTempLinkParameter($data);
    if($response['error'] == false)
    {
        $timestamp = date("Y-m-d H:i:s"); // Define the timestamp
        $logMessage = "[{$timestamp}] Password changed. Data:\n" . print_r($data, true) . "\n";
        file_put_contents($logPath, $logMessage, FILE_APPEND);
        $response['message'] = "Password Updated, Kindly login again using updated credentials";
    }
}
else
{
    $response['error'] = true;
    $response['message'] = "Technical Problem. Please try again";
}
echo json_encode($response);
?>