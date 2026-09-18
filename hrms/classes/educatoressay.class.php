<?php 
class EducatorEssay extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertEducatorQuestionForm($data)
	{
		$educator_name = $data['educator_name'];
		$educator_question = $data['educator_question'];
		$question_date = $data['question_date'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO educator_essay(Educator,Question,QuestionDate,CreatedDate,CreatedTime,CreatedBy) VALUES ('$educator_name','$educator_question','$question_date','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_educator_essay_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_educator_essay_details['message'] = "Educator Essay is Successfully Added !";
		
		return $response_insert_educator_essay_details;
		

	}


	function UpdateEducatorQuestionForm($data)
	{	
		$educator_name = $data['educator_name'];
		$educator_question = $data['educator_question'];
		$question_date = $data['question_date'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$question_id = $data['form_id'];
		

	    $old_educator_question_details = $this->GetEducatorQuestionDetailsbyID($Book_id);
	    if($educator_name == $old_educator_question_details['Educator']  && $educator_question == $old_educator_question_details['Question']  && $question_date == $old_educator_question_details['QuestionDate'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " Educator = '$educator_name', Question = '$educator_question', QuestionDate = '$question_date' where ID=$question_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'educator_essay', $update_param);
			$response['message'] = "Educator Question has been Update";
	    	
	    }

	    return $response;
	}

	public function GetAllEducatorQuestions($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "educator_essay", $where);
        return $Admission_details;
	}

	public function getTotalEducatorQuestion($table)
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

	function DeleteEducatorQuestion($data)
	{
		$EducatorQuestionID = $data['ID'];
		// Delete course
		$where = " where ID = $EducatorQuestionID";
		$response = $this->delete_identity_filter($this->conn,"educator_essay",$where);
		return $response;
	}

    function GetEducatorQuestionDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$question_details = $this->_getTableDetails($this->conn,'educator_essay', $where);
		return $question_details;
	}

	function GetAllCommentbyQuestionID($ID)
	{
		$where = " where QuestionID = $ID";
		$question_comment_details = $this->_getTableRecords($this->conn,'essay_comment',$where);
		return $question_comment_details;
	}


	function InsertQuestionComment($data)
	{
		$username = $data['username'];
		$comment = $data['comment'];
		$question_id = $data['question_id'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO essay_comment(QuestionID,Comment,Username,CreatedDate,CreatedTime,CreatedBy) VALUES ('$question_id','$comment','$username','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_educator_essay_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_educator_essay_details;
		

	}

}