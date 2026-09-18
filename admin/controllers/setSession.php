<?php session_start();
if(isset($_POST['EmployeeID']))
{
	$_SESSION['EmployeeID'] = $_POST['EmployeeID'];
}

if(isset($_POST['BookingID']))
{
	$_SESSION['BookingID'] = $_POST['BookingID'];
}
if(isset($_POST['CorporateHQID']))
{
	$_SESSION['CorporateHQID'] = $_POST['CorporateHQID'];
}

if(isset($_POST['CompanyID']))
{
	$_SESSION['CompanyID'] = $_POST['CompanyID'];
}

if(isset($_POST['BranchID']))
{
	$_SESSION['BranchID'] = $_POST['BranchID'];
}
if(isset($_POST['TicketID']))
{
	$_SESSION['TicketID'] = $_POST['TicketID'];
}

if(isset($_POST['BranchAssetsID']))
{
	$_SESSION['BranchAssetsID'] = $_POST['BranchAssetsID'];
}
if(isset($_POST['City']))
{
	$_SESSION['City'] = $_POST['City'];
}

if(isset($_POST['OrderID']))
{
	$_SESSION['OrderID'] = $_POST['OrderID'];
}
if(isset($_POST['ProjectID']))
{
	$_SESSION['ProjectID'] = $_POST['ProjectID'];
}
if(isset($_POST['EquipmentID']))
{
	$_SESSION['EquipmentID'] = $_POST['EquipmentID'];
}
if(isset($_POST['SiteVisitID']))
{
	$_SESSION['SiteVisitID'] = $_POST['SiteVisitID'];
}
?>