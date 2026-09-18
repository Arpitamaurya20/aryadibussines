<?php 
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$EmployeeID = $_POST['EmployeeID'];
$response = array();
if($EmployeeID == -1)
{
	$response['error'] = true;
	$response['message'] = "Invalid Employee ID";
}
else
{
	$leaves_allowed = $_POST['leaves_allowed'];
	$core = new Core();
	$update_sql = " EmployeeLeave = $leaves_allowed where ID = $EmployeeID";
	$response = $core->_UpdateTableRecords($conn,'employees',$update_sql);
	if($response['error'] == false)
	{
		$response['employee_allowed_leaves'] = $leaves_allowed;
	}
}
echo json_encode($response);
?>
