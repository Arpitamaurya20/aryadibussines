<?php 
class CourseFees extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCourseFees($data)
	{
		$course = $data['course'];
		$course_mode = $data['course_mode'];
		$fees = $data['fees'];
		$original_price = $data['original_price'];
		$firstyear_fees = $data['firstyear_fees'];
		$secondyear_fees = '';
		if(isset($data['secondyear_fees'])){
			$secondyear_fees = $data['secondyear_fees'];
		}
		$thirdyear_fees = '';
		if(isset($data['thirdyear_fees'])){
			$thirdyear_fees = $data['thirdyear_fees'];
		}
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO courses_fee (CourseID,Mode,Mrp,Fees,FirstYear,SecondYear,ThirdYear,CreatedDate,CreatedTime,CreatedBy) VALUES ('$course','$course_mode','$original_price','$fees','$firstyear_fees','$secondyear_fees','$thirdyear_fees','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_course_fee_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_course_fee_details;
		

	}

	// public function GetAllCourseFee($conn)
	// {
	// 	$where = " where IsActive = 1 ORDER BY ID DESC";
    //     $Admission_details = $this->_getTableRecords($this->conn, "courses_fee", $where);
    //     return $Admission_details;
	// }

	public function getTotalCoursesFee($table)
	{
		$sql = "Select COUNT(*) as total_enquiries from $table where IsActive = 1";
		$result=mysqli_query($this->conn,$sql);
		if($result->num_rows>0)	
		{
			$row = $result->fetch_assoc();
			return $row['total_enquiries'];
		}
		else
		{
			return 0;
		}
	}

	function DeleteCourseFees($data)
	{
		$CourseFeeID = $data['ID'];
		// Delete course
		$where = " where ID = $CourseFeeID";
		$response = $this->delete_identity_filter($this->conn,"courses_fee",$where);
		return $response;
	}


}