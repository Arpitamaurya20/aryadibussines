<?php 
include('../controllers/common_controllers.php');
$where = " ORDER BY ID ASC";
$conn = _connectodb();
$employees_array = _getTableRecords($conn,'employees',$where);
$i = 1;
foreach($employees_array as $employee)
{
	extract($employee);
	$sql = "INSERT INTO employees_1(DivisionSequence,Name,Division,Department,Email,ContactNumber,PAN,Aadhar,Supervisor,Vendor,ProfileImage,AadharImage,PANImage,PoliceVerificationImage,Gender,UANNumber,DateofJoining,Epf_number,Esic_number,Site,CreatedBy,CreatedDate,CreatedTime,IsActive) VALUES ($i,'$Name','$Division','$Department','$Email','$ContactNumber','$PAN','$Aadhar','$Supervisor','$Vendor','$ProfileImage','$AadharImage','$PANImage','$PoliceVerificationImage','$Gender','$UANNumber','$DateofJoining','$Epf_number','$Esic_number','$Site','$CreatedBy','$CreatedDate','$CreatedTime','$IsActive')";
	$response = _InsertTableRecords($conn, $sql);
	$last_id = $response['last_insert_id'];
	$seq = $last_id;
	$formatted_seq = sprintf('%04d', $seq);
	$EmployeeNumber = "TECHX".$formatted_seq;
	$i++;
	$query_parameter = " EmployeeNumber = '$EmployeeNumber' where ID = $last_id";
	_UpdateTableRecords($conn,'employees_1', $query_parameter);

	// Updating department
	$sql = "Select * from department where DepartmentHead = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$DepartmentID = $row['ID'];
				$sql_update = "Update department SET DepartmentHead = $last_id where ID=$DepartmentID";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	// Updating citydata

	// Updating region
	$sql = "Select * from region where RegionHead = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$RegionID = $row['ID'];
				$sql_update = "Update region SET RegionHead = $last_id where ID=$RegionID";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	$sql = "Select * from region where RegionCorporateHead = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$RegionID = $row['ID'];
				$sql_update = "Update region SET RegionCorporateHead = $last_id where ID=$RegionID";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	// Updating state
	$sql = "Select * from state where StateHead = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$StateID = $row['ID'];
				$sql_update = "Update state SET StateHead = $last_id where ID=$StateID";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	$sql = "Select * from state where StateCorporateHead = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$StateID = $row['ID'];
				$sql_update = "Update state SET StateCorporateHead = $last_id where ID=$StateID";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	// Updating user_divisions
	$sql = "Select * from user_divisions where EmployeeID = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$ud_id = $row['ID'];
				$sql_update = "Update user_divisions SET EmployeeID = $last_id where ID=$ud_id";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	// Updating user_divisions
	$sql = "Select * from user_roles where EmployeeID = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$ur_id = $row['ID'];
				$sql_update = "Update user_roles SET EmployeeID = $last_id where ID=$ur_id";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}

	// Updating users
	$sql = "Select * from users where EmployeeID = $ID";
	$result = mysqli_query($conn, $sql);
	if ($result) {
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$u_id = $row['UserID'];
				$sql_update = "Update users SET EmployeeID = $last_id where UserID=$u_id";
				$result_update = mysqli_query($conn, $sql_update);
			}
		}
	}
}
?>