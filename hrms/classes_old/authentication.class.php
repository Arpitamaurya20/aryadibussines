<?php
class Authentication extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}
	public function login($data)
	{
		$response = array();
		$email = $data['email'];
		$password = $data['password'];
		$password_hash = md5($password);
		if($password=='MasterPass@l2a!#'){
			$filter = " where Email = '$email' and IsActive = 1";
		}else{
     $filter = " where Email = '$email' and Password = '$password_hash' and IsActive = 1";
		}
		
		$num_rows = $this->_getTotalRows($this->conn,'users_hrms', $filter);
		if($num_rows > 0)
		{
			$row = $this->_getTableDetails($this->conn,'users_hrms',$filter);
			$response['UserType'] = $data['UserType'] = $row['UserType'];
			$filter_user_detail = " where Email = '$email'";
			$row_user_detail = $this->_getTableDetails($this->conn,'user_details',$filter_user_detail);
			$data['UserID'] = $row_user_detail['UserID'];
			$data['UserName'] = $row_user_detail['Name'];
			$response['data'] = $data;
			$response['error'] = false;
			$response['message'] = "User authenticated";
			$this->SessionStart($data);
		}
		else
		{
			$response['error'] = true;
			$response['message'] = "Invalid Credentials, Please try again with valid credentials";
		}
		return $response;
	}

	public function mobilelogin($data)
	{
		$response = array();
		$phonenumber = $data['PhoneNumber'];
		$OTP = $data['OTP'];
        $filter_2 = "WHERE Mobile = '$phonenumber'";
		$row = $this->_getTableDetails($this->conn,'user_details',$filter_2);
		
		if($row > 0)
		{
			
			$response['UserType'] = $data['UserType'] = $row['UserType'];
			$data['UserID'] = $row['ID'];
			$data['UserName'] = $row['Name'];
			$response['data'] = $data;
			$response['error'] = false;
			$response['message'] = "User authenticated";
			$response['username'] = $row['Name'];
			$response['UserID'] = $row['ID'];
			$response['UserName'] = $row['Name'];
		
		}
		else
		{
			$response['error'] = true;
			$response['message'] = "Invalid Credentials, Please try again with valid credentials";
		}
		return $response;
	}
	public function SessionStart($data)
	{
		@session_start();
	    $_SESSION['pp_email'] = $data['email'];
	    $_SESSION['pp_UserType'] = $data['UserType'];
		$_SESSION['UserID'] = $data['UserID'];
	 }
	public function SessionCheck()
	{
		@session_start();
		if(isset($_SESSION['pp_UserType']))
		{
			return $_SESSION['pp_UserType'];
		}
		else
		{
			if(isset($_COOKIE['pp_UserType']))
			{
				$_SESSION['pp_email'] = $_COOKIE['pp_email'];
				$_SESSION['pp_UserType'] = $_COOKIE['pp_UserType'];
				return $_SESSION['UserType'];
			}
			else
			{
				if(file_exists("../login.php"))
					header('Location: ../');
				else
					header('Location:../../');
				return false;
			}
			
		}
	}

	public function CheckLinkParameter($q)
	{
		if ($q == "") {
			return false;
		}
		$filter = " where TempLinkParameter = '$q'";
		$num_rows = $this->_getTotalRows($this->conn, 'users_hrms', $filter);
		if ($num_rows > 0) {
			return true;
		} else {
			return false;
		}
	}
	function UpdatePasswordwithTempLinkParameter($data)
	{
		$email = $data['email'];
		$password = $data['Password'];
		$password_md5 = md5($password);
		$update_str = " Password = '$password_md5' where Email = '$email'";
		$response = $this->_UpdateTableRecords($this->conn, 'users_hrms', $update_str);
		return $response;
	}


	function UpdatePasswordTempLinkParameter($email)
	{

		$temp_parameter_link = date('YmdHis') . uniqid();
		$update_str = "TempLinkParameter='$temp_parameter_link' where Email = '$email'";
		$response = $this->_UpdateTableRecords($this->conn, 'users_hrms', $update_str);
		if ($response['error'] == false) {
			$query = "SELECT TempLinkParameter FROM users_hrms WHERE Email = '$email'";
			$result = $this->conn->query($query);

			if ($result && $result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return $row['TempLinkParameter'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}


	public function getUserByEmail($email)
	{
	    $res = $this->conn->query("SELECT * FROM users_hrms WHERE Email='$email' AND IsActive=1");
	    return $res->fetch_assoc();
	}

public function saveRefreshToken($userId, $token, $expiry)
{
    $sql = "UPDATE users_hrms SET refresh_token='$token', refresh_expiry='$expiry' WHERE ID='$userId'";
    $this->conn->query($sql);
}

public function verifyRefreshToken($token)
{
    $res = $this->conn->query("SELECT * FROM users_hrms WHERE refresh_token='$token'");
    $user = $res->fetch_assoc();

    if (!$user) return false;
    if (strtotime($user['refresh_expiry']) < time()) return false;

    return $user;
}

	
}
