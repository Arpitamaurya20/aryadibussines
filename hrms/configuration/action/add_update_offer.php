<?php
	@session_start();
	require_once('../../include/autoloader.inc.php');
	// this file will give client connection object $conn $_SESSION['dwd_OrgID'] must be set
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	$core->setTimeZone();
	if(isset($_POST['offerName']))
	{
		$data = $_POST;
		$form_action=$_POST['offer_form_action'];
		$data['CreatedDate'] = date("Y-m-d");
		$data['CreatedTime'] = date("H:i:s");
		$data['CreatedBy'] = $_SESSION['pp_email'];
		$IMSSetting = new IMSSetting($conn);
		if($form_action == "add")
		{
			$response = $IMSSetting->CheckDuplicateofferName($data);
			if($response == true)
			{
				$response = $IMSSetting->InsertofferName($data);
				if($response['error'] == false)
				{
					$response['message'] = "Offer Saved !";
				}
				else
				{
					$response['error'] = true;
					$response['message'] = "Some Technical Error ! Please Try Again.";
				}
			}else{
				$response['error'] = true;
				$response['message'] = "Offer Already Added.";
			}
		}
		else 
		{
			$response = $IMSSetting->UpdateofferName($data);
			$response['message'] = "Offer Updated !";
		}	
       
		
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Some Technical Error ! Please Try Again.";
	}
	echo json_encode($response);
?>