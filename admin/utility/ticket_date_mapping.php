<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$dir = fopen("Tentative_Date.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
	$ClientTicketID = $core->cleantext($data[0]);
	$DueDate = $core->cleantext($data[1]);
	$sql = " DueDate = '$DueDate' where ClientTicketID = '$ClientTicketID'";
	$respone = $core->_UpdateTableRecords($conn,'corporate_tickets',$sql);
	if($respone['error'] == false)
	{
		echo "<br>$ClientTicketID - Updated";
	}
	else
	{
		echo "<br>$ClientTicketID - Not Updated - Error -".$respone['error'];
	}
	
}
?>