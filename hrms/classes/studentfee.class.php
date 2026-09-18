<?php 
class StudentFee extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function InsertStudentFee($data)
	{
		$CreatedDate = date("Y-m-d");
		$CreatedTime = date("H:i:s");
		$Coursesarr = $data['course'];
		$Courses = implode(",",$Coursesarr);
		$StudentName = $data['student_name'];
		$StudentEmail = $data['student_email'];
		$StudentPhoneNumber = $data['student_phone_number'];
		$StudentFee = $data['student_fee'];
		$CreatedBy = $data['CreatedBy'];
		$total_fee_as_per_structure = $data['total_fee_as_per_structure'];
		$scholarship_provided = $data['scholarship_provided'];
		$total_fees_payable = $data['total_fees_payable'];
		$Mode = $data['mode'];
		$AdmissionID = -1;
		if(isset($data['AdmissionID'])){
			$AdmissionID = $data['AdmissionID'];
		}
		$invoice_check = $data['InvoiceCheck'];
		$CoursesBatch = "Old Batch";
		if(isset($data['Batch']))
		{
			$CoursesBatch = $data['Batch'];
			if($CoursesBatch == "")
			{
				$CoursesBatch = "Old Batch";
			}
		}
		/*$columns = ["StudentName","StudentEmail","StudentPhoneNumber","Courses","StudentFee","CreatedBy","CreatedDate","CreatedTime"];
		$values = [$data['student_name'],$data['student_email'],$data['student_phone_number'],$Courses,$data['student_fee'],$data['CreatedBy'],$CreatedDate,$CreatedTime];
		$response = $this->_InsertPreparedData($this->conn,'student_fee', $columns, $values);*/
		$sql = "INSERT INTO student_fee(AdmissionID,StudentName,StudentEmail,StudentPhoneNumber,Courses,Batch,Mode,TotalStructuredFee,ScholarshipProvided,FeestobePaid,StudentFee,InvoiceCheck,CreatedBy,CreatedDate,CreatedTime) VALUES ($AdmissionID,'$StudentName','$StudentEmail','$StudentPhoneNumber','$Courses','$CoursesBatch','$Mode','$total_fee_as_per_structure','$scholarship_provided','$total_fees_payable','$StudentFee','$invoice_check','$CreatedBy','$CreatedDate','$CreatedTime')";
		$response = $this->_InsertTableRecords($this->conn,$sql);
		if($response['error'] == false)
		{
        	$response['message'] = "Payment Link Created in the System";
        	$last_insert_id = $response['last_insert_id'];
        	$mail_data['Name'] = $data['student_name'];
			$mail_data['Email'] = $data['student_email'];
			$PaymentID = base64_encode($last_insert_id);
			$mail_data['Link'] = "https://tathastuics.com/admin/student-fee/pay-fee?StudentID=$PaymentID";

			$url = "https://garyglobalsolutions.com/api-mail/tathastu/admin/payment-link-api.php";
		
      		$this->sendMailRequest($mail_data,$url);
        }

        
		return $response;
	}

	public function GetAllStudentFee($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Studets_details = $this->_getTableRecords($conn, "student_fee", $where);
        return $Studets_details;
	}
	public function GetStudentFeeDetails($conn,$ID)
	{
		$where = " where ID = $ID";
        $Studet_details = $this->_getTableDetails($conn, "student_fee", $where);
        return $Studet_details;
	}

	public function GetAllCourses($conn)
	{
		$where = " where 1 ";
        $course_details = $this->_getTableRecords($conn, "courses_for_display", $where);
        return $course_details;
	}
	public function setCourseFeeArray()
	{
		$course_fee_array = array();
		$where = " where IsActive = 1";
        $course_details = $this->_getTableRecords($this->conn, "courses_fee", $where);
        foreach($course_details as $course)
        {
        	$CourseID = $course['CourseID'];
        	$Mode = $course['Mode'];
        	$course_fee_array[$CourseID][$Mode] = $course['Fees'];
        }
        return $course_fee_array;
	}
	public function getFeesDetails($data)
	{
		$response = array();
		$Course_id = implode(", ", $data['selected_courses']);
		$course_mode = $data['course_mode'];
		$where = " Where CourseID = $Course_id AND Mode = '$course_mode'";
		$response3 = $this->_getTableDetails($this->conn,'courses_fee',$where);
		if($response3)
		{
			$response['exist'] = true;
		}
		else
		{
			$response['exist'] = false;
		}
		return $response;
	}
}
?>