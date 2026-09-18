<?php
require_once('../../includes/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
if (!isset($_POST['ID']) || empty($_POST['ID'])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}
$ID = intval($_POST['ID']);
$sql = "SELECT a.*, b.CompanyName 
        FROM corporate_rate_card a 
        JOIN company b ON a.CompanyID = b.ID 
        WHERE a.ID = $ID LIMIT 1";
$rows = $core->_getSQLRecords($conn, $sql);

if (!empty($rows)) {
    echo json_encode($rows[0]); // return only the first row
} else {
    echo json_encode(["error" => "No record found"]);
}
?>
