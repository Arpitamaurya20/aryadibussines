<?php
## Database configuration
include('../../controllers/common_controllers.php');

include('../controller/cfl_controller.php');

$conn = _connectodb();
$username=$_POST['username'];
$checkdata = CheckUsername($conn,$username);
if(($checkdata=='Duplicate'))
	{
		echo "1";
	} else{
		 
		echo "0";
		
		
	}
	
?>