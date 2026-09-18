<?php
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$dir = fopen("Branch_wise_Contact.csv", "r");
$k=0;
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
	$BranchCode = $data[0];
	$POCName = $data[1];
	$POCContact = $data[2];
	$sql = " BranchMobile = '$POCContact',SiteIncharge = '$POCName' where BranchCode = '$BranchCode'";
	$respone = $core->_UpdateTableRecords($conn,'branch',$sql);
	if($respone['error'] == false)
	{
		echo "<br>$BranchCode - Updated";
	}
	else
	{
		echo "<br>$BranchCode - Not Updated - Error -".$respone['error'];
	}
	
}
?>