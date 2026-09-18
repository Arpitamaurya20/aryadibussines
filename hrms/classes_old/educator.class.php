<?php 
class Educator extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function DuplicateEducator_pass($data)
	{
		$educator_email = $data['educator_email'];
		$educator_phone = $data['educator_phone'];
		$filter = " where Email = '$educator_email' or PhoneNumber = '$educator_phone'";
		$response = $this->check_unique_identity_filter($this->conn,'educator', $filter);
		return $response;
	}

	function InsertEducatorForm($data)
	{
		$center_id = $data['center_id'];
		$educator_name = $data['educator_name'];
		$educator_designation = $data['educator_designation'];
		$educator_phone = $data['educator_phone'];
		$educator_alternative = $data['educator_alternative'];
		$educator_email = $data['educator_email'];
		$StrategySession = $data['Session'];

		$educator_details = $data['educator_details'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$educator_profile = "";

		if (isset($_FILES['educator_profile']['name'])  && $_FILES['educator_profile']['name'] != '')
	    {
	        $extn_pan = explode('.', $_FILES["educator_profile"]["name"]);
	        $educator_profile   = $educator_name."_item.".$extn_pan[1];
	        $path = "../../project-assets/admin-media/educator/".$educator_profile;
	        move_uploaded_file($_FILES["educator_profile"]["tmp_name"], $path);
	    }

		$sql = "INSERT INTO educator(CenterID,EducatorName,Designation,PhoneNumber,AlternativeNumber,Email,EducatorDetails,EducatorProfile,CreatedDate,CreatedTime,CreatedBy,StrategySession) VALUES ($center_id,'$educator_name','$educator_designation','$educator_phone','$educator_alternative','$educator_email','$educator_details','$educator_profile','$CreatedDate','$CreatedTime','$CreatedBy','$StrategySession')";
		$response_insert_educator_details = $this->_InsertTableRecords($this->conn,$sql);
		
		$response_insert_educator_details['message'] = "Educator is Successfully Added";
		return $response_insert_educator_details;
	}


	function UpdateEducatorForm($data)
	{	
		$center_id = $data['center_id'];
		$educator_name = $data['educator_name'];
		$educator_designation = $data['educator_designation'];
		$educator_details = $data['educator_details'];
		$educator_phone = $data['educator_phone'];
		$educator_alternative = $data['educator_alternative'];
		$educator_email = $data['educator_email'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$Educator_id = $data['form_id'];
	    $old_educator_details = $this->GetEducatorDetailsbyID($Educator_id);
	    if($center_id == $old_educator_details['CenterID'] && $educator_name == $old_educator_details['EducatorName']  && $educator_designation == $old_educator_details['Designation'] && $educator_details == $old_educator_details['EducatorDetails'] && $educator_email == $old_educator_details['Email'] && $educator_phone == $old_educator_details['PhoneNumber'] && $educator_alternative == $old_educator_details['AlternativeNumber'])
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = "CenterID = $center_id,EducatorName = '$educator_name', Designation = '$educator_designation', EducatorDetails= '$educator_details', Email= '$educator_email', PhoneNumber= '$educator_phone', AlternativeNumber= '$educator_alternative' where ID=$Educator_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'educator', $update_param);
			$response['message'] = "Educator Details Updated !";	

			
	    }

		if (isset($_FILES['educator_profile']['name'])  && $_FILES['educator_profile']['name'] != '')
		{
			$extn_pan = explode('.', $_FILES["educator_profile"]["name"]);
			$educator_profile   = $educator_name."_item.".$extn_pan[1];
			$path = "../../project-assets/admin-media/educator/".$educator_profile;
			$update_param = "EducatorProfile= '$educator_profile' where ID=$Educator_id";
			$response = $this->_UpdateTableRecords($this->conn,'educator', $update_param);
			move_uploaded_file($_FILES["educator_profile"]["tmp_name"], $path);
		}
			
	    return $response;
	}

	public function getTotalEducators($table)
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

	function DeleteEducators($data)
	{
		$EducatorID = $data['ID'];
		// Delete course
		$where = " where ID = $EducatorID";
		$response = $this->delete_identity_filter($this->conn,"educator",$where);
		return $response;
	}

    function GetEducatorDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$educator_details = $this->_getTableDetails($this->conn,'educator', $where);
		return $educator_details;
	}

	function GetAllEducators($conn)
	{
		$where = " where IsActive = 1";
		$educator_details = $this->_getTableRecords($this->conn,'educator', $where);
		return $educator_details;
	}

	function GetAllSubjectByEducatorID($EducatorID)
	{
		$where = " where IsActive = 1 and EducatorID = $EducatorID";
		$educator_all_subjects = $this->_getTableRecords($this->conn,'educator_subject', $where);
		return $educator_all_subjects;
	}

	function GetEducatorSubjectDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$educator_details = $this->_getTableDetails($this->conn,'educator_subject', $where);
		return $educator_details;
	}

	function InsertEducatorSubjectForm($data)
	{
		
		$Educator_ID = $data['educator_id'];
		$Subject_ID = $data['subject_id'];

		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');


		$sql = "INSERT INTO educator_subject(EducatorID,SubjectID,CreatedDate,CreatedTime,CreatedBy) VALUES ($Educator_ID,'$Subject_ID','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_educator_subject_details = $this->_InsertTableRecords($this->conn,$sql);
		
		$response_insert_educator_subject_details['message'] = "Educator Subject is Successfully Added";
		return $response_insert_educator_subject_details;
	}
	function DeleteEducatorSubject($data)
	{
		$ID = $data['ID'];
		$where = " where ID = $ID";
		$response = $this->delete_identity_filter($this->conn,"educator_subject",$where);
		return $response;
	}

	function GetAllDefaultSlotByEducatorsID($EducatorID)
	{
		$where = " where EducatorID = $EducatorID And IsActive = 1";
		$all_educator_default_slot = $this->_getTableRecords($this->conn,'educator_default_slot', $where);
		return $all_educator_default_slot;
	}

	public function CheckDuplicateDefaultSlotEducators($data)
	{
		$EduactorTimeSlot = $data['EduactorTimeSlot'];
		$EducatorID = $data['EducatorID'];
		$filter = " where Slot = '$EduactorTimeSlot' And EducatorID = $EducatorID";
		$response = $this->check_unique_identity_filter($this->conn,'educator_default_slot', $filter);
		return $response;
	}
	public function InsertDefaultSlotEducators($data)
	{
		$EduactorTimeSlot = $data['EduactorTimeSlot'];
		$EducatorID = $data['EducatorID'];

		$sql = "INSERT INTO educator_default_slot(Slot,EducatorID) VALUES ('$EduactorTimeSlot',$EducatorID)";
		$response_insert_subject_details = $this->_InsertTableRecords($this->conn,$sql);
		return $response_insert_subject_details;

	}
	public function UpdateDefaultSlotEducators($data)
	{
		extract($data);
		$update_sql = " Slot = '$EduactorTimeSlot' where ID = $add_slot_form_id";
		$response = $this->_UpdateTableRecords($this->conn,'educator_default_slot',$update_sql);
		return $response;
	}

}