<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();

$core = new Core();
$dir = fopen("DELHINCRAUG24.csv", "r");
$k=0;
$city_array = array();
$city_array_temp = $core->_getTableRecords($conn,'citydata',' where 1');
foreach($city_array_temp as $city)
{
	$city_array[$city['CityName']] = $city['CorporateLead'];
}
$employee_obj = new Employee($conn);
$employees_array = $employee_obj->setEmployeeArray("All");
while (($data = fgetcsv($dir, 1000, ",")) !== FALSE) 
{
	echo "<br><hr>";
	$ClientTicketID = $data[0];
	if($k==0)
	{
		$k++;
		continue;
	}
	$where = " where ClientTicketID = '$ClientTicketID'";
	$ticketDetails = $core->_getTableDetails($conn,'corporate_tickets',$where);
	$TicketID = $ticketDetails['ID'];
	echo $ClientTicketID." - ";
	if($ticketDetails == null)
	{
		echo " Not Found";
	}
	else
	{
		$BranchID = $ticketDetails['BranchID'];
		$branch_details = $core->_getTableDetails($conn,'branch',' where ID = '.$BranchID);
		echo $branch_details['BranchCity']. " - ";
		$CorporateLead = $city_array[$branch_details['BranchCity']];
		if(isset($employees_array[$CorporateLead]))
		{
			echo $employees_array[$CorporateLead]['Name']." - ";
		}
		echo $ticketDetails['CloseDate']." - ";
		$ticketFinance = $core->_getTableDetails($conn,'corporate_tickets_finance','where TicketID = '.$TicketID);
		echo "Cost in sheet - ".$data[2]." - ";
		if($ticketFinance != null)
		{
			echo "Portal Price - ".$ticketFinance['C_TotalPrice']." - ";
		}
		else
		{
			echo "Finance not put - ";
		}
	}
}
?>