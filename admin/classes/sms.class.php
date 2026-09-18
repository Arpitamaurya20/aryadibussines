<?php 
class Sms extends Core
{
	public function __construct()
	{
		$this->setTimeZone();
	}

	function sendOTPSMS($data) 
	{
		$phoneNumber = $data['phonenumber'];
		$otp = $data['otp'];
	    $curl = curl_init();

	    $url = 'https://2factor.in/API/V1/21a23310-23ad-11ef-8b60-0200cd936042/SMS/' . $phoneNumber . '/' . $otp . '/OTP1';

	    curl_setopt_array($curl, array(
	        CURLOPT_URL => $url,
	        CURLOPT_RETURNTRANSFER => true,
	        CURLOPT_ENCODING => '',
	        CURLOPT_MAXREDIRS => 10,
	        CURLOPT_TIMEOUT => 0,
	        CURLOPT_FOLLOWLOCATION => true,
	        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	        CURLOPT_CUSTOMREQUEST => 'GET',
	    ));

	    $response = curl_exec($curl);

	    curl_close($curl);
	}
}
?>