<?php 
class CompanyDetails extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertCompanyDetailsForm($data)
	{
		$company_name = $data['company_name'];
		$company_email = $data['company_email'];
		$company_phone_number = $data['company_phone_number'];
		$company_alternative_number = $data['company_alternative_number'];
		$company_address = $data['company_address'];
		$company_gst_number = $data['company_gst_number'];
		$center_id = $data['center_id'];

		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		// $company_logo = "";

		//  if (isset($_FILES['company_logo']['name'])  && $_FILES['company_logo']['name'] != '')
	    // {
	    //     $extn_pan = explode('.', $_FILES["company_logo"]["name"]);
	    //     $company_logo   = $company_name."company_logo.".$extn_pan[1];
	    //     $path = "../../../assets/media/".$book_pdf;
	    //     move_uploaded_file($_FILES["company_logo"]["tmp_name"], $path);
	    // }

		$sql = "INSERT INTO company_details(CompanyName,Email,PhoneNumber,AlternativeNumber,Address,GSTNumber,CenterID,CreatedDate,CreatedTime,CreatedBy) VALUES ('$company_name','$company_email','$company_phone_number','$company_alternative_number','$company_address','$company_gst_number','$center_id','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_company_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_company_details['message'] = "Company Details Sussessfully Added !";
		
		return $response_insert_company_details;
		

	}


	function UpdateCompanyDetailsForm($data)
	{	
		$company_name = $data['company_name'];
		$company_email = $data['company_email'];
		$company_phone_number = $data['company_phone_number'];
		$company_alternative_number = $data['company_alternative_number'];
		$company_address = $data['company_address'];
		$company_gst_number = $data['company_gst_number'];
		$center_id = $data['center_id'];

		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$Company_id = $data['form_id'];
		
		// $company_logo = "";

		// if (isset($data['company_logo']['name'])  && $data['company_logo']['name'] != '')
	    // {
	    //     $extn_pan = explode('.', $data["company_logo"]["name"]);
	    //     $company_logo   = $company_name."_item.".$extn_pan[1];
	    //     $path = "../../../assets/media/".$company_logo;
	    //     move_uploaded_file($data["company_logo"]["tmp_name"], $path);
	   	    
	    // }

	    $old_company_details = $this->GetCompanyDetailsbyID($Company_id);
	    if($company_name == $old_company_details['CompanyName']  && $company_email == $old_company_details['Email'] && $company_phone_number == $old_company_details['PhoneNumber']  && $company_alternative_number == $old_company_details['AlternativeNumber'] && $company_address == $old_company_details['Address']  && $company_gst_number == $old_company_details['GSTNumber'] && $center_id == $old_company_details['CenterID']  )
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " CompanyName = '$company_name', Email = '$company_email',PhoneNumber = '$company_phone_number', AlternativeNumber = '$company_alternative_number',Address = '$company_address', GSTNumber = '$company_gst_number',CenterID = '$center_id' where ID=$Company_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'company_details', $update_param);
			$response['message'] = "Company Data Updated";
	    	
	    }

	    return $response;
	}

	public function GetAllCompanyDetails($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $Admission_details = $this->_getTableRecords($conn, "company_details", $where);
        return $Admission_details;
	}

	public function getTotalCompanyDetails($table)
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

	function DeleteCompanyDetails($data)
	{
		$CompanyID = $data['ID'];
		// Delete course
		$where = " where ID = $CompanyID";
		$response = $this->delete_identity_filter($this->conn,"company_details",$where);
		return $response;
	}

    function GetCompanyDetailsbyID($conn)
	{
		$where = " where ID = 1";
		$company_details = $this->_getTableDetails($this->conn,'company_details', $where);
		return $company_details;
	}

}