<?php
require_once('../../includes/autoloader.inc.php');


$dbh = new Dbh();
$conn = $dbh->_connectodb();

$Id = $_POST['Id'];
$Status = $_POST['Status'];

$sql = "UPDATE employee_concerns 
        SET Status='$Status' 
        WHERE Id='$Id'";

$conn->query($sql);

echo json_encode(['success' => true]);
?>