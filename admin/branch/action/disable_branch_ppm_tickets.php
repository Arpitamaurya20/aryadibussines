<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_POST['BranchID']))
{
	$BranchID = $_POST['BranchID'];
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$sql_update = " IsActive = 0 where BranchID = $BranchID and (Status = 'Raised' OR Status = 'Assigned' OR Status = 'Work In Progress' OR Status = 'Hold by Customer' or Status = 'Submitted for Closure' or Status = 'Cancel')";
	$response = $core->_UpdateTableRecords($conn,'ppm_tickets',$sql_update);
	if($response['error'] == false)
	{
		$response['message'] = "PPM Tickets Disabled for this Branch";
	}
}
else
{
	$response['error'] = true;
	$response['message'] = "Invalid Operation!";
}
echo json_encode($response);
?>