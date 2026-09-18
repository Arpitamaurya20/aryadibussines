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
	
	$sql = " Status = '$BookingStatus',AssignedTo = $AssignedTo,UpdatedBy='$UpdatedBy',UpdatedDate='$UpdatedDate',UpdatedTime='$UpdatedTime' where ID = $BookingID";
	$response = _UpdateTableRecords($conn,'confirm_booking', $sql);

	if($response['error'] == false)
	{
		$response['message'] = "Booking Updated!";
		// get booking data 
        $get_booking_data = getOneBookingData($conn,$BookingID);
		$Cityname = $get_booking_data['City_name'];
		$UserPhone = $get_booking_data['Phone'];
		$UserName = $get_booking_data['Name'];
		$Service = $get_booking_data['Service_name'];
		$SubService = $get_booking_data['SubService'];

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
		}
		if($BookingStatus == "Assigned")
		{
			$BookingStartOTP = $data['BookingStartOTP'];
			$sql = "BookingStartOTP = '$BookingStartOTP' where ID = $BookingID";
			$response = _UpdateTableRecords($conn,'confirm_booking', $sql);
		}
		if($BookingStatus == "Completed")
		{
			$data['phonenumber'] = $UserPhone;
			$TicketNumber = "HC_000".$BookingID;
		    $body_values = '["'.$UserName.'","'.$TicketNumber.'"]';
		    $data['body_values'] = $body_values;
		    $data['template'] = "HC_BKG_MSG_TO_CUS_COMPLETE";
			_interakt_sendWhatsAppMessage_common($data);
			// twm
			 //$usermessage = "Hello $UserName, \n\nYour Ticket Number is HC_000$BookingID. Your Ticket has been Closed.\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service. Please Give your feedback.\n\nFeedback link - https://techxpertindia.in/customer-rating.php?ticket=HC_000$BookingID\n\nRegards,\nTechXpert Team";
			 //  $User_Phone = "+91".$UserPhone;
			 //  sendWhatsAppMessage($User_Phone,$usermessage);

			   // send message to Employee
			$data['phonenumber'] = $EmployeesPhone;
		    $body_values = '["'.$EmployeeName.'","'.$TicketNumber.'"]';
		    $data['body_values'] = $body_values;
		    $data['template'] = "HC_BKG_MSG_TO_EMP_COMPLETE";
			_interakt_sendWhatsAppMessage_common($data);

			/*$AssignedTo = $EmployeeName;
			$message = "Dear $AssignedTo, \n\nThe Ticket Number is HC_000$BookingID has been Closed.\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
			   $Employees_Phone = "+91".$EmployeesPhone;
		   sendWhatsAppMessage($Employees_Phone,$message);*/
		}
		else{
			if($EmployeesPhone != "")
			{
				$TicketNumber = "HC_000".$BookingID;
				
				$data['phonenumber'] = $EmployeesPhone;
			    $body_values = '["'.$EmployeeName.'","'.$TicketNumber.'","'.$EmployeeName.'","'.$Service.'","'.$SubService.'","'.$BookingStatus.'","'.$UserPhone.'","'.$Cityname.'","'.$UserName.'"]';
			    $data['body_values'] = $body_values;
			    $data['template'] = "HC_BKG_MSG_TO_EMP_STATUS_CHANGE";
				_interakt_sendWhatsAppMessage_common($data);

				/*// send message to Employee
				$AssignedTo = $EmployeeName;
				$message = "Dear $AssignedTo, \n\nYour Ticket Number is HC_000$BookingID.\n\nTicket Details SOS\n\n📍 Assigned To- $AssignedTo\n\n🧰 Service - $Service ( $SubService )\n\n🧰 Status - $BookingStatus\n\n☎ Mobile No. - $UserPhone\n\n📍 Location - $Cityname\n\n✅ Name - $UserName\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
			   $Employees_Phone = "+91".$EmployeesPhone;
			   sendWhatsAppMessage($Employees_Phone,$message);*/

				// Get City Corporate Lead
				// $get_booking_data = getOneBookingData($conn,$BookingID);
				// $Cityname = $get_booking_data['City_name'];
		   		$where = " where CityName = '$Cityname'";
		   		$result_city_lead = _getTableDetails($conn,'citydata',$where);
		   		$CityLead = $result_city_lead['CityLead'];
		   		$AssignedTo = -1;
				if($CityLead != -1 && $CityLead != "")
				{
					$AssignedTo = $CityLead;
					$where_emp = " where ID = $AssignedTo";
			   		$result_emp = _getTableDetails($conn,'employees',$where_emp);
			   		$Employee_PhoneNumber = $result_emp['ContactNumber'];
			   		$CityLeadmessage = "Hello, \n\nThis is to inform you that we have a ticket. The ticket number is HC_000$BookingID.\n\nTicket Details SOS\n\n📍 Assigned To - $EmployeeName\n\n🧰 Status - $BookingStatus\n\n🧰 Service - $Service ( $SubService ) \n\nThank you for your support and action for our customer.\n\nRegards,\nTechXpert Team";
				    $phonenumber = "+91".$Employee_PhoneNumber;
					//sendWhatsAppMessage($phonenumber,$CityLeadmessage);
		    	}

				


				// send message to user 
				$data['phonenumber'] = $UserPhone;
			    $body_values = '["'.$UserName.'","'.$TicketNumber.'","'.$EmployeeName.'","'.$EmployeesPhone.'","'.$BookingStatus.'"]';
			    $data['body_values'] = $body_values;
			    $data['template'] = "HC_BKG_MSG_TO_CUS_STATUS_CHANGE";
				_interakt_sendWhatsAppMessage_common($data);
				
			/*	$usermessage = "Hello $UserName, \n\nYour Ticket Number is HC_000$BookingID. Your Ticket has been assigned to one of our Technician, and they will be working on resolving your issue as soon as possible. You can expect to hear from them shortly.\n\nTicket Details SOS\n\n📍 Assigned To - $EmployeeName\n\n🧰 Service - $Service ( $SubService ) \n\n🧰 Status - $BookingStatus\n\n☎ Mobile No. - $EmployeesPhone\n\nPlease feel free to let us know if you have any additional information or if there's anything else we can assist you with.\n\nThank you for choosing our service.\n\nRegards,\nTechXpert Team";
				   $User_Phone = "+91".$UserPhone;
				   sendWhatsAppMessage($User_Phone,$usermessage);*/

			 }
		}

		
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

    $book_query = "INSERT INTO confirm_booking (BookingID,Division,DivisionSequence,Name, Phone, Email, Service_name,SubService, Price,Customer_address, City_name,State_name, Location_landmark, PostalCode, Subject, File_path,BookingDate,BookingTime,CreatedBy,UpdatedBy,UpdatedDate,UpdatedTime,AssignedTo) VALUES('$BookingID','$Division',$seq,'$booking_name','$phone_number','$booking_email','$service_name','$Subservice','$Price','$customer_address','$city_name','$state_name', '$location_landmark','$postal_code', '$subject','$attach_file', '$BookingDate','$BookingTime','$CreatedBy','system','$BookingDate','$BookingTime','$AssignedTo')";

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
		    $data['template'] = "glf_cus_bkg_create_v1";
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
	sendMailRequest($post_mail_data);
    $last_insert_id = $book_result['last_insert_id'];
    $booking_response = array();
    if ($book_result)
    {
        $booking_response['error'] = false;
        $booking_response['message'] = "Booking is Confirmed. Thank you for contacting Us";
		$booking_response['BookingID'] = $last_insert_id;
    } else {
        echo mysqli_error($conn);
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

?>