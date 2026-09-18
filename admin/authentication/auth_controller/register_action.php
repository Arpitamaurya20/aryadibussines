<?php 
include('../../controllers/common_controllers.php');
include('../../users/controller/user_controller.php');
include('authentication_controller.php');
if(isset($_POST))
{
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $phonenumber = $_POST['phonenumber'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $user_password = $_POST['user_password'];
  
    $conn = _connectodb();
	setTimeZone();
	$current_date = date("Y-m-d");
    $response = insertUser($conn,$fname,$lname,$phonenumber,$email,$username,$user_password,$current_date);
    print_r($response);
    if($response['error'] == false)
    {
       $response['message'] = "Registered Successfully, You can now login to Novologic";
    }
    else
    {
        echo json_encode($response);
    }
}
else
{
    echo "Unauthorized Access";
}
?>