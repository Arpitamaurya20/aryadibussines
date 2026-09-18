<?php 
class BooksDispatch extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}


	public function getTotalDispatchBooks($table)
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

	function DeleteBook($data)
	{
		$BookID = $data['ID'];
		// Delete course
		$where = " where ID = $BookID";
		$response = $this->delete_identity_filter($this->conn,"student_book",$where);
		return $response;
	}

    function GetBookDispatchDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$book_details = $this->_getTableDetails($this->conn,'student_book', $where);
		return $book_details;
	}

	function ChangeDispatchedStatus($data)
	{	
		$status = $data['status'];
		$dispatch_id = $data['form_id'];
		

	    $old_status_details = $this->GetBookDispatchDetailsbyID($dispatch_id);
	    if($status == $old_status_details['Status'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " Status = '$status' where ID=$dispatch_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'student_book', $update_param);
			$response['message'] = "Status Update";
	    	
	    }

	    return $response;
	}

}