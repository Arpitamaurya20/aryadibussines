<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_POST['BranchAssetID']))
{
	$BranchAssetID = $_POST['BranchAssetID'];
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$sql_update = " IsActive = 0 where BranchAssetID = $BranchAssetID and (Status = 'Raised' OR Status = 'Assigned' OR Status = 'Work In Progress' OR Status = 'Hold by Customer' or Status = 'Submitted for Closure' or Status = 'Cancel')";
	$response = $core->_UpdateTableRecords($conn,'ppm_tickets',$sql_update);
	if($response['error'] == false)
	{
		$response['message'] = "PPM Tickets Disabled for this Asset";
	}
}
else
{
	$response['error'] = true;
	$response['message'] = "Invalid Operation!";
}
echo json_encode($response);
?>