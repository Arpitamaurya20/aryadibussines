<?php 
class CourseBatch extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCourseBatch($data)
	{
		$course_id = $data['course'];
		$batch_name = $data['batch_name'];
		$startdate=$data['batch_start_date'];
		$enddate=$data['batch_end_date'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO course_batch (CourseID,BatchName,BatchStartDate,BatchEndDate,CreatedDate,CreatedTime,CreatedBy) VALUES ('$course_id','$batch_name','$startdate','$enddate','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_course_cordinator_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_course_cordinator_details;
	}

	function UpdateCourseBatch($data)
	{
		$course_batch_id = $data['edit_course_batch_id'];
		$batch_name = $data['edit_batch_name'];
		$batch_start_date = $data['edit_batch_start_date'];
		$batch_end_date = $data['edit_batch_end_date'];

		$sql = "BatchName = '$batch_name', BatchStartDate = '$batch_start_date', BatchEndDate = '$batch_end_date' WHERE ID = $course_batch_id";
		$response_update_course_batch = $this->_UpdateTableRecords($this->conn, 'course_batch',$sql);
		return $response_update_course_batch;
	}

	public function GetAllCourseBatch($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $course_cordinator_details = $this->_getTableRecords($this->conn, "course_batch", $where);
        return $course_cordinator_details;
	}

	public function getTotalCourseBatch($table)
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

	function DeleteCourseBatch($data)
	{
		$BatchID = $data['ID'];
		// Delete course
		$where = " where ID = $BatchID";
		$response = $this->delete_identity_filter($this->conn,"course_batch",$where);
		return $response;
	}

	public function GetAllCourseBatchByCourseID($conn,$CourseID)
	{
		$where = " where Course = $CourseID ORDER BY ID DESC";
        $course_cordinators_details = $this->_getTableRecords($this->conn, "course_batch", $where);
        return $course_cordinators_details;
	}

	public function GetCourseBatchByCourseID($conn,$data)
	{

		$course_arr = $data['CourseID'];
		if(is_array($course_arr))
		{
			$course = implode(",",$course_arr);
			$query = "CourseID IN ($course)";
		}
		else
		{
			$course = $course_arr;
			$query = "CourseID = $course";
		}
		$where = " where $query ORDER BY ID DESC";
		// echo $where;
        $course_cordinators_details = $this->_getTableRecords($this->conn, "course_batch", $where);
        return $course_cordinators_details;
	}

	public function getBatchNameByID($ID)
	{
	    $sql="Select cd.CourseName AS CourseName, cb.BatchName AS CourseBatch from course_batch cb LEFT JOIN courses_for_display cd ON cd.ID=cb.CourseID Where cb.ID=$ID";
        $details = $this->_getSQLDetails($this->conn,  $sql);
        return $details;
	}


}

?>