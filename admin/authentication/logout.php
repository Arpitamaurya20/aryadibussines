<?php
if(!isset($_SESSION['pb_username']))
{
	session_start();
}
session_destroy();
if(isset($_COOKIE['pb_username']))
{
	unset($_COOKIE['pb_username']);
	unset($_COOKIE['UserType']);
	unset($_COOKIE['Roles']);
	setcookie('pb_username',"", -1, '/');
	setcookie('UserType',"", -1, '/'); 
	setcookie('Roles',"", -1, '/'); 
}
header('Location: login.php');
?>