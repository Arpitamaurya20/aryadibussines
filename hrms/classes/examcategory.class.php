<?php 
class ExamCategory extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertExamCategoryForm($data)
	{
		$exam_category = $data['exam_category'];
		$exam_duration = $data['exam_duration'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO exam_category(category,exam_time_in_minutes,CreatedDate,CreatedTime,CreatedBy) VALUES ('$exam_category','$exam_duration','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_exam_category_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_exam_category_details['message'] = "Exam Category is Successfully Added !";
		return $response_insert_exam_category_details;
		
	}


	function UpdateExamCategoryForm($data)
	{	
		$exam_category = $data['exam_category'];
		$exam_duration = $data['exam_duration'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$exam_category_id = $data['form_id'];

	    $old_exam_category_details = $this->GetExamCategoryDetailsbyID($exam_category_id);
	    if($exam_category == $old_exam_category_details['category']  && $exam_duration == $old_exam_category_details['exam_time_in_minutes'])
	    {
	    	$response['message'] = "No changes to update !";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " category = '$exam_category', exam_time_in_minutes = '$exam_duration' where id=$exam_category_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'exam_category', $update_param);
			$response['message'] = "Exam Category Data Update !";
	    	
	    }

	    return $response;
	}

	public function GetAllExamCategory($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "exam_category", $where);
        return $Admission_details;
	}

	public function getTotalExamCategory($table)
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

	function DeleteExamCategory($data)
	{
		$ExamCategoryID = $data['ID'];
		$where = " where id = $ExamCategoryID";
		$response = $this->delete_identity_filter($this->conn,"exam_category",$where);
		return $response;
	}

    function GetExamCategoryDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$book_details = $this->_getTableDetails($this->conn,'exam_category', $where);
		return $book_details;
	}

}