<?php
class Admission extends Core
{
	private $conn;
	private $file;
	public function __construct($conn)
	{
		$this->conn = $conn;
		if (file_exists("../logs/admission_logs.txt")) {
			$this->file = "../logs/admission_logs.txt";
		} elseif (file_exists("admin/logs/admission_logs.txt")) {
			$this->file = "admin/logs/admission_logs.txt";
		} else {
			$this->file = "../../logs/admission_logs.txt";
		}
		$this->setTimeZone();
	}
	public function WriteLog($txt)
	{
		$logFile = fopen($this->file, "a");
		fwrite($logFile, "\n" . $txt);
		fclose($logFile);
	}
	public function GetAllAdmissions($conn)
	{
		$where = " where PaymentStatus = 'Successfull' and  IsActive = 1 ORDER BY ID DESC";
		$Admission_details = $this->_getTableRecords($conn, "student_admission", $where);
		return $Admission_details;
	}
	public function GetAllAdmissionsByCenter($conn, $CenterID)
	{
		if ($CenterID == -1) {
			$where = " where PaymentStatus = 'Successfull' and  IsActive = 1 ORDER BY ID DESC";
		} else {
			$where = " where PaymentStatus = 'Successfull' and BranchID = $CenterID and  IsActive = 1 ORDER BY ID DESC";
		}
		$Admission_details = $this->_getTableRecords($conn, "student_admission", $where);
		return $Admission_details;
	}
	public function getTotalAdmissions($table)
	{
		$sql = "Select COUNT(*) as total_enquiries from $table where IsActive = 1";
		$result = mysqli_query($this->conn, $sql);
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['total_enquiries'];
		} else {
			return 0;
		}
	}

	public function getAdmissionDetails($data)
	{
		$response = array();
		// $Email = $data['Email'];
		$PhoneNumber = $data['PhoneNumber'];
		$where = " where PhoneNumber = '$PhoneNumber' and IsActive = 1";
		$response['data'] = $this->_getTableDetails($this->conn, 'student_admission', $where);
		if (isset($response['data']['Name'])) {
			$response['exist'] = true;
		} else {
			$response['exist'] = false;
		}
		return $response;
	}

	public function getStudentAdmissionDetails($data)
	{
		$PhoneNumber = $data['PhoneNumber'];
		$where = " where PhoneNumber = '$PhoneNumber' and IsActive = 1";
		$response= $this->_getTableDetails($this->conn, 'student_admission', $where);
		return $response;
	}

	
  public function getStudentAdmissionDetailsByUserID($userId)
	{
		$where = " where UserID = $userId and IsActive = 1";
		$response= $this->_getTableDetails($this->conn, 'student_admission', $where);
		return $response;
	}
	// public function CreateAdmission($data,$Source,$PaymentMode)
	// {
	// 	$StudentName = $data['StudentName'];
	//     $StudentEmail = $data['StudentEmail'];
	//     $StudentPhone = $data['StudentPhoneNumber'];
	//     $StudentFee = $data['StudentFee'];
	//     $Course = $data['StudentFee'];
	//     $TotalStructuredFee = $data['TotalStructuredFee'];
	//     $ScholarshipProvided = $data['ScholarshipProvided'];
	//     $FeestobePaid = $data['FeestobePaid'];
	//     $CreatedBy = $data['CreatedBy'];
	//     $Postal_address = $data['PostalAddress'];
	//     $TranscID = $data['TransactionID'];
	//     $CreatedDate = date('Y-m-d');
	//     $CreatedTime = date('H:i:s');
	//     $Source = $Source;
	// 	$sql = "INSERT INTO `student_admission`(Name,Email,PhoneNumber,Address,Courses,TotalStructuredFee,ScholarshipProvided,FeestobePaid,Source,PaymentMode,TransactionID,PaymentStatus,CreatedBy,CreatedDate,CreatedTime) values('$StudentName','$StudentEmail','$StudentPhone','$Postal_address','$Course','$TotalStructuredFee','$ScholarshipProvided','$FeestobePaid','$Source','$PaymentMode','$TranscID','Successfull','$CreatedBy','$CreatedDate','$CreatedTime')";
	//     $result = $this->_InsertTableRecords($this->conn,$sql);
	//     return $result;
	// }

	function InsertAdmissionForm($data)
	{
		$student_name = $data['StudentName'];

		$student_gender = 'N/A';
		if (isset($data['Gender'])) {
			$student_gender = $data['Gender'];
		}

		$student_email = 'N/A';
		if (isset($data['StudentEmail'])) {
			$student_email = $data['StudentEmail'];
		}

		$student_phone = $data['StudentPhoneNumber'];

		$student_alternative_number = "N/A";
		if (isset($data['AlternativeNumber'])) {
			$student_alternative_number = $data['AlternativeNumber'];
		}

		$student_state = 'N/A';
		if (isset($data['State'])) {
			$student_state = $data['State'];
		}

		$student_city = 'N/A';
		if (isset($data['City'])) {
			$student_city = $data['City'];
		}

		$student_address = 'N/A';
		if (isset($data['Address'])) {
			$student_address = $data['Address'];
		}

		$student_postal_address = "";
		if (isset($data['PostalAddress'])) {
			$student_postal_address = $data['PostalAddress'];
		}

		$student_pin_code = "";
		if (isset($data['PinCode'])) {
			$student_pin_code = $data['PinCode'];
		}

		$student_Dob = "";
		if (isset($data['DOB'])) {
			$student_Dob = $data['DOB'];
		}

		$student_aadhaar = '';
		if (isset($data['aadhaarnumber'])) {
			$student_aadhaar = $data['aadhaarnumber'];
		}

		$mode = $data['Mode'];
		$course_arr = $data['Courses'];
		if (is_array($course_arr)) {
			$course = implode(",", $course_arr);
		} else {
			$course = $course_arr;
		}

		$total_fee_as_per_structure = $data['TotalStructuredFee'];

		$scholarship_provided = 0;
		if (isset($data['ScholarshipProvided'])) {
			if ($data['ScholarshipProvided'] != 0) {
				$scholarship_provided = $data['ScholarshipProvided'];
			}
		}

		if (isset($data['ScholarshipProvidedAmount'])) {
			if ($data['ScholarshipProvidedAmount'] != 0 && $data['ScholarshipProvided'] == 0) {
				$scholarship_amount = $data['ScholarshipProvidedAmount'];
				$scholarship_provided = ((float)$scholarship_amount / (float)$total_fee_as_per_structure) * 100;
			}
		}


		$total_fees_payable = $data['FeestobePaid'];
		$student_fee = $data['StudentFee'];
		$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = $data['CreatedTime'] = date('H:i:s');
		$Source = "Payment Link";
		if (isset($data['Source'])) {
			$Source = $data['Source'];
		}

		$PaymentMode = "Online";
		if (isset($data['PaymentMode'])) {
			$PaymentMode = $data['PaymentMode'];
		}

		$TransactionID = "";
		if (isset($data['TransactionID'])) {
			$TransactionID = $data['TransactionID'];
		}

		$PaymentStatus = 'Successfull';

		$admission_date = date('Y-m-d');
		if (isset($data['AdmissionDate'])) {
			$admission_date = $data['AdmissionDate'];
		}

		$payment_date = '';
		if (isset($data['PaymentDate'])) {
			$payment_date = $data['PaymentDate'];
		}

		$FatherHusbandName = "";
		if (isset($data['FatherHusbandName'])) {
			$FatherHusbandName = $data['FatherHusbandName'];
		}

		$BranchID = -1;
		if (isset($data['BranchID'])) {
			$BranchID = $data['BranchID'];
		}

		$AdmissionStatus = "Approved";

		$Batch = "";
		if (isset($data['Batch'])) {
			$Batch = $data['Batch'];
		}


		$sql = "INSERT INTO student_admission(BranchID,Name,Gender,Email,PhoneNumber,AlternativeNumber,State,City,Address,PostalAddress,Pin,AadhaarNumber,DOB,Mode,Courses,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,FeestobePaid,AdmissionDate,PaymentDate,CreatedDate,CreatedTime,CreatedBy,Source,PaymentMode,TransactionID,PaymentStatus,FatherHusbandName,AdmissionStatus,temp_batch) VALUES ('$BranchID','$student_name','$student_gender','$student_email','$student_phone','$student_alternative_number','$student_state','$student_city','$student_address','$student_postal_address','$student_pin_code','$student_aadhaar','$student_Dob','$mode','$course','$total_fee_as_per_structure','$scholarship_provided','$total_fees_payable','$student_fee','$admission_date','$payment_date','$CreatedDate','$CreatedTime','$CreatedBy','$Source','$PaymentMode','$TransactionID','$PaymentStatus','$FatherHusbandName','$AdmissionStatus','$Batch')";
		$response_insert_admission_details = $this->_InsertTableRecords($this->conn, $sql);
		$this->WriteLog(" -------------- New Admission  ------------------");
		$this->WriteLog($sql);

		if ($response_insert_admission_details['error'] == false) {
			$last_insert_id = $response_insert_admission_details['last_insert_id'];
			$data['AdmissionID'] = $last_insert_id;
			$data['TotalPaybleFee'] = $total_fees_payable;
			$this->InsertStudentEnrollment($data);
			$this->AddEnrollmentTransaction($data);
			$AdmissionID = $last_insert_id;
			if (isset($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['name'] != '') {
				$extn_pan = explode('.', $_FILES["profile_picture"]["name"]);
				$profile_picture = $student_name . "profile_picture." . $extn_pan[1];
				$path = "../../project-assets/admin-media/user-profile/" . $profile_picture;
				move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $path);
				$query = "ProfilePicture = '$profile_picture' WHERE ID = $AdmissionID";
				$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
			}


			if (isset($_FILES['id_proof']['name']) && $_FILES['id_proof']['name'] != '') {
				$extn_pan = explode('.', $_FILES["id_proof"]["name"]);
				$id_proof = $student_name . "id_proof." . $extn_pan[1];
				$path = "../../project-assets/admin-media/user-profile/" . $id_proof;
				move_uploaded_file($_FILES["id_proof"]["tmp_name"], $path);
				$query = "IDProof = '$id_proof' WHERE ID =$AdmissionID";
				$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
			}
			if (isset($data['Lead_ID'])) {
				if ($data['Lead_ID'] != "") {
					$LeadID = $data['Lead_ID'];
					$where = " where LeadID = $LeadID ORDER BY CONCAT(CreatedDate, ' ', CreatedTime) DESC LIMIT 1";
					$lead_assignement_details = $this->_getTableDetails($this->conn, "lead_assignment_history", $where);
					$AssignedTo = $lead_assignement_details['AssignedTo'];
					$LeadStatus = $lead_assignement_details['Status'];
					if ($lead_assignement_details) {

						$sql = "INSERT INTO lead_assignment_history(LeadID,AssignedTo,Status,Remark,CreatedDate,CreatedTime,CreatedBy) VALUES ('$LeadID','$AssignedTo','$LeadStatus','Convert To Admission','$CreatedDate','$CreatedTime','$CreatedBy')";
						$response_insert_remark_details = $this->_InsertTableRecords($this->conn, $sql);
					}
				}
			}

			// $where = " where CourseID = $course";
			// $courses_cordinator_details = $this->_getTableDetails($this->conn,'course_cordinator',$where);
			// $mail_data['CordinatorName'] = "";
			// $mail_data['CordinatorPhoneNumber'] = "";

			//  $mail_data['CourseID'] = $course
			//   $mail_data['email']= $data['StudentEmail'];
			//   $mail_data['name']= $data['StudentName'];
			//   $mail_data['IDCardLink']= "https://tathastuics.com/admin/admission/student-id-card?StudentID=".base64_encode($last_insert_id);
			//   $URL = "https://garyglobalsolutions.com/api-mail/tathastu/admission-welcome-api.php";
			//   $this->sendMailRequest($mail_data,$URL);

		} else {
			$this->WriteLog($response_insert_admission_details['message']);
		}

		return $response_insert_admission_details;
	}

	function InsertStudentEnrollment($data)
	{
		$courses_array = array();
		$where = " where IsActive = 1";
		$courses_fee_array = $this->_getTableRecords($this->conn, 'courses_fee', $where);
		foreach ($courses_fee_array as $course_fee) {
			$CourseID = $course_fee['CourseID'];
			$Mode = $course_fee['Mode'];
			$courses_array[$CourseID][$Mode] = $course_fee['Fees'];
		}
		if (isset($data['AdmissionID'])) {
			$AdmissionID = $data['AdmissionID'];
			$student_email = $data['StudentEmail'];
			$scholarship_provided = $data['ScholarshipProvided'];
			$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
			$CreatedTime = $data['CreatedTime'] = date('H:i:s');
			$CreatedBy = $data['CreatedBy'];
			$TotalPaybleFee = $data['TotalPaybleFee'];


			$CoursesBatch = "Old Batch";
			if (isset($data['Batch'])) {
				$CoursesBatch = $data['Batch'];
			}

			if (is_array($data['Courses'])) {
				foreach ($data['Courses'] as $course) {
					$Mode = $data['Mode'];

					$StructuredCourseFee = $courses_array[$course][$Mode];
					$sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Batch,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$student_email','$course','$CoursesBatch','$Mode','$StructuredCourseFee','$scholarship_provided','$TotalPaybleFee','$CreatedDate','$CreatedTime','$CreatedBy')";
					$this->WriteLog($sql);
					$response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn, $sql);
					if ($response_insert_admission_enrollment['error'] == true) {
						$this->WriteLog($response_insert_admission_enrollment['message']);
					}


				}

			} else {
				$Mode = $data['Mode'];
				$course = $data['Courses'];
				$StructuredCourseFee = $courses_array[$course][$Mode];
				$sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Batch,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$student_email','$course','$CoursesBatch','$Mode','$StructuredCourseFee','$scholarship_provided','$TotalPaybleFee','$CreatedDate','$CreatedTime','$CreatedBy')";
				$this->WriteLog($sql);
				$response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn, $sql);
				if ($response_insert_admission_enrollment['error'] == true) {
					$this->WriteLog($response_insert_admission_enrollment['message']);
				}
			}

			return $response_insert_admission_enrollment;
		}

	}

	function AddStudentEnrollment($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$student_email = $data['StudentEmail'];
		$scholarship_provided = $data['ScholarshipProvided'];
		$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
		$CreatedTime = $data['CreatedTime'] = date('H:i:s');
		$CreatedBy = $data['CreatedBy'];
		$TotalPaybleFee = $data['FeestobePaid'];
		$Course = $data['Course'];
		$Mode = $data['Mode'];
		$StructuredCourseFee = $data['TotalStructuredFee'];
		$CoursesBatch = "Old Batch";
		if (isset($data['Batch'])) {
			$CoursesBatch = $data['Batch'];
		}

		$sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Batch,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$student_email','$Course','$CoursesBatch','$Mode','$StructuredCourseFee','$scholarship_provided','$TotalPaybleFee','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn, $sql);

		$response_insert_admission_enrollment['error'] = false;
		$response_insert_admission_enrollment['message'] = "Student Enrollment is Successfully Added!";

		return $response_insert_admission_enrollment;

	}

	function UpdateStudentEnrollment($data)
	{
		$scholarship_provided = $data['ScholarshipProvided'];
		$TotalPaybleFee = $data['FeestobePaid'];
		$Course = $data['Course'];
		$Mode = $data['Mode'];
		$StructuredCourseFee = $data['TotalStructuredFee'];
		$CoursesBatch = "Old Batch";
		if (isset($data['Batch'])) {
			$CoursesBatch = $data['Batch'];
		}
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$enrollment_id = $data['form_id'];



		$old_enrollment_details = $this->GetStudentEnrollmentbyID($enrollment_id);
		if ($Course == $old_enrollment_details['Course'] && $Mode == $old_enrollment_details['Mode'] && $StructuredCourseFee == $old_enrollment_details['TotalStructuredFee'] && $scholarship_provided == $old_enrollment_details['ScholarshipProvided'] && $TotalPaybleFee == $old_enrollment_details['TotalPaybleFee'] && $CoursesBatch == $old_enrollment_details['Batch']) {
			$response['message'] = "No changes to update";
			$response['error'] = true;
		} else {
			$update_param = " Course = '$Course', Mode = '$Mode', TotalStructuredFee = '$StructuredCourseFee', ScholarshipProvided = '$scholarship_provided', TotalPaybleFee = '$TotalPaybleFee', Batch = '$CoursesBatch' where ID=$enrollment_id";
			$response = $this->_UpdateTableRecords($this->conn, 'student_enrollment', $update_param);
			$response['error'] = false;
			$response['message'] = "Enrollment Updated";

		}

		return $response;
	}

	function AddEnrollmentTransaction($data)
	{
		$AdmissionID = $data['AdmissionID'];
		$Amount = $data['StudentFee'];
		$PaymentMode = "Online";
		if (isset($data['PaymentMode'])) {
			$PaymentMode = $data['PaymentMode'];
		}
		$TransactionID = "";
		if (isset($data['TransactionID'])) {
			$TransactionID = $data['TransactionID'];
		}
		$CourseMode = "";
		if (isset($data['Mode'])) {
			$CourseMode = $data['Mode'];
		}
		
		$course_arr = $data['Courses'];

		if (is_array($course_arr)) {
			$course = implode(",", $course_arr);
		} else {
			$course = $course_arr;
		}

		$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
		$CreatedTime = $data['CreatedTime'] = date('H:i:s');
		$CreatedBy = $data['CreatedBy'];

		// $InvoiceCheck = "Yes";
		// if(isset($data['InvoiceCheck'])){

		// }

		// echo $data['InvoiceCheck'];
		// die();
		if (isset($data['TransactionDate'])) {
			$TransactionDate = $data['TransactionDate'];
		} else {
			$TransactionDate = $CreatedDate;
		}

		$InvoiceNo = -1;
		if (isset($data['InvoiceCheck'])) {
			if ($data['InvoiceCheck'] == 'Yes') {

				$where_query = "";
				if ($CourseMode == "Online") {
					$where_query = " where Mode = 'Online'";
				} else {
					$where_query = " where Mode = 'Offline'";
				}

				$invoice_details = $this->_getMaxIdentityValue_filter($this->conn, 'invoice_no', "InvoiceNo", $where_query);
				$InvoiceNo = $invoice_details + 1;

				$update_param = " InvoiceNo = '$InvoiceNo' $where_query";
				$response = $this->_UpdateTableRecords($this->conn, 'invoice_no', $update_param);
			}
		}


		if (isset($data['AdmissionID'])) {
			$Status = "Approved";
			if (isset($data['payment_approval_status'])) {
				$Status = $data['payment_approval_status'];
			}

			$sql = "INSERT INTO student_transaction(InvoiceNo,AdmissionID,Course,Mode,Amount,PaymentMode,Status,TransactionID,TransactionDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($InvoiceNo,'$AdmissionID','$course','$CourseMode','$Amount','$PaymentMode','$Status','$TransactionID','$TransactionDate','$CreatedDate','$CreatedTime','$CreatedBy')";
			$this->WriteLog($sql);
			$response_insert_transaction_details = $this->_InsertTableRecords($this->conn, $sql);
			if ($response_insert_transaction_details['error'] == true) {
				$this->WriteLog($response_insert_transaction_details['message']);
			}

		}

		return $response_insert_transaction_details;
	}

	function ApproveEnrollmentTranascation($data)
	{

		$Amount = $data['StudentFee'];
		$PaymentMode = "Online";
		if (isset($data['PaymentMode'])) {
			$PaymentMode = $data['PaymentMode'];
		}
		$TransactionID = "";
		if (isset($data['TransactionID'])) {
			$TransactionID = $data['TransactionID'];
		}
		$TransactionDate = date("Y-m-d");
		if (isset($data['TransactionDate'])) {
			$TransactionDate = $data['TransactionDate'];
		}
		$InvoiceNo = -1;
		if (isset($data['InvoiceCheck'])) {
			if ($data['InvoiceCheck'] == 'Yes') {

				$where_query = "";
				if ($CourseMode == "Online") {
					$where_query = " where Mode = 'Online'";
				} else {
					$where_query = " where Mode = 'Offline'";
				}

				$invoice_details = $this->_getMaxIdentityValue_filter($this->conn, 'invoice_no', "InvoiceNo", $where_query);
				$InvoiceNo = $invoice_details + 1;

				$update_param = " InvoiceNo = '$InvoiceNo' $where_query";
				$response = $this->_UpdateTableRecords($this->conn, 'invoice_no', $update_param);
			}
		}
		if (isset($data['form_id'])) {
			$Status = "Approved";
			$ID = $data['form_id'];
			$update_sql = " InvoiceNo = '$InvoiceNo',Amount = '$Amount',TransactionDate='$TransactionDate',PaymentMode='$PaymentMode',Status='$Status',TransactionID='$TransactionID' where ID = $ID";

			$this->WriteLog($update_sql);
			$response_update_transaction_details = $this->_UpdateTableRecords($this->conn, 'student_transaction', $update_sql);
			if ($response_update_transaction_details['error'] == true) {
				$this->WriteLog($response_insert_transaction_details['message']);
			}

		}

		return $response_update_transaction_details;
	}


	function AddStudentBookDispatch($data)
	{
		$AdmissionID = $data['AdmissionID'];
		$course = $data['course'];
		$course_book_arr = $data['course_book'];
		$course_book = implode(",", $course_book_arr);

		$student_postal_address = $data['student_postal_address'];
		$student_tracking_id = $data['student_tracking_id'];

		$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
		$CreatedTime = $data['CreatedTime'] = date('H:i:s');
		$CreatedBy = $data['CreatedBy'];

		if (isset($data['AdmissionID'])) {

			$sql = "INSERT INTO student_book(AdmissionID,Course,CourseBooks,PostalAddress,TrackingID,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$course_book','$student_postal_address','$student_tracking_id','$CreatedDate','$CreatedTime','$CreatedBy')";
			$response_insert_transaction_details = $this->_InsertTableRecords($this->conn, $sql);

			if ($response_insert_transaction_details['error'] == false) {

				$student_remark = $data['student_book_remark'];

				$sql = "INSERT INTO student_remark(AdmissionID,Remarks,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$student_remark','$CreatedDate','$CreatedTime','$CreatedBy')";
				$response_insert_transaction_details = $this->_InsertTableRecords($this->conn, $sql);
			}

		}

		return $response_insert_transaction_details;
	}


	function SendFeeReciptInEmail($ID)
	{
		$where = " where ID = '$ID'";
		$Student_details = $this->_getTableDetails($this->conn, "student_transaction", $where);
		$Student_AdmissionID = $Student_details['AdmissionID'];

		$where = " where ID = $Student_AdmissionID";
		$Student_details2 = $this->_getTableDetails($this->conn, "student_admission", $where);
		$Student_encode = base64_encode($Student_AdmissionID);

		// $Fee_Receipt_link = "https://praajasv.digitalworkdesk.com/student-fee-receipt?AdmissionID=$Student_encode&FeeID=$ID";



		$mail_data['email'] = $Student_details2['Email'];
		$mail_data['name'] = $Student_details2['Name'];
		$mail_data['Encode'] = $Student_encode;
		$mail_data['StudentID'] = $ID;
		$URL = "https://praajasv.digitalworkdesk.com/mail-api/student-fee-reciept-mail-api.php";
		$response = $this->sendMailRequest($mail_data, $URL);
		return $response;
	}

	function UpdateStudentDetails($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$Name = $data['student_name'];
		$Email = $data['student_email_id'];
		$MobileNumber = $data['student_mobile_number'];
		$Country = $data['country'];
		$State = $data['state'];
		$City = $data['student_city'];
		$Address = $data['student_address'];
		$Postal_address = $data['postal_address'];
		$pin = $data['pin'];
		$student_dob = $data['student_dob'];
		$student_gender = $data['student_gender'];
		$college_school = $data['college_school'];
		$year_class = $data['year_class'];
		$father_husband_name = $data['father_husband_name'];
		$parent_mobile = $data['parent_mobile'];
		$Roll_no = $data['roll_no'];
		$Degree = $data['degree'];


		if (isset($_FILES['profile_picture']['name']) && $_FILES['profile_picture']['name'] != '') {
			$extn_pan = explode('.', $_FILES["profile_picture"]["name"]);
			$profile_picture = $Name . "profile_picture." . $extn_pan[1];
			$path = "../../project-assets/admin-media/user-profile/" . $profile_picture;
			move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $path);
			$query = "ProfilePicture = '$profile_picture' WHERE ID =$AdmissionID";
			$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
		}


		if (isset($_FILES['id_proof']['name']) && $_FILES['id_proof']['name'] != '') {
			$extn_pan = explode('.', $_FILES["id_proof"]["name"]);
			$id_proof = $Name . "id_proof." . $extn_pan[1];
			$path = "../../project-assets/admin-media/user-profile/" . $id_proof;
			move_uploaded_file($_FILES["id_proof"]["tmp_name"], $path);
			$query = "IDProof = '$id_proof' WHERE ID =$AdmissionID";
			$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
		}


		$query = " Name = '$Name',Email = '$Email',PhoneNumber = '$MobileNumber',Country = '$Country',State = '$State',City = '$City',Address = '$Address',PostalAddress = '$Postal_address',DOB = '$student_dob',Gender = '$student_gender',CollegeSchool = '$college_school',YearClass = '$year_class',FatherHusbandName = '$father_husband_name',ParentMobile = '$parent_mobile',Pin = '$pin',RollNo = '$Roll_no',Degree = '$Degree' where ID = $AdmissionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
		return $response;
	}

	public function GetAdmissionStudentDetailsByID($conn, $ID)
	{
		$where = " where ID = $ID";
		$Student_details = $this->_getTableDetails($this->conn, "student_admission", $where);
		return $Student_details;
	}

	public function GetStudentEnrollmentDetailsByID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_enrollment", $where);
		return $Student_enrollment_details;
	}

	public function GetStudentFeeTransitionsDetailsByID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_transaction", $where);
		return $Student_enrollment_details;
	}

	public function GetStudentStudentBookDetailsByID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_book", $where);
		return $Student_enrollment_details;
	}

	public function GetStudentFeeRecieptDetailsByID($conn, $ID)
	{
		$where = " where ID = $ID";
		$Student_fee_details = $this->_getTableDetails($this->conn, "student_transaction", $where);
		return $Student_fee_details;
	}

	public function AddStudentRemarkDispatch($data)
	{
		$AdmissionID = $data['AdmissionID'];
		$student_remark = $data['student_remark'];

		$CreatedDate = $data['CreatedDate'] = date('Y-m-d');
		$CreatedTime = $data['CreatedTime'] = date('H:i:s');
		$CreatedBy = $data['CreatedBy'];

		if (isset($data['AdmissionID'])) {

			$sql = "INSERT INTO student_remark(AdmissionID,Remarks,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$student_remark','$CreatedDate','$CreatedTime','$CreatedBy')";
			$response_insert_transaction_details = $this->_InsertTableRecords($this->conn, $sql);

		}

		return $response_insert_transaction_details;
	}

	public function GetStudentRemarksByID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_remark", $where);
		return $Student_enrollment_details;
	}


	public function CreateStudentFeeReminders($data)
	{
		$AdmissionID = $data['AdmissionID'];
		$Amountarr = $data['Amount'];

		$Reminder_date_temp = $data['reminderdate'];

		$CreatedBy = $data['CreatedBy'];
		$CreatedDate = date('Y-m-d');
		$CreatedTime = date('H:i:s');

		foreach ($Reminder_date_temp as $key => $val) {

			$ReminderDate = $Reminder_date_temp[$key];
			$Amount = $Amountarr[$key];

			// Convert using strtotime
			$timestamp = strtotime($ReminderDate);

			// Format the date
			$ReminderDate = date('Y-m-d', $timestamp);

			$sql = "INSERT INTO student_fee_reminder(AdmissionID,Amount,ReminderDate,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$Amount','$ReminderDate','$CreatedDate','$CreatedTime','$CreatedBy')";
			$insert_fee_reminder = $this->_InsertTableRecords($this->conn, $sql);
		}
		return $insert_fee_reminder;
	}
	public function EditStudentFeeReminders($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$Amountarr = $data['Amount'];
		$reminderid = $data['reminderid'];

		$Reminder_date_temp = $data['reminderdate'];

		$CreatedBy = $data['CreatedBy'];
		$CreatedDate = date('Y-m-d');
		$CreatedTime = date('H:i:s');

		foreach ($Reminder_date_temp as $key => $val) {

			$ReminderDate = $Reminder_date_temp[$key];
			$Amount = $Amountarr[$key];
			$ReminderID = $reminderid[$key];


			// Convert using strtotime
			$timestamp = strtotime($ReminderDate);

			// Format the date
			$ReminderDate = date('Y-m-d', $timestamp);

			$update_filter = " Amount = '$Amount',ReminderDate='$ReminderDate' where ID = $ReminderID";
			$update_fee_reminder = $this->_UpdateTableRecords($this->conn, 'student_fee_reminder', $update_filter);
		}
		return $update_fee_reminder;
	}
	public function GetStudentFeeReminderByID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_fee_reminder", $where);
		return $Student_enrollment_details;
	}
	public function ChangeFeeReminderStatus($data)
	{
		$FeeReminderID = $data['FeeReminderID'];
		$Status = $data['Status'];
		$update_filter = " Status = '$Status' where ID = $FeeReminderID";
		$response = $this->_UpdateTableRecords($this->conn, "student_fee_reminder", $update_filter);
		return $response;
	}

	public function GetStudentEnrollmentDetailsByAdmissionID($conn, $AdmissionID)
	{
		$where = " where AdmissionID = $AdmissionID";
		$Student_enrollment_details = $this->_getTableRecords($this->conn, "student_enrollment", $where);
		return $Student_enrollment_details;
	}

	function ChangeEnrollmentStatus($data)
	{
		$EnrollmentID = $data['ID'];
		$StatusID = $data['StatusID'];
		if ($StatusID == 1) {
			$StatusID = 0;
		} else {
			$StatusID = 1;
		}
		// Delete course
		$query = "IsActive = $StatusID WHERE ID =$EnrollmentID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_enrollment', $query);
		return $response;
	}

	public function ImportStudentAdmissionData($data)
	{
		if ($data[0] == "StudentName") {
			return;
		}

		$student_name = $data[0];
		if ($data[0] != '') {
			$student_name = $data[0];
		}
		$student_father = '';
		if ($data[1] != '') {
			$student_father = $data[1];
		}
		$student_gender = '';
		if ($data[2] != '') {
			$student_gender = $data[2];
		}
		$student_state = '';
		if ($data[3] != '') {
			$student_state = $data[3];
		}
		$student_phone = '';
		if ($data[4] != '') {
			$student_phone = $data[4];
		}
		$student_email = '';
		if ($data[5] != '') {
			$student_email = $data[5];
		}
		$course = $data[6];
		$mode = $data[7];
		$PaymentMode = $data[8];
		if ($data[8] != '') {
			$PaymentMode = $data[8];
		}
		$admission_date = $data[9];
		if ($data[9] != '') {
			$admission_date = $data[9];
		}
		$total_fee_as_per_structure = $data[10];
		$total_fees_payable = $data[11];
		$scholarship_provided = 0;
		if ($data[12] != '') {
			$scholarship_provided = $data[12];
		}
		$student_fee = $data[13];
		$CreatedDate = date('Y-m-d');
		$CreatedTime = date('H:i:s');
		$Source = "Admin Portal";

		$not_duplicate = true;

		if ($student_email != "") {
			$where = " where Email = '$student_email' OR PhoneNumber = '$student_phone'";
			$not_duplicate = $this->check_unique_identity_filter($this->conn, 'student_admission', $where);
		}

		if ($not_duplicate) {

			$sql = "INSERT INTO student_admission(Name,Gender,Email,PhoneNumber,State,Mode,Courses,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,FeestobePaid,AdmissionDate,CreatedDate,CreatedTime,Source,PaymentMode,PaymentStatus,FatherHusbandName,CreatedBy) VALUES ('$student_name','$student_gender','$student_email','$student_phone','$student_state','$mode','$course','$total_fee_as_per_structure',$scholarship_provided,'$total_fees_payable','$student_fee','$admission_date','$CreatedDate','$CreatedTime','$Source','$PaymentMode','Successfull','$student_father','Admin Importer')";
			$response_insert_admission_details = $this->_InsertTableRecords($this->conn, $sql);

			if ($response_insert_admission_details['error'] == false) {
				$last_insert_id = $response_insert_admission_details['last_insert_id'];
				$AdmissionID = $last_insert_id;

				// enrollment query 
				$sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$student_email','$course','$mode','$total_fee_as_per_structure','$scholarship_provided','$total_fees_payable','$CreatedDate','$CreatedTime','system')";
				$response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn, $sql);


				$sql = "INSERT INTO student_transaction(AdmissionID,Course,Mode,Amount,PaymentMode,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$mode','$student_fee','$PaymentMode','$CreatedDate','$CreatedTime','system')";

				$response_insert_transaction_details = $this->_InsertTableRecords($this->conn, $sql);
				return $response_insert_transaction_details;
			}

		}

		return $not_duplicate;

	}

	function UpdateStudentRefundStatus($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$RefundStatus = $data['refundstatus'];

		$query = " Refunded = $RefundStatus where ID = $AdmissionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_enrollment', $query);
		return $response;
	}

	function UpdateStudentDroppedStatus($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$DroppedStatus = $data['droppedstatus'];

		$query = " Dropped = $DroppedStatus where ID = $AdmissionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_enrollment', $query);
		return $response;
	}

	function GetStudentTransactionDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$student_transaction = $this->_getTableDetails($this->conn, 'student_transaction', $where);
		return $student_transaction;
	}

	function UpdateStudentTransactionDetails($data)
	{

		$StudentTransactionID = $data['StudentTransactionID'];
		$TransactionID = $data['TransactionID'];
		$PaymentMode = $data['PaymentMode'];
		$StudentFee = $data['StudentFee'];

		$query = " TransactionID = '$TransactionID', PaymentMode = '$PaymentMode', Amount = '$StudentFee' where ID = $StudentTransactionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_transaction', $query);
		return $response;
	}
	function GetAllPaymentTransactions()
	{
		/*$payment_modes = array();
			  $sql = "Select DISTINCT(PaymentMode) as PaymentMode from student_transaction where IsActive = 1";
			  $result = mysqli_query($this->conn, $sql);
			  if ($result) {
				  if ($result->num_rows > 0) {
					  while ($row = $result->fetch_assoc()) {
						  array_push($payment_modes, $row['PaymentMode']);
					  }
				  }
			  }*/
		$payment_modes = array("Cash", "UPI", "Offline", "Bank", "EDC", "CHQ");
		return $payment_modes;
	}

	function GetStudentEnrollmentbyID($ID)
	{
		$where = " where ID = $ID";
		$student_transaction = $this->_getTableDetails($this->conn, 'student_enrollment', $where);
		return $student_transaction;
	}

	function UpdateApprovedByAccountStatus($data)
	{

		$AdmissionID = $data['AdmissionID'];
		$StudentFee = $data['StudentFee'];
		$AdmissionStatus = "Approved By Account";

		$query = " AdmissionStatus = '$AdmissionStatus', FeestobePaid=$StudentFee  where ID = $AdmissionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
		return $response;
	}

	function UpdateLeadOwner($data)
	{

		$AdmissionID = $data['Lead_AdmissionID'];
		$LeadOwner = $data['LeadOwner'];

		$query = " LeadOwner = $LeadOwner where ID = $AdmissionID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_admission', $query);
		return $response;
	}
	function calculateScholarship($data)
	{
		$totalStructuredFee = $data['TotalStructuredFee'];
		$feesToBePaid = $data['FeestobePaid'];

		$scholarshipPercentage = (($totalStructuredFee - $feesToBePaid) / $totalStructuredFee) * 100;

		// Round to 2 decimal places
		$scholarshipPercentage = round($scholarshipPercentage, 2);

		// Format to remove unnecessary decimals
		if (floor($scholarshipPercentage) == $scholarshipPercentage) {
			$scholarshipPercentage = intval($scholarshipPercentage);
		}

		return $scholarshipPercentage;
	}
}
?>