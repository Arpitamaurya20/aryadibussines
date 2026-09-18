<?php 
include('../../controllers/common_controllers.php');
include('authentication_controller.php');
$response = array();
if(isset($_POST))
{
    $email = $_POST['email'];
    $conn = _connectodb();
    $user_details = getUserDetailfromEmail($conn,$email);
    if($user_details == 0)
    {
    	$response['error'] = true;
    	$response['message'] = "No User Found associated with this email";
    }
    else
    {
    	if($user_details['IsActive'] == 0)
    	{
    		$response['error'] = true;
    		$response['message'] = "User is not activated";
    	}
    	else
    	{
    		setTimeZone();
    		$token_respone = generateToken($conn,$email);
    		if($token_respone['error'] == false)
    		{
    			$data['action'] = "forget_password";
				$data['Email'] = $email;
				$data['token'] = $token_respone['token'];
				$postdata = json_encode($data);
				$result = sendRequest($postdata);
				$response['error'] = false;
    			$response['message'] = "An email has been sent to reset the password, link will be activated only for 60 minutes";
    		}
    		else
    		{
    			$response = $token_respone;
    		}
    	}
    }
}
else
{
	$response['error'] = true;
	$response['message'] = "Unauthorized Access";
}
echo json_encode($response);
?>
