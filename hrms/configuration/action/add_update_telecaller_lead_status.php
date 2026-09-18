<?php
	@session_start();
	require_once('../../include/autoloader.inc.php');
	// this file will give client connection object $conn $_SESSION['dwd_OrgID'] must be set
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$core->setTimeZone();
	if(isset($_POST['LeadStatus']))
	{
		$LeadStatus = $_POST['LeadStatus'];
		$form_action = $_POST['form_action'];
		$data = $_POST;
		$data['CreatedDate'] = date("Y-m-d");
		$data['CreatedTime'] = date("H:i:s");
		$data['CreatedBy'] = $_SESSION['pp_email'];
		$IMSSetting = new IMSSetting($conn);
		if($form_action == "add")
		{
			$response = $IMSSetting->CheckDuplicateTelecallerLeadStatus($data);
			if($response == true)
			{
				$response = $IMSSetting->InsertTelecallerLeadStatus($data);
				if($response['error'] == false)
				{
					$response['message'] = "Lead Status Saved !";
				}
				else
				{
					$response['error'] = true;
					$response['message'] = "Some Technical Error ! Please Try Again.";
				}
			}else{
				$response['error'] = true;
				$response['message'] = "Lead Status Already Added.";
			}
		}
		else 
		{
			$response = $IMSSetting->UpdateTelecallerLeadStatus($data);
			$response['message'] = "Lead Status Updated !";
		}	
       
		
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Some Technical Error ! Please Try Again.";
	}
	echo json_encode($response);
?>