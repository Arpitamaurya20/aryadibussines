<?php
function _api_corporate_login_user($conn,$data)
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
?>