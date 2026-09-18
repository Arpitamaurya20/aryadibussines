<?php
class Utility extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}
	public function insertScholarship($data)
	{
		print_r($data);
		if($data[1] == "Name")
		{
			return;
		}
		$Name = $data[1];
		$OrgName = $data[2];
		$EmailID = $data[4];
		$MobileNumber = $data[5];
		$Code = $data[6];
		$Admission = $data[7];
		$Score = $data[10];
		$Scholarship = $data[22];
		echo "<br><hr>";
		$sql = "INSERT INTO scholarship_result_2(Name,OrgName,Code,AdmissionNumber,Email,Mobile,Score,Scholarship,Remarks,Course)VALUES('$Name','$OrgName','$Code','$Admission','$EmailID','$MobileNumber','$Score','$Scholarship','','BA')";
		$result = $this->_InsertTableRecords($this->conn,$sql);
	}
	public function insertAdmissionData($data)
	{
		print_r($data);
		if($data[1] == "LearnerName")
		{
			return;
		}
		$LearnerName = $data[1];
		$LearnerEmail = $data[2];
		$Password = $data[3];
		$MobileNumber = $data[4];
		$CourseName = $data[5];
		$Validity = $data[6];
		$AmoundPaid = $data[7];
		$DripStartDate = $data[8];
		$Link = $data[10];
		echo "<br><hr>";
		$sql = "INSERT INTO admission_data(LearnerName,LearnerEmail,Password,MobileNumber,CourseName,Validility,AmountPaid,DripStartDate,Link)VALUES('$LearnerName','$LearnerEmail','$Password','$MobileNumber','$CourseName','$Validity','$AmoundPaid','$DripStartDate','$Link')";
		$result = $this->_InsertTableRecords($this->conn,$sql);
	}

	public function changeEnquiryTable($enquiry,$courses_array)
	{
		$module_course_name_temp_array = array();
		$module_course_name_string = "";
		if($enquiry['ModuleCourseName'] == "")
		{
			$module_array = explode(',',$enquiry['Module']);
			foreach($module_array as $module)
			{
				$module_name_temp = $courses_array[$module];
				array_push($module_course_name_temp_array,$module_name_temp);
			}
			$module_course_name_string = implode(',',$module_course_name_temp_array);
			$ID = $enquiry['ID'];
			$sql = " ModuleCourseName = '$module_course_name_string' where ID = $ID";
			$this->_UpdateTableRecords($this->conn,'allcourse_form_payment', $sql);
		}
	}

	public function correctEnrollmentDates($data)
	{
		$temp_serial_number = $data[1];
		$enrollment_date = $data[11];
		$sql_update = " CreatedDate = '$enrollment_date' where TempSrNo = $temp_serial_number";
		echo $sql_update."<br>";
		$response_update = $this->_UpdateTableRecords($this->conn,'student_enrollment',$sql_update);
		echo $response_update['message']."<br>";
	}


	public function insertStudentAdmissionData($data)
	{
		// print_r($data);
		if($data[1] == "Name")
		{
			return "Not Parsed";
		}
		$temp_serial_number = $data[1];
		$parsed_serial_number = true;

	 	$where = " where SrNo = $temp_serial_number";
	 	$parsed_serial_number = $this->check_unique_identity_filter($this->conn,'parsed_serial_number',$where);
	 	if(!$parsed_serial_number)
	 	{
		 	return "<br>$temp_serial_number - Already Parsed<br>";
	 	}

		$courses_array = array();
		$where = " where IsActive = 1";
		$courses_fee_array = $this->_getTableRecords($this->conn,'courses_fee',$where);
		foreach($courses_fee_array as $course_fee)
		{
			$CourseID = $course_fee['CourseID'];
			$Mode = $course_fee['Mode'];
			$courses_array[$CourseID][$Mode] = $course_fee['Fees'];
		}
		

        $student_name = $data[2];
        $student_father = $data[3];
		$student_gender = $data[4];
		$student_state = $data[5];
		$student_phone = $data[6];
		// Split the input based on the "/" delimiter
		$phone_numbers = explode('/', $student_phone);

		// Extract the phone number and alternative number
		if (count($phone_numbers) == 2) {
		    $student_phone = $phone_numbers[0];
		    $student_alternative_number = $phone_numbers[1];
		} else {
		    // If there is no "/", set the entire input as the student phone number
		    $student_phone = $student_phone;
		    $student_alternative_number = '';
		}
		$student_email = $data[7];
		
		$course = $data[8];
		if(!is_numeric($course))
		{
			return "Course - Problem for $student_email - Data Error";
		}

		$mode = $data[9];
		$PaymentMode = $data[10];
		$admission_date = $data[11];
		//$total_fee_as_per_structure = $courses_array[$course][$mode];
		$total_fee_as_per_structure = $total_fees_payable = $data[14];
		$Batch = "Old Batch";
		if($data[68] != "")
		{
			$Batch = "December Batch 2023";
		}
		if($data[66] != "")
		{
			$scholarship_provided = intval($data[66]); // to be updated later

			$total_fee_as_per_structure = (intval($total_fees_payable))/(1-$scholarship_provided/100);
		}
		else
		{
			$scholarship_provided = 0;
		}

		$student_fee = str_replace([',', '/-', '/'], '', $data[13]);

		if (strpos($student_fee,'|') !== false) 
		{
			$total_payment_amount = 0;
		    $pa_array = explode("|", $student_fee);
		    foreach($pa_array as $pa)
		    {
		    	if (strpos($pa,'(') !== false)
		    	{
		    		if (preg_match('/(\d+)\(([^)]+)\)/', $pa, $matches)) {
					    $pa_amount = (int) $matches[1];
					    $payment_mode = $matches[2];
					} 
		    	}
		    	else
		    	{
		    		$pa_amount = $pa;
		    	}
		    	
				$total_payment_amount = $total_payment_amount + $pa_amount;
		    }
		    $student_fee = $total_payment_amount;
		} 


		$CreatedDate = date('Y-m-d');
		$CreatedTime = date('H:i:s');
		$Source = "Admin Portal";

		$not_duplicate = true;

		 $where = " where Email = '$student_email' AND PhoneNumber = '$student_phone'";
		 $not_duplicate = $this->check_unique_identity_filter($this->conn,'student_admission',$where);
		 

		 if($not_duplicate)
		 {
		 	$AdmissionID = "";
		 	echo "<br>Dupicate - False<br>";
		 }
		 else
		 {
		 	$Student_details = $this->_getTableDetails($this->conn,'student_admission',$where);
		 	$AdmissionID = $Student_details['ID'];
		 	echo "<br>Duplicate - True | Admission ID : $AdmissionID";
		 }

		  if($not_duplicate)
		  {
		  	echo "Name = ".$student_name."<br>";
		  	echo "Gender = ".$student_gender."<br>";
		  	echo "Email = ".$student_email."<br>";
		  	echo "PhoneNumber = ".$student_phone."<br>";
		  	echo "AlternativeNumber = ".$student_alternative_number."<br>";
		  	echo "State = ".$student_state."<br>";
		  	echo "Mode = ".$mode."<br>";
		  	echo "Courses = ".$course."<br>";
		  	echo "TotalStructuredFee = ".$total_fee_as_per_structure."<br>";
		  	echo "ScholarshipProvided = ".$scholarship_provided."<br>";
		  	echo "TotalPaybleFee = ".$total_fees_payable."<br>";
		  	echo "FeestobePaid = ".$student_fee."<br>";
		  	echo "AdmissionDate = ".$admission_date."<br>";
		  	echo "CreatedDate = ".$CreatedDate."<br>";
		  	echo "CreatedTime = ".$CreatedTime."<br>";
		  	echo "Source = ".$Source."<br>";
		  	echo "PaymentMode = ".$PaymentMode."<br>";
		  	echo "PaymentStatus = ".$student_name."<br>";
		  	echo "FatherHusbandName = ".$student_father."<br>";

			$sql = "INSERT INTO student_admission(Name,Gender,Email,PhoneNumber,AlternativeNumber,State,Mode,Courses,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,FeestobePaid,AdmissionDate,CreatedDate,CreatedTime,Source,PaymentMode,PaymentStatus,FatherHusbandName) VALUES ('$student_name','$student_gender','$student_email','$student_phone','$student_alternative_number','$student_state','$mode','$course','$total_fee_as_per_structure',$scholarship_provided,'$total_fees_payable','$student_fee','$admission_date','$CreatedDate','$CreatedTime','$Source','$PaymentMode','Successfull','$student_father')";
			echo "<br>Insert Query - ".$sql;
			// echo $sql."<br>";
			$response_insert_admission_details = $this->_InsertTableRecords($this->conn,$sql);
			//$response_insert_admission_details['error'] = false;
			//$response_insert_admission_details['last_insert_id'] = -1;
			if($response_insert_admission_details['error'] == false)
			{
	          $last_insert_id = $response_insert_admission_details['last_insert_id'];
	          $AdmissionID = $last_insert_id;
	          
	          // enrollment query 
	          $sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Batch,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,TempSrNo,CreatedBy) VALUES ($AdmissionID,'$student_email','$course','$Batch','$mode','$total_fee_as_per_structure','$scholarship_provided','$total_fees_payable','$CreatedDate','$CreatedTime',$temp_serial_number,'system')";
	          echo "	<br>Enrollment SQL - ".$sql."<br>";
	          $response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn,$sql);

	          $insert_serial_number = "INSERT INTO parsed_serial_number(SrNo) VALUES($temp_serial_number)";
	      	  $response_insert_serial_number = $this->_InsertTableRecords($this->conn,$insert_serial_number);

	          echo "		Further Payments : <br>";
              $array_check_counter = [11,16,19,22,27,30,33,38,41,44,49,52,57,60];
              $total_payment_amount = 0;
			  foreach($array_check_counter as $counter)
			  {
					if($data[$counter] !== "")
					{
						$payment_Date = $data[$counter];
						$payment_mode = $data[$counter+1];
						if($payment_mode == "")
						{
							$payment_mode = "CASH";
						}
						echo "<br>Counter : ".$counter;
						$payment_Date = $this->datecheck($payment_Date);
						$payment_amount = str_replace([',', '/-', '/'], '', $data[$counter+2]);
						if (strpos($payment_amount,'|') !== false) 
						{
						    $pa_array = explode("|", $payment_amount);
						    foreach($pa_array as $pa)
						    {
						    	if (strpos($pa,'(') !== false)
						    	{
						    		if (preg_match('/(\d+)\(([^)]+)\)/', $pa, $matches)) {
									    $pa_amount = (int) $matches[1];
									    $payment_mode = $matches[2];
									} 
						    	}
						    	else
						    	{
						    		$pa_amount = $pa;
						    	}
						    	echo "<br>		$payment_Date | $payment_mode | $pa_amount : <br>";
								$total_payment_amount = $total_payment_amount + $pa_amount;

								// insert fee_transaction
								$sql = "INSERT INTO student_transaction(AdmissionID,Course,Mode,Amount,PaymentMode,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$mode','$pa_amount','$payment_mode','$payment_Date','$CreatedTime','system')";

								echo "Transaction".$sql;

								$response_insert_transaction_details = $this->_InsertTableRecords($this->conn,$sql);
						    }
						} 
						else 
						{
							
							echo "<br>		$payment_Date | $payment_mode | $payment_amount : <br>";
							$total_payment_amount = $total_payment_amount + $payment_amount;
							// insert fee_transaction
							$sql = "INSERT INTO student_transaction(AdmissionID,Course,Mode,Amount,PaymentMode,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$mode','$payment_amount','$payment_mode','$payment_Date','$CreatedTime','system')";

							echo "Transaction".$sql;
				
				  			$response_insert_transaction_details = $this->_InsertTableRecords($this->conn,$sql);
			  			}
					}	
			   }

			   /*echo "<br>Total Payment = ".$total_payment_amount;
			   echo "<br>Excel Payment = ".$total_payment_amount;*/
			   $excel_total_payment_amount = str_replace([',', '/-', '/'], '', $data[0]);
			   if(intval($total_payment_amount) == intval($excel_total_payment_amount))
			   {
			   		echo "<br> Payment Right <br>";
			   }
			   else
			   {
			   		echo "<br> Payment Wrong <br>";
			   }
			   echo "<hr>";



		       
	        }


	     }  
	     else
	     {
	     	echo "Duplicate Entry<br>";
		     if($AdmissionID != "")
		     {

		     
		     	  $sql = "INSERT INTO student_enrollment(AdmissionID,Email,Course,Batch,Mode,TotalStructuredFee,ScholarshipProvided,TotalPaybleFee,CreatedDate,CreatedTime,CreatedBy,TempSrNo) VALUES ($AdmissionID,'$student_email','$course','$Batch','$mode','$total_fee_as_per_structure','$scholarship_provided','$total_fees_payable','$CreatedDate','$CreatedTime','system',$temp_serial_number)";
		          echo "	<br>Enrollment SQL - ".$sql."<br>";
		          $response_insert_admission_enrollment = $this->_InsertTableRecords($this->conn,$sql);

		          $insert_serial_number = "INSERT INTO parsed_serial_number(SrNo) VALUES($temp_serial_number)";
	      	  	  $response_insert_serial_number = $this->_InsertTableRecords($this->conn,$insert_serial_number);

		          echo "		Further Payments : <br>";
	              $array_check_counter = [11,16,19,22,27,30,33,38,41,44,49,52,57,60];
	              $total_payment_amount = 0;
				  foreach($array_check_counter as $counter)
				  {
						if($data[$counter] !== "")
						{
							$payment_Date = $data[$counter];
							$payment_mode = $data[$counter+1];
							if($payment_mode == "")
							{
								$payment_mode = "CASH";
							}
							echo "<br>Counter : ".$counter;
							$payment_Date = $this->datecheck($payment_Date);
							$payment_amount = str_replace([',', '/-', '/'], '', $data[$counter+2]);
							if (strpos($payment_amount,'|') !== false) 
							{
							    $pa_array = explode("|", $payment_amount);
							    foreach($pa_array as $pa)
							    {
							    	if (strpos($pa,'(') !== false)
							    	{
							    		if (preg_match('/(\d+)\(([^)]+)\)/', $pa, $matches)) {
										    $pa_amount = (int) $matches[1];
										    $payment_mode = $matches[2];
										} 
							    	}
							    	else
							    	{
							    		$pa_amount = $pa;
							    	}
							    	echo "<br>		$payment_Date | $payment_mode | $pa_amount : <br>";
									$total_payment_amount = $total_payment_amount + $pa_amount;
									// insert fee_transaction
									$sql = "INSERT INTO student_transaction(AdmissionID,Course,Mode,Amount,PaymentMode,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$mode','$pa_amount','$payment_mode','$payment_Date','$CreatedTime','system')";

									echo "Transaction".$sql;

									$response_insert_transaction_details = $this->_InsertTableRecords($this->conn,$sql);
							    }
							} 
							else 
							{
								
								echo "<br>		$payment_Date | $payment_mode | $payment_amount : <br>";
								$total_payment_amount = $total_payment_amount + $payment_amount;
								// insert fee_transaction
								$sql = "INSERT INTO student_transaction(AdmissionID,Course,Mode,Amount,PaymentMode,CreatedDate,CreatedTime,CreatedBy) VALUES ('$AdmissionID','$course','$mode','$payment_amount','$payment_mode','$payment_Date','$CreatedTime','system')";

								echo "Transaction".$sql;
					
					  			$response_insert_transaction_details = $this->_InsertTableRecords($this->conn,$sql);
				  			}
						}	
				   }

				   /*echo "<br>Total Payment = ".$total_payment_amount;
				   echo "<br>Excel Payment = ".$total_payment_amount;*/
				   $excel_total_payment_amount = str_replace([',', '/-', '/'], '', $data[0]);
				   if(intval($total_payment_amount) == intval($excel_total_payment_amount))
				   {
				   		echo "<br> Payment Right <br>";
				   }
				   else
				   {
				   		echo "<br> Payment Wrong <br>";
				   }
				   echo "<hr>";



		     }  
		 }

		 // If remarks exist 
		 if($AdmissionID != -1)
		 {
		 	$Remarks = $this->cleantext($data[15]);
		 	// insert remarks
		 	$sql_remarks = "INSERT INTO student_remark(AdmissionID,Remarks,CreatedDate,CreatedTime,CreatedBy) VALUES ($AdmissionID,'$Remarks','$CreatedDate','$CreatedTime','system')";
		 	echo $sql_remarks."<br>";
		 	$response_insert_remarks_sql = $this->_InsertTableRecords($this->conn,$sql_remarks);
		 }
		 if($data[67] == 1 && $AdmissionID != -1)
		 {
		 	echo "<br>REFUNDED<br>";
		 	$sql_update_refund = "Refunded = 1 where ID = $AdmissionID";
		 	$update_refund = $this->_UpdateTableRecords($this->conn,'student_admission',$sql_update_refund);
		 }

	}


	public function insertStudentAdmissionDataNew($data)
	{
		print_r($data);
		if($data[1] == "LearnerName")
		{
			return;
		}
		$Student_name  = $data[2];
		$MobileNumber = $data[3];
		$AdmissionDate = $data[4];
		// $CourseName = $data[5];
		// $Validity = $data[6];
		$TotalAmoundPaid = $data[7];
		$CourseStructureFees = $data[8];
		$CourseID = $data[9];
		$CourseMode = $data[10];
		$ModeOfPayment = $data[12];
		$CreatedBy = $data[13];
		$Remark = $data[14];

		echo "<br><hr>";
		$sql = "INSERT INTO admission_data(LearnerName,LearnerEmail,Password,MobileNumber,CourseName,Validility,AmountPaid,DripStartDate,Link)VALUES('$LearnerName','$LearnerEmail','$Password','$MobileNumber','$CourseName','$Validity','$AmoundPaid','$DripStartDate','$Link')";
		echo $sql."<br>";
		// $result = $this->_InsertTableRecords($this->conn,$sql);
	}
}
?>