<?php 
class Current extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCurrentForm($data)
	{
		$current_date = $data['current_date'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$current_pdf = "";

		 if (isset($_FILES['current_pdf']['name'])  && $_FILES['current_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["current_pdf"]["name"]);
	        $current_pdf   = $current_date."current_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/current-affairs/".$current_pdf;
	        move_uploaded_file($_FILES["current_pdf"]["tmp_name"], $path);
	    }

		 $sql = "INSERT INTO current_affairs(CurrentDate,PdfName,CreatedDate,CreatedTime,CreatedBy) VALUES ('$current_date','$current_pdf','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_book_details = $this->_InsertTableRecords($this->conn,$sql);
		
		return $response_insert_book_details;
		

	}

	function UpdateCurrentForm($data)
	{
		$current_date = $data['current_date'];
		$current_update_id = $data['current_update_id'];
		$CreatedDate = date('Y-m-d');

		$current_pdf = "";

		 if (isset($_FILES['current_pdf']['name'])  && $_FILES['current_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["current_pdf"]["name"]);
	        $current_pdf   = $current_date."current_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/monthly-affairs/".$current_pdf;
	        move_uploaded_file($_FILES["current_pdf"]["tmp_name"], $path);
	    }

		 $sql_mu = "CurrentDate='$current_date' ,PdfName='$current_pdf',CreatedDate='$CreatedDate' WHERE id='$current_update_id'";
		 $table_name ='current_affairs';
		$response_insert_book_details = $this->_UpdateTableRecords($this->conn,$table_name,$sql_mu);
		
		return $response_insert_book_details;
		

	}
	public function getAllCurrentAffairs($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "current_affairs", $where);
        return $Admission_details;
	}

	public function getTotalCurrentAffair($table)
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

  function DeleteCurrent($data)
	{
		$CurrentID = $data['ID'];
		// Delete company
		$where = " where ID = $CurrentID";
		$response = $this->delete_identity_filter($this->conn,"current_affairs",$where);
		return $response;
	}
}