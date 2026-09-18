<?php
function getAllBookings($conn,$username)
{
	$response = array();
	$response['data'] = array();

	$sql = "Select * from  confirm_booking ORDER BY ID DESC";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)
	{
		while($row = $result->fetch_assoc())
		{
			array_push($response['data'],$row);
		}
		$response['message'] = "Bookings Fetched";
		$response['error'] = false;
	}
	else
	{
		$response['message'] = "No Bookings found!";
		$response['error'] = true;
	}
	return $response;
}

function getAllBookingsByAssignedTo($conn, $AssignedTo){
	$response = array();
	$where = " where AssignedTo = '$AssignedTo' ORDER BY ID DESC";
	$data = _getTableRecords($conn,'confirm_booking', $where);
	
	$response['data'] = $data;
	if(sizeof($data) == 0)
	{
		$response['message'] = "No Bookings found!";
	}
	else
	{
		$response['message'] = "Booking Fetched";
	}
	$response['error'] = false;
	return $response;
}

function getUserBookings($conn,$phonenumber)
{
	$response = array();
	$where = " where CreatedBy = '$phonenumber' ORDER BY BookingDate DESC";
	$data = _getTableRecords($conn,'confirm_booking', $where);
	$response['data'] = $data;
	if(sizeof($data) == 0)
	{
		$response['message'] = "No Services Booked till now";
	}
	else
	{
		$response['message'] = "Booking Fetched";
	}
	$response['error'] = false;
	return $response;
}

function getBookingDetail($conn,$BookingID)
{
	$response = array();
	$response['data'] = array();

	$sql = "Select * from  confirm_booking where ID = $BookingID";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)
	{
		$row = $result->fetch_assoc();

		$response['data'] = $row;
		if($response['data']['AssignedTo'] != -1 && $response['data']['AssignedTo'] != "")
		{
			$AssignedTo = $response['data']['AssignedTo'];
			$where = " where ID = $AssignedTo";
			$respone_employee = _getTableDetails($conn,'employees', $where);
			if(isset($respone_employee['Name']))
				$response['data']['EmployeeName'] = $respone_employee['Name'];

		}
		if($response['data']['Service_name'] == "Laundry & Dry Cleaning")
		{
			$where = " where BookingID = $BookingID";
	        $laundry_booking_array = _getTableRecords($conn,'laundry_booking',$where);
			foreach ($laundry_booking_array as &$laundry) {
				$SubService_ID = $laundry['SubServiceID'];
				$service_where = " where ID = $SubService_ID";
	            $sub_service_detail = _getTableDetails($conn,'subservice', $service_where);
				$laundry['SubServiceName'] = $sub_service_detail['title']; 

				$TypeofClothes_ID = $laundry['TypeofClothesID'];
				$Type_of_clothes_where = " where ID = $TypeofClothes_ID";
	            $laundry_sub_service_detail = _getTableDetails($conn,'laundry_sub_service', $Type_of_clothes_where);
				$laundry['TypeofClothesName'] = $laundry_sub_service_detail['TypeOfClothes']; 
			}
			$response['data']['LaundryData'] = $laundry_booking_array;

		}
		$response['message'] = "Booking Details Fetched";
		$response['error'] = false;
	}
	else
	{
		$response['message'] = "No Bookings found!";
		$response['error'] = true;
	}
	return $response;
}

function getBookingStatus($conn)
{
	$response = array();
	$response['data'] = array();

	$sql = "Select * from  booking_status where IsActive = 1";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)
	{
		while($row = $result->fetch_assoc())
		{
			array_push($response['data'],$row['Status']);
		}
		$response['message'] = "Status Fetched";
		$response['error'] = false;
	}
	else
	{
		$response['message'] = "No Status active!";
		$response['error'] = true;
	}
	return $response;
}

function _api_changeBookingStatus($conn,$data)
{
	// twm
	$BookingID = $data['BookingID'];
	$BookingStatus = $data['BookingStatus'];
	$AssignedTo = $data['AssignedTo'];
	$UpdatedBy = $data['UpdatedBy'];
	$UpdatedDate = $data['UpdatedDate'];
	$UpdatedTime = $data['UpdatedTime'];
	if($BookingStatus == "Generate OTP for Closure")
	{

	}
	else
	{
		$sql = " Status = '$BookingStatus',AssignedTo = $AssignedTo,UpdatedBy='$UpdatedBy',UpdatedDate='$UpdatedDate',UpdatedTime='$UpdatedTime' where ID = $BookingID";
		$response = _UpdateTableRecords($conn,'confirm_booking', $sql);
	}

	if($response['error'] == false || $BookingStatus == "Generate OTP for Closure")
	{
		$response['message'] = "Booking Updated!";
		// get booking data 
        $get_booking_data = getOneBookingData($conn,$BookingID);
		$Cityname = $get_booking_data['City_name'];
		$UserPhone = $get_booking_data['Phone'];
		$UserName = $get_booking_data['Name'];
		$Service = $get_booking_data['Service_name'];
		$SubService = $get_booking_data['SubService'];
		$PaymentStatus = $get_booking_data['PaymentStatus'];
		
		$BookingDate = $get_booking_data['BookingDate'];
		$customer_address = $get_booking_data['Customer_address'];
		$location_landmark = $get_booking_data['Location_landmark'];

		// Get Employeee details from Ticket
		$EmployeeID = $AssignedTo;
		$where = " where ID = $EmployeeID";
		$employees_Detail = _getTableDetails($conn,'employees',$where);
		$EmployeesPhone = $employees_Detail['ContactNumber'];
		$EmployeeName = $employees_Detail['Name'];

		if($BookingStatus == "Assigned")
		{
			$BookingStartOTP = $data['BookingStartOTP'];
			$sql = "BookingStartOTP = '$BookingStartOTP' where ID = $BookingID";
			$response = _UpdateTableRecords($conn,'confirm_booking', $sql);
			$BookingID = $get_booking_data['BookingID'];
		    $data['phonenumber'] = $UserPhone;
			$body_values = '["'.$UserName.'","'.$BookingStatus.'","'.$BookingID.'","'.$Service.'","'.$SubService.'","'.$customer_address.$location_landmark.'","'.$UserPhone.'","'.$BookingDate.'","'.$EmployeeName.'","'.$EmployeesPhone.'","'.$BookingStartOTP.'"]';
		    $data['body_values'] = $body_values;
		    $data['template'] = "glf_cus_bkg_assign_v1";
		    _interakt_sendWhatsAppMessage_common($data);
		    if($EmployeesPhone != "")
			{
				$AssignedTo = $EmployeeName;
				$message = "Dear $AssignedTo, \n\nA Booking with the following details are assigned to you.\n\nBooking ID - $BookingID\n\nService - $Service\n\nSubservice - $SubService\n\nCustomer Name - $UserName\n\nCustomer Phone - $UserPhone\n\nLocation - $customer_address.$location_landmark\n\nPlease check and complete the booking in definite time\n\nRegards,\nTechXpert Team";
			   	$Employees_Phone = "+91".$EmployeesPhone;
		   		sendWhatsAppMessage($Employees_Phone,$message);
			}
		}		
		if($BookingStatus == "Generate OTP for Closure")
		{
			$BookingCloseOTP = $data['BookingCloseOTP'];
			$sql = "BookingCloseOTP = '$BookingCloseOTP' where ID = $BookingID";
			$response = _UpdateTableRecords($conn,'confirm_booking', $sql);

			$link = "https://goodlifefacilities.com/pay-booking-amount.php?BookingID=".$BookingID;
			$BookingID = $get_booking_data['BookingID'];

		    $data['phonenumber'] = $UserPhone;
		    if($PaymentStatus == "Not Paid" || $PaymentStatus == "Pay Later")
		    {

				$body_values = '["'.$UserName.'","'.$BookingCloseOTP.'","'.$link.'","'.$BookingID.'","'.$Service.'","'.$SubService.'","'.$customer_address.$location_landmark.'","'.$UserPhone.'","'.$BookingDate.'"]';
			    $data['body_values'] = $body_values;
			    $data['template'] = "glf_cus_bkg_sfc_pnd_v1";
			    _interakt_sendWhatsAppMessage_common($data);
			    if($EmployeesPhone != "")
				{
					$AssignedTo = $EmployeeName;
					$message = "Dear $AssignedTo, \n\nYou have submitted Booking for closure that is assigned to you.\n\nBooking ID - $BookingID\n\nService - $Service\n\nSubservice - $SubService\n\nCustomer Name - $UserName\n\nCustomer Phone - $UserPhone\n\nLocation - $customer_address.$location_landmark\n\nThe payment is not done by customer, so kindly collect payment using this link - $link and then submit the OTP\n\nRegards,\nTechXpert Team";
				   	$Employees_Phone = "+91".$EmployeesPhone;
			   		sendWhatsAppMessage($Employees_Phone,$message);
				}
			}
			if($PaymentStatus == "Paid")
		    {

				$body_values = '["'.$UserName.'","'.$BookingCloseOTP.'","'.$BookingID.'","'.$Service.'","'.$SubService.'","'.$customer_address.$location_landmark.'","'.$UserPhone.'","'.$BookingDate.'"]';
			    $data['body_values'] = $body_values;
			    $data['template'] = "glf_cus_bkg_sfc_pd_v1";
			    _interakt_sendWhatsAppMessage_common($data);
			    if($EmployeesPhone != "")
				{
					$AssignedTo = $EmployeeName;
					$message = "Dear $AssignedTo, \n\nYou have submitted Booking for closure that is assigned to you.\n\nBooking ID - $BookingID\n\nService - $Service\n\nSubservice - $SubService\n\nCustomer Name - $UserName\n\nCustomer Phone - $UserPhone\n\nLocation - $customer_address.$location_landmark\n\nThe payment is already done by the customer.\n\nRegards,\nTechXpert Team";
				   	$Employees_Phone = "+91".$EmployeesPhone;
			   		sendWhatsAppMessage($Employees_Phone,$message);
				}
			}
		}		
	}
	return $response;
}

function BookingPayLater($conn,$data)
{
	$BookingID = $data['BookingID'];
	$get_booking_data = getOneBookingData($conn,$BookingID);
	$sql_update = " PaymentStatus = 'Pay Later' where ID = $BookingID";
	$response = _UpdateTableRecords($conn,'confirm_booking', $sql_update);
	if($response['error'] == false)
	{
		$response['message'] = "Booking Payment Status updated!";
		$UserPhone = $get_booking_data['Phone'];
		$UserName = $get_booking_data['Name'];
		$Service = $get_booking_data['Service_name'];
		$SubService = $get_booking_data['SubService'];	
		$BookingDate = $get_booking_data['BookingDate'];
		$customer_address = $get_booking_data['Customer_address'];
		$location_landmark = $get_booking_data['Location_landmark'];
		$link = "https://goodlifefacilities.com/pay-booking-amount.php?BookingID=".$BookingID;
		$BookingNumber = $get_booking_data['BookingID'];
		$body_values = '["'.$UserName.'","'.$link.'","'.$BookingNumber.'","'.$Service.'","'.$SubService.'","'.$customer_address.$location_landmark.'","'.$UserPhone.'","'.$BookingDate.'"]';
		if($UserPhone != "")
		{
			$data['phonenumber'] = $UserPhone;
		    $data['body_values'] = $body_values;
		    $data['template'] = "glf_cus_bkg_pay_later_v1";
		    _interakt_sendWhatsAppMessage_common($data);
		}
	}
	return $response;

}

function checkBookingOTP($conn,$BookingID,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where ID = '$BookingID'";
	$otp_details = _getTableDetails($conn,'confirm_booking', $where);
	$OTPtable = $otp_details['BookingStartOTP'];
	if($otp == $OTPtable)
	{
		$response["error"] = false;
		$response["message"] = "Booking OTP is validated!";
		$response["ID"] = $BookingID;

	}
	else
	{
		$response["error"] = true;
		$response["message"] = "OTP not validated! Either the OTP is expired or incorrect!";
	}
	return $response;

}
function checkBookingOTPforClosure($conn,$BookingID,$otp)
{
	$response = array();
	$CurrentDate = date("Y-m-d");
	$CurrentTime = date("H:i:s");
	$where = " where ID = '$BookingID'";
	$otp_details = _getTableDetails($conn,'confirm_booking', $where);
	$OTPtable = $otp_details['BookingCloseOTP'];
	if($otp == $OTPtable)
	{
		$response["error"] = false;
		$response["message"] = "Booking Close OTP is validated!";
		$response["ID"] = $BookingID;

	}
	else
	{
		$response["error"] = true;
		$response["message"] = "OTP not validated! Either the OTP is expired or incorrect!";
	}
	return $response;

}
function deletebooking($conn,$id)
{
	$sql = "Delete from confirm_booking where id = ".$id;
	$result_delete_data = mysqli_query($conn,$sql);
	//echo $sql;
	if(!$result_delete_data)
	{
		mysqli_error($conn,$sql);
		//echo $sql;
	}
	else
	{

		return false;
	}
}


function getOneBookingData($conn, $ID)
{
    $where = " Where ID = $ID";
    $Booking_Details = _getTableDetails($conn, "confirm_booking", $where);
    return $Booking_Details;
}


function getBookingStatusArray($conn)
{
	$where = " where IsActive = 1";
	$roles_array = _getTableRecords($conn,'booking_status',$where);
	return $roles_array;
}

function ManageBookingAssignmentStatus($conn,$data)
{
	$response = array();
	$BookingID = $data['BookingID'];
	$old_booking_data = getOneBookingData($conn,$BookingID);
	if($old_booking_data['AssignedTo'] == $data['assigned_employee'] && $old_booking_data['Status'] == $data['booking_status'])
	{
		$response['message'] = "Either change Status or Assigned Employee!";
		$response['error'] = true;
	}
	else
	{
		$data['BookingStatus'] = $data['booking_status'];
		$data['AssignedTo'] = $data['assigned_employee'];
		$response = _api_changeBookingStatus($conn,$data);

	}


	return $response;
}

function CreateBooking($conn,$data,$division)
{
	$Division = $division;
	$booking_name = $data["bookingName"];
    $phone_number = $data["phoneNumber"];
    $booking_email = $data["bookingEmail"];
    $service_name = $data["serviceName"];
    $Subservice = "";
    if(isset($data["Subservice"]))
    {
    	$Subservice = $data["Subservice"];
    }
    $Price = "";
    if(isset($data["Price"]))
    {
    	$Price = $data["Price"];
    }
	// $Subservice = $data["subservice"];
    $customer_address = $data["customerAddress"];
    $city_name = $data["cityName"];
    $state_name = $data["stateName"];
    $location_landmark = $data["locationLandmark"];
    $subject = $data["subject"];
    $CreatedBy = $data["phoneNumber"];
    $Employee_PhoneNumber = "";

	$SourceType = "";
    if(isset($data["SourceType"]))
    {
    	$SourceType = $data["SourceType"];
    }

    // Get City Corporate Lead
   	$where = " where CityName = '$city_name'";
   	$result_city_lead = _getTableDetails($conn,'citydata',$where);
   	$CityLead = $result_city_lead['CityLead'];
   	$AssignedTo = -1;
   	$Supervisor_PhoneNumber = "Not Set";
   	$Supervisor_Name = "Not Set";
	if($CityLead != -1 && $CityLead != "")
	{
		$AssignedTo = $CityLead;
		$where_emp = " where ID = $AssignedTo";
   		$result_emp = _getTableDetails($conn,'employees',$where_emp);
   		$Employee_PhoneNumber = $Supervisor_PhoneNumber = $result_emp['ContactNumber'];
   		$Employee_name = $Supervisor_Name = $result_emp['Name'];
	}

    $BookingDate = date('Y-m-d');
    $BookingTime = date('H:i:s');
    $attach_file = "";
    $postal_code = "";
	$data["subservice"] ="";

    // Get initials
	$where = " where Division = '$Division' and IsActive = 1";
	$division_row = _getTableDetails($conn,'employee_divisions', $where);
	$Initials = $division_row['Initials'];

    $where_query = " where Division = '$Division'";
	$max_seq = _getMaxIdentityValue_filter($conn,'confirm_booking','DivisionSequence', $where_query);
	$seq = $max_seq+1;
	$formatted_seq = sprintf('%05d', $seq);
	$BookingID = $Initials.$formatted_seq;

	$data['service_name'] = $service_name;
	$data['sub_service'] = $Subservice;
	$data['customer_address'] = $customer_address;
	$data['location_landmark'] = $location_landmark;
	$data['city_name'] = $city_name;
	$data['state_name'] = $state_name;
	$data['BookingDate'] = $BookingDate;
	$data['service_name'] = $service_name;

    $book_query = "INSERT INTO confirm_booking (BookingID,Division,DivisionSequence,Name, Phone, Email, Service_name,SubService, Price,Customer_address, City_name,State_name, Location_landmark, PostalCode, Subject, File_path,BookingDate,BookingTime,CreatedBy,UpdatedBy,UpdatedDate,UpdatedTime,AssignedTo,SourceType) VALUES('$BookingID','$Division',$seq,'$booking_name','$phone_number','$booking_email','$service_name','$Subservice','$Price','$customer_address','$city_name','$state_name', '$location_landmark','$postal_code', '$subject','$attach_file', '$BookingDate','$BookingTime','$CreatedBy','system','$BookingDate','$BookingTime','$AssignedTo','$SourceType')";

		if($phone_number != "")
		{
			if($location_landmark == "")
			{
				$location_landmark = " ";
			}
			if($state_name == null || $state_name == "")
			{
				$state_name = " ";
			}
			 $message = "Dear $booking_name,\n\nThank you for booking our $service_name ($Subservice) services for your needs. We have received your booking with following details:\n\n🧰 $service_name ($Subservice) \n📍 $customer_address $location_landmark $city_name , $state_name \n☎️ $phone_number\n🗓️ $BookingDate\n\n💰 Rs. $Price /-\n\nOut team will get in touch with you very soon. Thank you for choosing our $service_name services, and we look forward to serving you soon!😊🤝 \n\n🌐 Our Website - www.goodlifefacilities.com\n\nRegards,\nGoodLifeFacilities Team,\nTollfree No - 18001201343";
			
		    $phonenumber = "+91".$phone_number;
		    // twm
		    $data['phonenumber'] = $phone_number;
		    $body_values = '["'.$booking_name.'","'.$service_name.'","'.$Subservice.'","'.$BookingID.'","'.$customer_address.$location_landmark.'","'.$phone_number.'","'.$BookingDate.'","'.$Supervisor_Name.'","'.$Supervisor_PhoneNumber.'"]';
		    $data['body_values'] = $body_values;
		    $data['template'] = "booking_techx_service";
			//sendWhatsAppMessage($phonenumber,$message);
			_interakt_sendWhatsAppMessage_common($data);
		}
  
	if($Employee_PhoneNumber != "")
	{
		if($location_landmark == "")
		{
			$location_landmark = " ";
		}
		if($state_name == null || $state_name == "")
		{
			$state_name = " ";
		}
		$message = "Dear $Employee_name,\n\nWe have received a booking with following detials:\n\nBooking ID - $BookingID\n\n🧰 $service_name ($Subservice) \n📍 $customer_address $location_landmark $city_name \n☎️ $phone_number\n🗓️ $BookingDate\n\n Please take approporate action !\n\nRegards,\nTechXpert Team\n\nApplication Link - https://play.google.com/store/apps/details?id=io.ionic.workapp\n\n";
		$data['phonenumber'] = $Employee_PhoneNumber;
		 $phonenumber = "+91".$Employee_PhoneNumber;
		sendWhatsAppMessage($phonenumber,$message);
	    /*$body_values = '["'.$service_name.'","'.$Subservice.'","'.$customer_address.'","'.$location_landmark.'","'.$city_name.'","'.$state_name.'","'.$phone_number.'","'.$BookingDate.'","'.$Employee_name.'"]';
	    $data['body_values'] = $body_values;
	    $data['template'] = "HC_BK_MSG_TO_EMP_1";*/
			//sendWhatsAppMessage($phonenumber,$message);
		//_interakt_sendWhatsAppMessage_common($data);
	   
	}

    $book_result = _InsertTableRecords($conn, $book_query);
    $post_mail_data['action'] = "New Home Care Booking";
	$post_mail_data['Name'] = $booking_name;
	$post_mail_data['Email'] = $booking_email;
	$post_mail_data['Service_name'] = $service_name;
	$post_mail_data['Phone'] = $phone_number;
	// sendMailRequest($post_mail_data);
    $last_insert_id = $book_result['last_insert_id'];
    $booking_response = array();
    if ($book_result)
    {
        $booking_response['error'] = false;
        $booking_response['message'] = "Booking is Confirmed. Thank you for contacting Us";
		$booking_response['BookingID'] = $last_insert_id;
    } else {
        // echo mysqli_error($conn);
        $booking_response['error'] = true;
        $booking_response['emessage'] = "Please Contact to Administrtor. There is some technical issue.";
    }
    return $booking_response;
}

function getTotalBookings($conn)
{
	$sql = "Select COUNT(*) as booking_count from confirm_booking where 1";
	$result=mysqli_query($conn,$sql);
	if($result->num_rows>0)	
	{
		$row = $result->fetch_assoc();
		return $row['booking_count'];
	}
	else
	{
		return 0;
	}
}

function getRatingByBookingID($conn,$BookingID)
{
	$where = " where TicketID = '$BookingID'";
	$response = _getTableDetails($conn, 'customer_rating', $where);
	return $response;
}

function CreateLaundryBooking($conn,$data){

	$itemtypeArr = $data['itemtype'];
	$itemquantityArr = $data['itemquantity'];
	$itempriceArr = $data['itemprice'];
	$BookingID = $data['BookingID'];

	$CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

	foreach ($itemtypeArr as $key => $val) {

		$itemtype = $val;
		$itemquantity = $itemquantityArr[$key];
		$itemprice = $itempriceArr[$key];

		$where = " where ID = '$itemtype'";
	    $Get_type_of_clothes_details = _getTableDetails($conn, 'laundry_sub_service', $where);
		$SubService_ID = $Get_type_of_clothes_details['SubServiceID'];

		if ($itemtype != '' && $itemquantity != '') {
			
			$ticket_finance_sql = "INSERT INTO laundry_booking(BookingID,SubServiceID,TypeofClothesID,Quantity,Price,CreatedDate,CreatedTime) VALUES ('$BookingID','$SubService_ID','$itemtype','$itemquantity','$itemprice','$CreatedDate','$CreatedTime')";
			$booking_response = _InsertTableRecords($conn,$ticket_finance_sql);
			$booking_response['error'] = false;
            $booking_response['message'] = "Booking is Confirmed. Thank you for contacting Us";
		}
	}

	
	return $booking_response;
}

function getLaundryBookingByBookingID($conn,$BookingID)
{
	$where = " where BookingID = $BookingID";
	$laundry_booking_array = _getTableRecords($conn,'laundry_booking',$where);
	return $laundry_booking_array;
}

function InsertHourlyServiceDetails($conn,$data)
{
	$start_date = cleantext($data["start_date"]);
	$end_date = $data["end_date"];
    $start_time = cleantext($data["start_time"]);
    $end_time = cleantext($data["end_time"]);
	$BookingID = cleantext($data["BookingID"]);
	$SubServiceID = cleantext($data["SubServiceID"]);

	$where = " where ID = $SubServiceID";
    $subservice_details = _getTableDetails($conn,'subservice',$where);
	$HourlyPrice_temp = $subservice_details['sub_service_price'];
	$HourlyPrice = preg_replace('/\D/', '', $HourlyPrice_temp);


    $CreatedBy = $data['CreatedBy'];
    $CreatedDate = date('Y-m-d');
    $CreatedTime = date('H:i:s');

	$startDateTime = $start_date . ' ' . $start_time;
	$endDateTime = $end_date . ' ' . $end_time;

	// Create DateTime objects
	$start = new DateTime($startDateTime);
	$end = new DateTime($endDateTime);

	// Calculate the difference
	$interval = $start->diff($end);

	// Convert the difference to hours
	$hours = $interval->days * 24 + $interval->h + $interval->i / 60 + $interval->s / 3600;

	$Duration = number_format($hours, 2);

	$TotalPrice = $HourlyPrice * $Duration;


    $hourly_service_booking_query = "INSERT INTO hourly_service_booking (BookingID,SubServiceID,HourlyPrice,StartDate,StartTime,EndDate,EndTime,Duration,TotalPrice,CreatedBy,CreatedDate,CreatedTime ) VALUES('$BookingID','$SubServiceID','$HourlyPrice','$start_date','$start_time','$end_date','$end_time','$Duration','$TotalPrice','$CreatedBy','$CreatedDate','$CreatedTime')";
    $response = _InsertTableRecords($conn, $hourly_service_booking_query);
    $response['message'] = "Hourly Added in the system";
    
    
    return $response;
}



	function CreateServiceBooking($conn, $data)
{
    // 1️⃣ Get City Lead
    $CityLeadID = 0;
    $city = cleantext($data['cityName']);
    $cityRow = _getTableDetails($conn, 'citydata', " WHERE CityName = '$city'");

    if (!empty($cityRow['CityLead'])) {
        $CityLeadID = $cityRow['CityLead'];
    }

    // 2️⃣ Generate Booking Code
    $nextId = _getMaxIdentityValue($conn, 'service_bookings', 'ID') + 1;
    $BookingCode = "BK" . date("Ymd") . sprintf('%05d', $nextId);

    // 3️⃣ Prepare Insert Data
    $insertData = [
        "BookingCode"   => $BookingCode,
        "CustomerName"  => $data['bookingName'],
        "Phone"         => $data['phoneNumber'],
        "Email"         => $data['bookingEmail'],
        "ServiceName"   => $data['serviceName'],
        "SubService"    => $data['Subservice'],
        "Price"         => $data['Price'],
        "Address"       => $data['customerAddress'],
        "City"          => $data['cityName'],
        "State"         => $data['stateName'],
        "Landmark"      => $data['locationLandmark'],
        "BookingStatus" => "PENDING",
        "CityLeadID"    => $CityLeadID,
        "SourceType"    => $data['SourceType'],
        "CreatedBy"     => $data['CreatedBy'],
        "CreatedDate"   => date("Y-m-d"),
        "CreatedTime"   => date("H:i:s")
    ];

    // 4️⃣ Insert Booking
    $insert = _InsertTableRecords_prepare($conn, "service_bookings", $insertData);
    if ($insert['error']) {
        return [
            "error" => true,
            "message" => "Booking creation failed"
        ];
    }

    $CustomerID = $data['CustomerID'] ?? 0;

    // 5️⃣ Queue Notifications (async-friendly)
    queueBookingNotifications($conn, $data, $BookingCode, $CityLeadID, $CustomerID, $cityRow);

    // 6️⃣ Email state head and central team
    sendServiceBookingEmailNotification($conn, $data, $BookingCode);

    // 7️⃣ WhatsApp confirmation to customer
    sendServiceBookingCustomerWhatsApp($data, $BookingCode);

    // 8️⃣ Return Fast Response
    return [
        "error" => false,
        "message" => "Booking request submitted successfully",
        "BookingCode" => $BookingCode
    ];
}

// ==============================
// Booking Actions
// ==============================

function rejectBooking($conn, $BookingCode, $LeadID, $Reason)
{
    /* =========================
       STEP 1: VERIFY OWNERSHIP
    ========================= */
    $booking = _getTableDetails(
        $conn,
        "service_bookings",
        "WHERE BookingCode = '$BookingCode'
         AND CityLeadID = '$LeadID'
         AND BookingStatus = 'PENDING'"
    );

    if (empty($booking)) {
        return [
            "error"   => true,
            "message" => "Invalid booking, unauthorized access, or already processed"
        ];
    }

    /* =========================
       STEP 2: UPDATE BOOKING
    ========================= */
    $update = _UpdateTableRecords_prepare(
        $conn,
        "service_bookings",
        [
            "BookingStatus" => "REJECTED",
            "UpdatedBy"     => $LeadID,
            "UpdatedDate"   => date("Y-m-d"),
            "UpdatedTime"   => date("H:i:s")
        ],
        [
            "BookingCode" => $BookingCode,
            "CityLeadID"  => $LeadID
        ]
    );

    if (!empty($update['error'])) {
        return $update;
    }

    /* =========================
       STEP 3: INSERT REVIEW LOG
    ========================= */
    return _InsertTableRecords_prepare(
        $conn,
        "booking_reviews",
        [
            "booking_id"       => $BookingCode,
            "action"           => "REJECTED",
            "rejection_reason" => $Reason,
            "reviewed_by"      => $LeadID,
            "reviewed_role"    => "CITY_LEAD",
            "reviewed_at"      => date("Y-m-d H:i:s")
        ]
    );
}



function assignTechnician($conn, $BookingCode, $LeadID, $TechID)
{
    /* =========================
       STEP 1: VERIFY BOOKING
    ========================= */
    $booking = _getTableDetails(
        $conn,
        "service_bookings",
        "WHERE BookingCode = '$BookingCode'
         AND CityLeadID = '$LeadID'
         AND BookingStatus = 'ACCEPTED'"
    );

    if (empty($booking)) {
        return [
            "error"   => true,
            "message" => "Booking not accepted or unauthorized"
        ];
    }

    /* =========================
       STEP 2: ASSIGN TECHNICIAN
    ========================= */
    $update = _UpdateTableRecords_prepare(
        $conn,
        "service_bookings",
        [
            "BookingStatus" => "ASSIGNED",
            "TechnicianID"  => $TechID,
            "UpdatedBy"     => $LeadID,
            "UpdatedDate"   => date("Y-m-d"),
            "UpdatedTime"   => date("H:i:s")
        ],
        [
            "BookingCode" => $BookingCode,
            "CityLeadID"  => $LeadID
        ]
    );

    if (!empty($update['error'])) {
        return $update;
    }

    /* =========================
       STEP 3: REVIEW LOG
    ========================= */
    return _InsertTableRecords_prepare(
        $conn,
        "booking_reviews",
        [
            "booking_id"    => $BookingCode,
            "action"        => "ASSIGNED",
            "reviewed_by"   => $LeadID,
            "reviewed_role" => "CITY_LEAD",
            "reviewed_at"   => date("Y-m-d H:i:s")
        ]
    );
}


function acceptBooking($conn, $BookingCode, $LeadID)
{
    /* =========================
       STEP 1: VERIFY BOOKING
    ========================= */
    $booking = _getTableDetails(
        $conn,
        "service_bookings",
        "WHERE BookingCode = '$BookingCode'
         AND CityLeadID = '$LeadID'
         AND BookingStatus = 'PENDING'"
    );

    // var_dump($LeadID);

    if (empty($booking)) {
        return [
            "error" => true,
            "message" => "Booking not found or already processed"
        ];
    }

    /* =========================
       STEP 2: UPDATE BOOKING
    ========================= */
    $update = _UpdateTableRecords_prepare(
        $conn,
        "service_bookings",
        [
            "BookingStatus" => "ACCEPTED",
            "UpdatedBy"     => $LeadID,
            "UpdatedDate"   => date("Y-m-d"),
            "UpdatedTime"   => date("H:i:s")
        ],
        [
            "BookingCode" => $BookingCode,
            "CityLeadID"  => $LeadID
        ]
    );

    if ($update['error']) {
        return $update;
    }

    if ($update['affected_rows'] === 0) {
        return [
            "error" => true,
            "message" => "No rows updated"
        ];
    }

    /* =========================
       STEP 3: INSERT REVIEW LOG
    ========================= */
    _InsertTableRecords_prepare(
        $conn,
        "booking_reviews",
        [
            "booking_id"    => $BookingCode,
            "action"        => "ACCEPTED",
            "reviewed_by"   => $LeadID,
            "reviewed_role" => "CITY_LEAD",
            "reviewed_at"   => date("Y-m-d H:i:s")
        ]
    );

    return [
        "error" => false,
        "message" => "Booking accepted"
    ];
}



// ==============================
// Service booking email notification
// ==============================

function resolveStateRowForBooking($conn, $stateName, $cityName = '')
{
    $stateName = trim((string) $stateName);
    $cityName = trim((string) $cityName);

    if ($stateName !== '') {
        $stateNameEsc = mysqli_real_escape_string($conn, $stateName);
        $stateRow = _getTableDetails(
            $conn,
            'state',
            " WHERE LOWER(StateName) = LOWER('$stateNameEsc') AND IsActive = 1"
        );
        if (!empty($stateRow['ID'])) {
            return $stateRow;
        }
    }

    if ($cityName !== '') {
        $cityEsc = mysqli_real_escape_string($conn, $cityName);
        $cityRow = _getTableDetails(
            $conn,
            'citydata',
            " WHERE LOWER(CityName) = LOWER('$cityEsc') AND status = 1"
        );
        if (!empty($cityRow['StateID'])) {
            $stateId = (int) $cityRow['StateID'];
            return _getTableDetails($conn, 'state', " WHERE ID = $stateId AND IsActive = 1");
        }
    }

    return [];
}

function getStateHeadEmailForBooking($conn, $stateName, $cityName = '')
{
    $result = [
        'email'      => '',
        'name'       => '',
        'state_name' => trim((string) $stateName),
    ];

    $stateRow = resolveStateRowForBooking($conn, $stateName, $cityName);
    if (empty($stateRow)) {
        return $result;
    }

    $result['state_name'] = $stateRow['StateName'] ?? $result['state_name'];

    if (empty($stateRow['StateHead']) || (int) $stateRow['StateHead'] <= 0) {
        return $result;
    }

    $stateHeadId = (int) $stateRow['StateHead'];
    $employeeRow = _getTableDetails($conn, 'employees', " WHERE ID = $stateHeadId AND IsActive = 1");

    if (!empty($employeeRow['Email']) && filter_var($employeeRow['Email'], FILTER_VALIDATE_EMAIL)) {
        $result['email'] = $employeeRow['Email'];
        $result['name']  = $employeeRow['Name'] ?? '';
    }

    return $result;
}

function sendServiceBookingCustomerWhatsApp($data, $BookingCode)
{
    $phonenumber = preg_replace('/\D/', '', $data['phoneNumber'] ?? '');
    $phonenumber = substr($phonenumber, -10);
    if (strlen($phonenumber) !== 10) {
        return false;
    }

    $address = trim(($data['customerAddress'] ?? '') . ' ' . ($data['locationLandmark'] ?? ''));
    if ($address === '') {
        $address = 'N/A';
    }

    // Template home_care_service_booking_cust: {{1}} name, {{2}} booking id, {{3}} service,
    // {{4}} sub service, {{5}} amount, {{6}} address, {{7}} city
    $bodyValues = [
        $data['bookingName'] ?? 'Customer',
        $BookingCode,
        $data['serviceName'] ?? 'N/A',
        $data['Subservice'] ?? 'N/A',
        (string) ($data['Price'] ?? '0'),
        $address,
        $data['cityName'] ?? 'N/A',
    ];

    _interakt_sendWhatsAppMessage_common([
        'phonenumber' => $phonenumber,
        'template'    => 'home_care_service_booking_cust',
        'body_values' => json_encode($bodyValues, JSON_UNESCAPED_UNICODE),
    ]);

    return true;
}

function sendServiceBookingEmailNotification($conn, $data, $BookingCode)
{
    $stateName = $data['stateName'] ?? '';
    $cityName = $data['cityName'] ?? '';
    $stateHead = getStateHeadEmailForBooking($conn, $stateName, $cityName);

    $postMailData = [
        'action'         => 'New Service Booking',
        'BookingCode'    => $BookingCode,
        'CustomerName'   => $data['bookingName'] ?? '',
        'Phone'          => $data['phoneNumber'] ?? '',
        'Email'          => $data['bookingEmail'] ?? '',
        'ServiceName'    => $data['serviceName'] ?? '',
        'SubService'     => $data['Subservice'] ?? '',
        'Price'          => $data['Price'] ?? '',
        'Address'        => $data['customerAddress'] ?? '',
        'City'           => $cityName,
        'State'          => $stateHead['state_name'] ?: $stateName,
        'Landmark'       => $data['locationLandmark'] ?? '',
        'SourceType'     => $data['SourceType'] ?? '',
        'BookingDate'    => date('Y-m-d'),
        'BookingTime'    => date('H:i:s'),
        'StateHeadEmail' => $stateHead['email'],
        'StateHeadName'  => $stateHead['name'],
    ];

    $corePath = dirname(__DIR__, 2) . '/mail/include/service-booking-mail-core.php';
    if (is_readable($corePath)) {
        require_once $corePath;
        sendServiceBookingMail($postMailData);
    }
}

// ==============================
// Notification Queue
// ==============================

function queueBookingNotifications($conn, $data, $BookingCode, $CityLeadID, $CustomerID, $cityRow = [])
{
    $now = date("Y-m-d H:i:s");

    // 👤 Customer
    _InsertTableRecords_prepare($conn,"push_notifications_log",[
        "user_id" => $CustomerID ?: $data['phoneNumber'],
        "title"   => "Booking Received",
        "body"    => "Your booking $BookingCode has been received.",
        "payload" => json_encode([
            "type" => "BOOKING_CREATED",
            "booking_code" => $BookingCode,
            "channel" => ["WHATSAPP","EMAIL"],
            "phone" => $data['phoneNumber'],
            "email" => $data['bookingEmail']
        ]),
        "status" => "PENDING",
        "created_at" => $now
    ]);

    // 🧑‍💼 City Lead
    if ($CityLeadID > 0) {
        _InsertTableRecords_prepare($conn,"push_notifications_log",[
            "user_id" => $CityLeadID,
            "title"   => "New Booking Request",
            "body"    => "New booking $BookingCode requires your approval.",
            "payload" => json_encode([
                "type" => "CITY_LEAD_REVIEW",
                "booking_code" => $BookingCode,
                "channel" => ["WHATSAPP","EMAIL"],
                "phone" => $cityRow['CityLeadPhone'] ?? '',
                "email" => $cityRow['CityLeadEmail'] ?? ''
            ]),
            "status" => "PENDING",
            "created_at" => $now
        ]);
    }
}

// ==============================
// Notification Senders
// ==============================

function sendPushFromPayload($conn, $payload)
{
    if (!isset($payload['user_id'])) return false;

    $devices = _getTableRecords(
        $conn,
        'user_devices',
        "WHERE user_id = '{$payload['user_id']}' AND IsActive = 1"
    );

    if (empty($devices)) return false;

    foreach ($devices as $device) {
        sendPush($device['device_token'], $payload['title'], $payload['body'], $payload);
    }
    return true;
}

function sendWhatsAppFromPayload($conn, $payload)
{
    if (isset($payload['phone'])) {
        _interakt_sendWhatsAppMessage_common([
            "phonenumber" => $payload['phone'],
            "template"    => $payload['template'] ?? "generic_template",
            "body_values" => json_encode($payload['body_values'] ?? [])
        ]);
    }
}

function sendEmailFromPayload($conn, $payload)
{
    if (!isset($payload['email'])) return false;

    sendMailRequest([
        "to"      => $payload['email'],
        "subject" => $payload['title'],
        "body"    => $payload['body']
    ]);
}

// ==============================
// Notification Worker
// ==============================

function processNotificationQueue($conn)
{
    $notifications = _getTableDetailsMultiple(
        $conn,
        "push_notifications_log",
        "WHERE status='PENDING' ORDER BY id ASC LIMIT 20"
    );

    foreach ($notifications as $n) {
        $payload = json_decode($n['payload'], true);
        try {
            if (in_array("WHATSAPP", $payload['channel'] ?? [])) {
                sendWhatsAppFromPayload($conn, $payload);
            }
            if (in_array("EMAIL", $payload['channel'] ?? [])) {
                sendEmailFromPayload($conn, $payload);
            }
            sendPushFromPayload($conn, $payload);

            _UpdateTableRecords_prepare($conn, "push_notifications_log", ["status" => "SENT"], "id = ?", [$n['id']]);
        } catch (Exception $e) {
            _UpdateTableRecords_prepare($conn, "push_notifications_log", ["status" => "FAILED"], "id = ?", [$n['id']]);
        }
    }
}

// ==============================
// logNotification helper
// ==============================

function logNotification($conn, $data)
{
    return _InsertTableRecords_prepare(
        $conn,
        'push_notifications_log',
        [
            "user_id"    => $data['user_id'],
            "title"      => $data['title'],
            "body"       => $data['body'],
            "payload"    => $data['payload'],
            "status"     => "PENDING",
            "created_at" => date("Y-m-d H:i:s")
        ]
    );
}
?>