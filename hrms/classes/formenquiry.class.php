<?php 
class FormEnquiry extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function GetAllCoursesEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $Form_Details = $this->_getTableRecords($conn, "allcourse_form", $where);
        return $Form_Details;
	}

	public function GetAllScholarshipEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $Form_Details = $this->_getTableRecords($conn, "scholarship_form", $where);
        return $Form_Details;
	}

	public function GetAllGetInTouchEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $Form_Details = $this->_getTableRecords($conn, "contact_us", $where);
        return $Form_Details;
	}
	public function GetAllMSLVEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $Form_Details = $this->_getTableRecords($conn, "mslv_course", $where);
        return $Form_Details;
	}
	public function GetAllBookletEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $booklet_Details = $this->_getTableRecords($conn, "booklet_form", $where);
        return $booklet_Details;
	}
	public function GetAllMentorShipEnquiry($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $booklet_Details = $this->_getTableRecords($conn, "mentorship_enquiry", $where);
        return $booklet_Details;
	}
	public function GetAllWebsiteAdmissions($conn)
	{
		$where = " where PaymentStatus = 'Successfull' ORDER BY ID DESC";
        $Form_Details = $this->_getTableRecords($conn, "allcourse_form_payment", $where);
        return $Form_Details;
	}
	public function getTotalEnquiries($table)
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
	public function getTotalWebsiteAdmission($table)
	{
		$sql = "Select COUNT(*) as total_enquiries from $table where PaymentStatus = 'Successfull'";
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
}
?>