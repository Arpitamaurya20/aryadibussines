<?php
	@session_start();
	require_once('../../include/autoloader.inc.php');
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	if(isset($_POST['name']))
	{
		$Users = new Users($conn);
		$data = $_POST;
		$form_action = "add";
        if($form_action == "add")
        {
		        $checkdulicate=$Users->CheckDuplicateUser($_POST['email']);
		        if($checkdulicate)
		        {
			        $data['Center'] = 1;
					$data['user_name'] = $_POST['name'];
					$data['user_phone_number'] = $_POST['phone'];
					$data['user_email'] = $_POST['email'];
					$data['subject'] = $_POST['subject'];
					$data['message'] = $_POST['message'];
					$data['user_type'] = 'Student';
					$data['password'] = $_POST['password'];
					$data['CreatedBy'] = $_POST['email'];
					$data['CreatedDate'] = date('Y-m-d');
					$data['CreatedTime'] = date('H:i:s');
					$data['status'] = "Registration";	
					$response = $Users->InsertUser($data);
					$UserID=$response['last_insert_id'];
					$subject= $data['subject'];
					$message= $data['message'];
					
					$data['UserID']=$UserID;
					if($response['error']==false)
					{
						    // Upload profile picture
                if (isset($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['name'] != '') {
                    $extn = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
                    $username = isset($_POST['name']) ? $_POST['name'] : 'user';
                     $fileName = preg_replace('/\s+/', '_', $username) . "_profile." . $extn;

                    $uploadDir = "../../project-assets/admin-media/user-profile/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fullPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $fullPath)) {
                        $relativePath = "project-assets/admin-media/user-profile/" . $fileName;
                        $query = "INSERT INTO userprofile (UserID, ProfilePic) VALUES ($UserID, '$relativePath')";
                        $core->_InsertTableRecords($conn, $query);
                    }
                }
						$response = $Users->UpdateMentorshipSession($data);
						$response['error'] = false;
		                $response['message'] = "You have SuccessFully Registration ";
		                $query = "INSERT INTO  mentroship_enquiry (UserID,Subject,Message) VALUES ($UserID,'$subject', '$message')";
                        $core->_InsertTableRecords($conn, $query);
		                $url ="https://l2a.in/mail/send-registration-mail.php";
		                $postdata = [
						    "UserEmail" => $data['user_email'],
						    "UserName"  => $data['user_name']
						];

						

						$postdata = json_encode($postdata);

						$ch = curl_init($url);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
						curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
						curl_setopt($ch, CURLOPT_USERAGENT, 'api');
						curl_setopt($ch, CURLOPT_TIMEOUT, 2);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
						curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 10);
						curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
						curl_exec($ch);
						curl_close($ch);
					}
		        }else{
		        	$response['error'] = true;
		            $response['message'] = "User Already Found";
		        }
				
			
        }
	    
	   
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "Some Technical Error ! Please Try Again.";
	}
	echo json_encode($response);
?>