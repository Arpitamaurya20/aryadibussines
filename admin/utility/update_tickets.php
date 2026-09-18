<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../includes/autoloader.inc.php');
$core = new Core();
// Database connection details
$servername = "localhost"; 
$username = "root"; 
$password = "TechXpert@123"; // Replace with your actual password

// Connect to both databases
$techxpert_conn = new mysqli($servername, $username, $password, "techxpertindia");
$techxpert_old_conn = new mysqli($servername, $username, $password, "techxpert_old");

// Check connection
if ($techxpert_conn->connect_error) {
    die("Connection to techxpertindia failed: " . $techxpert_conn->connect_error);
}
if ($techxpert_old_conn->connect_error) {
    die("Connection to techxpert_old failed: " . $techxpert_old_conn->connect_error);
}

//$sql = "Select * from corporate_tickets_old where ClientTicketID NOT IN (Select ClientTicketID from corporate_tickets)";
$sql = "SELECT * FROM corporate_tickets WHERE CreatedDate BETWEEN '2024-09-01' AND '2024-09-27' and ClientTicketID != ''";
$records = $core->_getSQLRecords($techxpert_conn,$sql);
foreach($records as $record)
{
	//echo "Parsing Ticket {$record['ClientTicketID']} , {$record['ID']} -- <br>";

	// Checking Status
	if($record['Status'] == "Closed" || $record['Status'] == "Cancel" )
	{
		continue;
	}

	$ClientTicketID = $record['ClientTicketID'];
	$where_corporate_tickets_old = " where ClientTicketID = '$ClientTicketID'";
	$details_old = $core->_getTableDetails($techxpert_conn,'corporate_tickets_old',$where_corporate_tickets_old);
	if($details_old != null)
	{
		if($record['Status'] != $details_old['Status'] && $details_old['Status'] == 'Closed')
		{
			echo "ClientTicketID - {$record['ClientTicketID']} - Old Status - {$details_old['Status']}, New Status - {$record['Status']}<br>";
		}
	}

}
die();


// Close connections
$techxpert_conn->close();
$techxpert_old_conn->close();
?>