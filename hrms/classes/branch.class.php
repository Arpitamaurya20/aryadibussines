<?php
class Branch extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertBranchForm($data)
	{
		$branchname = $data['branchname'];
		//$branchManager = $data['branchManager'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		//$sql = "INSERT INTO branch(BranchName,Manager,CreatedDate,CreatedTime,CreatedBy) VALUES ('$branchname','$branchManager','$CreatedDate','$CreatedTime','$CreatedBy')";
		$sql = "INSERT INTO branch(BranchName,CreatedDate,CreatedTime,CreatedBy) VALUES ('$branchname','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_branch_details = $this->_InsertTableRecords($this->conn, $sql);

		if ($response_insert_branch_details['error'] == false) {
			$response_insert_branch_details['error'] = false;
			$response_insert_branch_details['message'] = "Branch is Successfully Added.";
		} else {
			$response_insert_branch_details['error'] = true;
			$response_insert_branch_details['message'] = "Technical Issue, please try later";
		}
		return $response_insert_branch_details;
	}


	function UpdateBranchForm($data)
	{
		$branchname = $data['branchname'];
		//$branchManager = $data['branchManager'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$branch_id = $data['form_id'];


		$old_branch_details = $this->GetBranchDetailsbyID($branch_id);
		//if($branchname == $old_branch_details['BranchName'] && $branchManager == $old_branch_details['Manager'])
		if ($branchname == $old_branch_details['BranchName']) {
			$response['message'] = "No changes to update";
			$response['error'] = true;
		} else {
			//$update_param = " BranchName = '$branchname', Manager = '$branchManager' where ID=$branch_id";
			$update_param = " BranchName = '$branchname' where ID=$branch_id";
			$response = $this->_UpdateTableRecords($this->conn, 'branch', $update_param);
			$response['error'] = false;
			$response['message'] = "Branch / Center Updated";
		}

		return $response;
	}

	public function GetAllBranch($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
		$branch_details = $this->_getTableRecords($conn, "branch", $where);
		return $branch_details;
	}

	public function GetAllTestSeries($conn)
	{
		$where = "where IsActive = 1 And CourseCat='test_series' ORDER BY ID DESC"; // You can add more conditions if needed
		$test_series = $this->_getTableRecords($conn, "courses_for_display", $where);
		return $test_series;
	}

	public function GetCourses($conn)
	{
		$where = "WHERE IsActive =1 ORDER BY id DESC";
		$paid_courses = $this->_getTableRecords($conn, "courses_for_display", $where);
		return $paid_courses;
	}

	public function SetBranchArrayByID()
	{
		$branch_array = array();
		$where = " where IsActive = 1 ORDER BY ID DESC";
		$branch_details = $this->_getTableRecords($this->conn, "branch", $where);
		foreach ($branch_details as $branch) {
			extract($branch);
			$branch_array[$ID]['BranchName'] = $BranchName;
			$branch_array[$ID]['ManagerID'] = $Manager;
		}
		return $branch_array;
	}

	public function getTotalBranch($table)
	{
		$sql = "Select COUNT(*) as total_enquiries from $table where IsActive = 1";
		$result = mysqli_query($this->conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['total_enquiries'];
		} else {
			return 0;
		}
	}

	function DeleteBranch($data)
	{
		$BranchID = $data['ID'];
		// Delete course
		$where = " where ID = $BranchID";
		$response = $this->delete_identity_filter($this->conn, "branch", $where);
		return $response;
	}

	function GetBranchDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$branch_details = $this->_getTableDetails($this->conn, 'branch', $where);
		return $branch_details;
	}

	function InsertUser($data)
	{
		$Center = $data['branch_id'];
		$user_name = $data['managername'];
		$user_email = $data['manageremail'];
		$user_phone_number = $data['managermobile'];
		$password = $data['password'];
		$user_type = "Center Manager";
		$CreatedDate = $data['CreatedDate'];
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = $data['CreatedTime'];
		$sql = "INSERT INTO user_details(CenterID,Name,Mobile,Email,UserType,CreatedDate,CreatedBy) VALUES ('$Center','$user_name','$user_phone_number','$user_email','$user_type','$CreatedDate','$CreatedBy')";
		$response_insert_user_details = $this->_InsertTableRecords($this->conn, $sql);
		$User_ID = $response_insert_user_details['last_insert_id'];


		if ($response_insert_user_details['error'] == false) {
			$update_param = " Manager = '$User_ID' where ID=$Center";
			$response = $this->_UpdateTableRecords($this->conn, 'branch', $update_param);


			$password_md5 = md5($password);
			$sql = "INSERT INTO users(Email,Password,UserType,CreatedDate,CreatedTime) VALUES ('$user_email','$password_md5','$user_type','$CreatedDate','$CreatedTime')";
			$response_insert_user = $this->_InsertTableRecords($this->conn, $sql);
			return $response_insert_user;
		} else {
			return $response_insert_user_details;
		}
	}
}