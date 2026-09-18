<?php 
class Magazine extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertMagazineForm($data)
	{
		$magazine_name = $data['magazine_name'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$magazine_pdf = "";

		 if (isset($_FILES['magazine_pdf']['name'])  && $_FILES['magazine_pdf']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["magazine_pdf"]["name"]);
	        $magazine_pdf   = $magazine_name."magazine_pdf.".$extn_pan[1];
	        $path = "../../../assets/admin-media/magazine/".$magazine_pdf;
	        move_uploaded_file($_FILES["magazine_pdf"]["tmp_name"], $path);
	    }

		$sql = "INSERT INTO magazine(MagazineName,MagazinePDF,CreatedDate,CreatedTime,CreatedBy) VALUES ('$magazine_name','$magazine_pdf','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_magazine_details = $this->_InsertTableRecords($this->conn,$sql);

		$response['message'] = "Magazine is Successfully Added!";
		
		return $response_insert_magazine_details;
		

	}


	function UpdateMagazineForm($data)
	{	
		$magazine_name = $data['magazine_name'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$magazine_id = $data['form_id'];
		
		// $magazine_pdf = "";

		// if (isset($data['magazine_pdf']['name'])  && $data['magazine_pdf']['name'] != '')
	    // {
	    //     $extn_pan = explode('.', $data["magazine_pdf"]["name"]);
	    //     $magazine_pdf   = $magazine_name."_item.".$extn_pan[1];
	    //     $path = "../../../assets/media/".$magazine_pdf;
	    //     move_uploaded_file($data["magazine_pdf"]["tmp_name"], $path);
	   	    
	    // }

	    $old_magazine_details = $this->GetMagazineDetailsbyID($magazine_id);
	    if($magazine_name == $old_magazine_details['MagazineName'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " MagazineName = '$magazine_name' where ID=$magazine_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'magazine', $update_param);
			$response['message'] = "Magazine Data Updated !";
	    	
	    }

	    return $response;
	}

	public function GetAllMagazine ($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "magazine", $where);
        return $Admission_details;
	}

	public function getTotalMagazine($table)
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

	function DeleteMagazine($data)
	{
		$MagazineID = $data['ID'];
		// Delete course
		$where = " where ID = $MagazineID";
		$response = $this->delete_identity_filter($this->conn,"magazine",$where);
		return $response;
	}

    function GetMagazineDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$magazine_details = $this->_getTableDetails($this->conn,'magazine', $where);
		return $magazine_details;
	}

}