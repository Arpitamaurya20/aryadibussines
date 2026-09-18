<?php 
class Cordinator extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCourseCordinator($data)
	{
		$course_id = $data['course'];
		$name = $data['name'];
		$mobilenumber = $data['mobilenumber'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO course_cordinator (CourseID,Name,PhoneNumber,CreatedDate,CreatedTime,CreatedBy) VALUES ('$course_id','$name','$mobilenumber','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_course_cordinator_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_course_cordinator_details;
		

	}

	public function GetAllCourseCordinator($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $course_cordinator_details = $this->_getTableRecords($this->conn, "course_cordinator", $where);
        return $course_cordinator_details;
	}

	public function getTotalCourseCordinator($table)
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

	function DeleteCourseCordinator($data)
	{
		$CordinatorID = $data['ID'];
		// Delete course
		$where = " where ID = $CordinatorID";
		$response = $this->delete_identity_filter($this->conn,"course_cordinator",$where);
		return $response;
	}

	public function GetAllCourseCordinatorByCourseID($conn,$CourseID)
	{
		$where = " where Course = $CourseID ORDER BY ID DESC";
        $course_cordinators_details = $this->_getTableRecords($this->conn, "course_cordinator", $where);
        return $course_cordinators_details;
	}


}

?>