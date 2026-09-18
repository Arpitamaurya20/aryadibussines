<?php 
class Monthly extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertMonthlyForm($data)
	{
		$monthly_date = $data['monthly_date'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$monthly_pdf = "";

		 if (isset($_FILES['monthly_pdf']['name'])  && $_FILES['monthly_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["monthly_pdf"]["name"]);
	        $monthly_pdf   = $monthly_date."monthly_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/monthly-affairs/".$monthly_pdf;
	        move_uploaded_file($_FILES["monthly_pdf"]["tmp_name"], $path);
	    }

		 $sql = "INSERT INTO monthly_affairs(MonthlyDate,PdfName,CreatedDate,CreatedTime,CreatedBy) VALUES ('$monthly_date','$monthly_pdf','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_book_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_book_details;
		

	}

	function UpdateMonthlyForm($data)
	{
		$monthly_date = $data['monthly_date'];
		$monthly_update_id = $data['monthly_update_id'];
		$CreatedDate = date('Y-m-d');

		$monthly_pdf = "";

		 if (isset($_FILES['monthly_pdf']['name'])  && $_FILES['monthly_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["monthly_pdf"]["name"]);
	        $monthly_pdf   = $monthly_date."monthly_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/monthly-affairs/".$monthly_pdf;
	        move_uploaded_file($_FILES["monthly_pdf"]["tmp_name"], $path);
	    }

		 $sql_mu = "MonthlyDate='$monthly_date' ,PdfName='$monthly_pdf',CreatedDate='$CreatedDate' WHERE id='$monthly_update_id'";
		 $table_name ='monthly_affairs';
		$response_insert_book_details = $this->_UpdateTableRecords($this->conn,$table_name,$sql_mu);
		
		return $response_insert_book_details;
		

	}

	
	public function getAllMonthlyAffairs($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "monthly_affairs", $where);
        return $Admission_details;
	}

	public function getTotalMonthlyAffair($table)
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

  function DeleteMonthly($data)
	{
		$CurrentID = $data['ID'];
		// Delete company
		$where = " where ID = $CurrentID";
		$response = $this->delete_identity_filter($this->conn,"monthly_affairs",$where);
		return $response;
	}
}