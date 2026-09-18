<?php 
class ScholarshipRegistration extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function GetAllScholarshipRegistration($conn)
	{
		$where = "SELECT r.name, r.contact, r.city, r.email, r.ScholarshipPercentage, e.total_question, e.correct_answer, e.wrong_answer, e.exam_time , e.examcategory FROM registration r JOIN exam_results e ON r.id = e.id";
        $Form_Details = $this->_getTableRecords($conn, "allcourse_form", $where);
        return $Form_Details;
	}
	public function getTotalScholarshipRegistration($table)
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

	public function GetAllScholarshipRegistrationByID($conn,$id)
	{
		$where = " where id = $id";
        $registration_Details = $this->_getTableDetails($conn, "registration", $where);
        return $registration_Details;
	}

	public function GetAllScholarshipResultByUserName($conn,$username)
	{
		$where = " where username = '$username'";
        $registration_Details = $this->_getTableRecords($conn, "exam_results", $where);
        return $registration_Details;
	}
}
?>