<?php 
class CourseBook extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCourseBook($data)
	{
		$course_name = $data['course'];
		$course_book = $data['course_book'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO course_book (Course,CourseBook,CreatedDate,CreatedTime,CreatedBy) VALUES ('$course_name','$course_book','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_course_book_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_course_book_details;
		

	}

	public function GetAllCourseBook($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $course_book_details = $this->_getTableRecords($this->conn, "course_book", $where);
        return $course_book_details;
	}

	public function getTotalCourseBook($table)
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

	function DeleteCourseBook($data)
	{
		$CourseBookID = $data['ID'];
		// Delete course
		$where = " where ID = $CourseBookID";
		$response = $this->delete_identity_filter($this->conn,"course_book",$where);
		return $response;
	}

	public function GetAllCourseBookByCourseID($conn,$CourseID)
	{
		$where = " where Course = $CourseID ORDER BY ID DESC";
        $course_books_details = $this->_getTableRecords($this->conn, "course_book", $where);
        return $course_books_details;
	}


}

?>