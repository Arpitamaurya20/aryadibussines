<?php 
class Books extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertBookForm($data)
	{
		$book_name = $data['book_name'];
		$book_price = $data['book_price'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$book_pdf = "";

		 if (isset($_FILES['book_pdf']['name'])  && $_FILES['book_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["book_pdf"]["name"]);
	        $book_pdf   = $book_name."book_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/book/".$book_pdf;
	        move_uploaded_file($_FILES["book_pdf"]["tmp_name"], $path);
	    }

		$sql = "INSERT INTO books(BookName,Price,BookPDF,CreatedDate,CreatedTime,CreatedBy) VALUES ('$book_name','$book_price','$book_pdf','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_book_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_book_details['message'] = "Book is Successfully Added.";
		
		return $response_insert_book_details;
		

	}


	function UpdateBookForm($data)
	{	
		$book_name = $data['book_name'];
		$book_price = $data['book_price'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$Book_id = $data['form_id'];
		
		// $book_pdf = "";

		// if (isset($data['book_pdf']['name'])  && $data['book_pdf']['name'] != '')
	    // {
	    //     $extn_pan = explode('.', $data["book_pdf"]["name"]);
	    //     $book_pdf   = $book_name."_item.".$extn_pan[1];
	    //     $path = "../../../assets/media/".$book_pdf;
	    //     move_uploaded_file($data["book_pdf"]["tmp_name"], $path);
	   	    
	    // }

	    $old_book_details = $this->GetBookDetailsbyID($Book_id);
	    if($book_name == $old_book_details['BookName']  && $book_price == $old_book_details['Price'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " BookName = '$book_name', Price = '$book_price' where ID=$Book_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'books', $update_param);
			$response['message'] = "Book Update";
	    	
	    }

	    return $response;
	}

	public function GetAllBooks($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "books", $where);
        return $Admission_details;
	}

	public function getTotalBooks($table)
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
		$response = $this->delete_identity_filter($this->conn,"books",$where);
		return $response;
	}

    function GetBookDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$book_details = $this->_getTableDetails($this->conn,'books', $where);
		return $book_details;
	}

}