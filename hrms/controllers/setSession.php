<?php
@session_start();

if (isset($_POST['CourseID'])) {
	$_SESSION['CourseID'] = $_POST['CourseID'];
}


if (isset($_POST['type'])) {
	$_SESSION['type'] = $_POST['type'];
}
if (isset($_POST['ID'])) {
	$_SESSION['ID'] = $_POST['ID'];
}

?>