<?php
	@session_start();
	require_once('../../include/autoloader.inc.php');
	// this file will give client connection object $conn $_SESSION['dwd_OrgID'] must be set
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$core->setTimeZone();
	if(isset($_POST['SubjectName']))
	{
		$SubjectName = $_POST['SubjectName'];
		$form_action = $_POST['form_action'];
		$data = $_POST;
		$data['CreatedDate'] = date("Y-m-d");
		$data['CreatedTime'] = date("H:i:s");
		$data['CreatedBy'] = $_SESSION['pp_email'];
		$IMSSetting = new IMSSetting($conn);
		if($form_action == "add")
		{
			$response = $IMSSetting->CheckDuplicateSubjectName($data);
			if($response == true)
			{
				$response = $IMSSetting->InsertSubjectName($data);
				if($response['error'] == false)
				{
					$response['message'] = "Subject Saved !";
				}
				else
				{
					$response['error'] = true;
					$response['message'] = "Some Technical Error ! Please Try Again.";
				}
			}else{
				$response['error'] = true;
				$response['message'] = "Subject Already Added.";
			}
		}
		else 
		{
			$response = $IMSSetting->UpdateSubjectName($data);
			$response['message'] = "Subject Name Updated !";
		}	
       
		
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Some Technical Error ! Please Try Again.";
	}
	echo json_encode($response);
?>