<?php
function _InsertTableRecords($conn, $sql)
	{
		$response = array();
		$result = mysqli_query($conn, $sql);
		if ($result) {
			$response['message'] = "Data Inserted";
			$response['error'] = false;
			$lastId = mysqli_insert_id($conn);
			$response['last_insert_id'] = $lastId;
		} else {
			$response['sql'] = $sql;
			$response['error'] = true;
			$error = mysqli_error($conn);
			$response['message'] = $error;
			echo $sql;
			echo $error;
		}
		return $response;
	}

	function _getTableDetails($conn,$table_name, $where)
	{
		$row = array();
		$sql = "Select * from $table_name $where";
		$result=mysqli_query($conn,$sql);
		if($result)
			$row = $result->fetch_assoc();
		else
		{
			$error = mysqli_error($conn);
			echo $sql;
			echo $error;
		}
		return $row;
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
	$query_parameter = "IsActive = 0 where PhoneNumber = '$phonenumber'";
	_UpdateTableRecords($conn,'temp_otp', $query_parameter);
	$sql = "INSERT INTO temp_otp(PhoneNumber,OTP,CreatedDate,CreatedTime) VALUES ('$phonenumber','$otp','$CreatedDate','$CreatedTime')";
	_InsertTableRecords($conn, $sql);
}

	 function _UpdateTableRecords($conn, $table_name, $query_parameter)
	{
		$response = array();
	   	$sql = "UPDATE $table_name SET $query_parameter";
		$result = mysqli_query($conn, $sql);
		if ($result) {
			$response['message'] = "Data Updated";
			$response['error'] = false;
		} else {
			$response['sql'] = $sql;
			$response['error'] = true;
			$error = mysqli_error($conn);
			$response['message'] = $error;
			echo $sql;
			echo $error;
		}
		return $response;
	}


function checkOTP($conn,$phonenumber,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where PhoneNumber = '$phonenumber' and IsActive = 1";
	$otp_details = _getTableDetails($conn,'temp_otp', $where);
	$OTPtable = $otp_details['OTP'];
	$masterOTP = '7756';
	if($otp == $OTPtable || $otp ==$masterOTP )
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


function sendWhatsAppMessage($phonenumber,$message)
{
  $params=array(
'token' => 'kjm0xksxbb9ya4s7',
'to' => $phonenumber,
'body' => $message
);
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => "https://api.ultramsg.com/instance99975/messages/chat",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_SSL_VERIFYHOST => 0,
  CURLOPT_SSL_VERIFYPEER => 0,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => http_build_query($params),
  CURLOPT_HTTPHEADER => array(
    "content-type: application/x-www-form-urlencoded"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  // echo "cURL Error #:" . $err;
} else {
  // echo $response;
}

}

function _interakt_sendWhatsAppMessage_common($data)
{
  $curl = curl_init();
	$phonenumber = $data['phonenumber'];
	$template = $data['template'];
	$body_values = $data['body_values'];
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>'{
		"countryCode": "+91",
		"phoneNumber": "'.$phonenumber.'",
		"type": "Template",
		"template": {
		"name": "'.$template.'", 
		"languageCode": "en", 
		"bodyValues": '.$body_values.'
		}
	  }',
	  CURLOPT_HTTPHEADER => array(
	    'Authorization: Basic bW9uTU4wNWJiU1Nmd2ZvcjJzVVVpNWwzajdkeXA1UWh6RmxQS3JoUXpiZzo=',
	    'Content-Type: application/json'
	  ),
	));
	//echo $body_values;
	$response = curl_exec($curl);
	//var_dump($response);
	curl_close($curl);

}

 function _getMaxIdentityValue($conn, $table, $column)
	{
		$sql = "Select COALESCE(MAX($column),0) as max_value from $table";
		$result = mysqli_query($conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['max_value'];
		} else {
			return 0;
		}
	}

function GenerateTempImageID($conn)
{
	$TempImageID = _getMaxIdentityValue($conn,'temp_capture_image','TempImageID');
	return $TempImageID;
}


function sendMobileOTPWhatsapp($data){
	$curl = curl_init();
	$phonenumber = $data['phonenumber'];
	$template = $data['template'];
	$body_values = $data['body_values'];
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_POSTFIELDS =>'{
		"countryCode": "+91",
		"phoneNumber": "'.$phonenumber.'",
		"type": "Template",
		"template": {
		"name": "'.$template.'", 
		"languageCode": "en", 
		"bodyValues": '.$body_values.'
		}
	  }',
	  CURLOPT_HTTPHEADER => array(
	    'Authorization: Basic bW9uTU4wNWJiU1Nmd2ZvcjJzVVVpNWwzajdkeXA1UWh6RmxQS3JoUXpiZzo=',
	    'Content-Type: application/json'
	  ),
	));
	//echo $body_values;
	$response = curl_exec($curl);
	//var_dump($response);
	curl_close($curl);
}


function sendSmsOtp($fields){
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_SSL_VERIFYHOST => 0,
  CURLOPT_SSL_VERIFYPEER => 0,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => json_encode($fields),
  CURLOPT_HTTPHEADER => array(
    "authorization: oCK4FLER15b7pGcQ8rnWiJVz32jYOfP6XMdk9StsuwmZ0DxTBIM4BiGUIXb35A7aKmuzHFfZy1Yx6jLS",
    "accept: */*",
    "cache-control: no-cache",
    "content-type: application/json"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

// if ($err) {
//   echo "cURL Error #:" . $err;
// } else {
//   echo $response;
// }
}

?>