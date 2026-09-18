<?php 
class ExamQuestion extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertExamQuestionForm($data)
	{
		$examcategory = $data['examcategory'];
		$question = $data['question'];
		$sub_question = "";
		if(isset($data['sub_question'])){
			$sub_question = $data['sub_question'];
		}
		$opt1 = $data['opt1'];
		$opt2 = $data['opt2'];
		$opt3 = $data['opt3'];
		$opt4 = $data['opt4'];
		$answer = $data['answer'];

		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO questions(examcategory,question,subquestion,opt1,opt2,opt3,opt4,answer,CreatedDate,CreatedTime,CreatedBy) VALUES ('$examcategory','$question','$sub_question','$opt1','$opt2','$opt3','$opt4','$answer','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_exam_category_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_exam_category_details['message'] = "Exam Question is Successfully Added !";
		return $response_insert_exam_category_details;
		
	}


	function UpdateExamQuestionForm($data)
	{	
		$question = $data['question'];
        $examcategory = $data['examcategory'];
		$sub_question = "";
		if(isset($data['sub_question'])){
			$sub_question = $data['sub_question'];
		}
		$opt1 = $data['opt1'];
		$opt2 = $data['opt2'];
		$opt3 = $data['opt3'];
		$opt4 = $data['opt4'];
		$answer = $data['answer'];

		$question_id = $data['form_id'];

	    $old_question_details = $this->GetExamQuestionDetailsbyID($question_id);
	    if($examcategory == $old_question_details['examcategory'] && $question == $old_question_details['question']  && $sub_question == $old_question_details['subquestion'] && $opt1 == $old_question_details['opt1'] && $opt2 == $old_question_details['opt2'] && $opt3 == $old_question_details['opt3'] && $opt4 == $old_question_details['opt4'] && $answer == $old_question_details['answer'])
	    {
	    	$response['message'] = "No changes to update !";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " examcategory = '$examcategory', question = '$question', subquestion = '$sub_question', opt1 = '$opt1',opt2 = '$opt2', opt3 = '$opt3', opt4 = '$opt4', answer = '$answer' where id=$question_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'questions', $update_param);
			$response['message'] = "Exam Question Update !";
	    	
	    }

	    return $response;
	}

	public function GetAllExamQuestion($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "questions", $where);
        return $Admission_details;
	}

	public function getTotalExamQuestion($table)
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

	function DeleteExamQuestion($data)
	{
		$ExamQuestionID = $data['ID'];
		$where = " where id = $ExamQuestionID";
		$response = $this->delete_identity_filter($this->conn,"questions",$where);
		return $response;
	}

    function GetExamQuestionDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$book_details = $this->_getTableDetails($this->conn,'questions', $where);
		return $book_details;
	}

}