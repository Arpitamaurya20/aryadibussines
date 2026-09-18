<?php
require_once('../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$employee = new Employee($conn);
$sql = "Select DISTINCT(Supervisor) from employees where IsActive = 1";
$result = mysqli_query($conn, $sql);
$data_supervisor_users = array();
if ($result) {
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			if($row != "")
				array_push($data_supervisor_users, $row);
		}
	}
} else {
	//echo $sql;
}
//print_r($data_supervisor_users);
foreach($data_supervisor_users as $supervisor)
{
	$data = array();
	$data['EmployeeRole'] = "Supervisor";
	$data['EmployeeID'] = $supervisor['Supervisor'];
	$employee->InsertRole($data);
}
?>
