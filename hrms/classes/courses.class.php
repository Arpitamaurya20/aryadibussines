<?php 
class Courses extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCourses($data)
	{
		$course_center_id = $data['course_center_id'];
		$course_name = $data['course_name'];
		$course_type = $data['course_type'];
		$course_cat=$data['course_cat'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		// $CreatedTime = date('H:i:s');
		$n_mentornship='1';

		$sql = "INSERT INTO courses_for_display (CenterID,CourseName,CourseType,CourseCat,NumberOfMentorship,CreatedDate,CreatedBy) VALUES ('$course_center_id','$course_name','$course_type','$course_cat','$n_mentornship','$CreatedDate','$CreatedBy')";
		$response_insert_course_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_course_details['message'] = "Course is Successfully Added !";
		
		return $response_insert_course_details;
		

	}

	function UpdateCourse($data)
	{	
		$course_center_id = $data['course_center_id'];
		$course_name = $data['course_name'];
		$course_type = $data['course_type'];
		$course_cat=$data['course_cat'];
		if(isset($data['n_mentornship'])){
			$mentorship_number=$data['n_mentornship'];
		}else{
			$mentorship_number='1';
		}
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$Course_id = $data['form_id'];
		$priority = $data['priority'];
		
	    $old_course_details = $this->GetCourseDetailsbyID($Course_id);
	    if($course_name == $old_course_details['CourseName']  && $course_type == $old_course_details['CourseType']  && $course_center_id == $old_course_details['CenterID'] && $mentorship_number == $old_course_details['NumberOfMentorship'] && $priority == $old_course_details['Priority'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " CourseName = '$course_name', CourseType = '$course_type', CenterID = '$course_center_id',CourseCat='$course_cat',NumberOfMentorship=$mentorship_number,Priority=$priority where ID=$Course_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'courses_for_display', $update_param);
			$response['message'] = "Course Update!";
	    	
	    }

	    return $response;
	}

	public function getAllCourseCounseller()
	{
		$where = " where IsActive = 1  ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($this->conn, "course_counsellor", $where);
        return $Admission_details;
	}

	public function getAllCourseCounsellerByCenterID($CenterID)
	{
		if($CenterID == "-1"){
			$where = "c.IsActive = 1";
		}else{
			$where = "u.CenterID = '$CenterID'";
		}
		$sql = "SELECT u.CenterID, c.CounsellorID, c.CourseID, u.Email FROM course_counsellor c JOIN user_details ud ON c.CounsellorID = ud.ID JOIN users u ON ud.Email = u.Email WHERE ($where) ORDER BY u.CenterID;";

		$response = array();
		$result = mysqli_query($this->conn, $sql);
		if ($result) {
			if ($result->num_rows > 0) {
				while ($row = $result->fetch_assoc()) {
					array_push($response, $row);
				}
			}
		} else {
			//echo $sql;
		}
		return $response;
	}

	public function getTotalCourses($table)
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

	function DeleteCourse($data)
	{
		$CourseID = $data['ID'];
		// Delete course
		$where = " where ID = $CourseID";
		$response = $this->delete_identity_filter($this->conn,"courses_for_display",$where);
		return $response;
	}

	function GetCourseDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$book_details = $this->_getTableDetails($this->conn,'courses_for_display', $where);
		return $book_details;
	}

	public function GetAllCourses($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($this->conn, "courses_for_display", $where);
        return $Admission_details;
	}
	public function getAllCourseByCenterID($CenterID)
	{
		if($CenterID == -1){
			$filter = " where IsActive = 1 OR IsActive = 0 ORDER BY ID DESC";
		}else{
			$filter = " where IsActive = 1  OR IsActive = 0 and CenterID = $CenterID ORDER BY ID DESC";
		}
		$center_users = $this->_getTableRecords($this->conn,'courses_for_display',$filter);
		return $center_users;
	}

	public function SoftDeleteCourseDetails($data)
	{
		$course_id = $data['ID'];
		$isActive = $data['IsActive'];
		if($isActive==0)
		{
			$isActive=1;
		}else
		{
			$isActive=0;
		}
		$update_sql = "IsActive= '$isActive' where ID = $course_id";
		$response = $this->_UpdateTableRecords($this->conn, 'courses_for_display', $update_sql);
		return $response;
	}

	public function DeleteAssociativeCourse($data)
	{
		$ID = $data['ID'];
		$where = " where ID = $ID";
		$response = $this->delete_identity_filter($this->conn,"assosiative_course_product",$where);
		return $response;
	}


}