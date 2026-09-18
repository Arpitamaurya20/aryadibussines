<?php 
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
// Get All States
$where = " where IsActive = 1";
$records = $core->_getTableRecords($conn,'state',$where);
foreach($records as $record)
{
	$StateCorporateHead = $record['StateCorporateHead'];
	$core->setTimeZone();
	$currentDate = date("Y-m-d");
	$currentTime = date("H:i:s");
	$where = " where Role = 'State Corporate Lead' and EmployeeID = $StateCorporateHead";
	$number_of_records = $core->_getTotalRows($conn,'user_roles',$where);
	if($number_of_records == 0)
	{
		$sql_insert_role = " INSERT INTO user_roles(EmployeeID,Role,CreatedDate,CreatedTime)VALUES($StateCorporateHead,'State Corporate Lead','$currentDate','$currentTime')";
		$response = $core->_InsertTableRecords($conn,$sql_insert_role);
		var_dump($response);
	}
}
?>