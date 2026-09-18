<?php 
class Coursesproduct extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}


	public function InsertCoursesProduct($data)
	{
         $course_name = $data['course_p_name'];
		$course_type = $data['course_p_type'];
		$course_des  =$data['course_p_description'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO courseproduct (ProductName,ProductType,ProductDescription,CreatedDate,CreatedBy,CreatedTime) VALUES ('$course_name','$course_type','$course_des','$CreatedDate','$CreatedBy','$CreatedTime')";
		$response_insert_course_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_course_details['message'] = "Course Product is Successfully Added !";
		
		return $response_insert_course_details;
	}

	function UpdateCourseProduct($data)
	{
		    $course_name = $data['course_p_name'];
		    $course_type = $data['course_p_type'];
		    $course_des  = $data['course_p_description'];
		    $Course_id   = $data['form_id'];
		    $CreatedBy   = $data['CreatedBy'];
		    $UpdatedDate = date('Y-m-d');
		    $UpdatedTime = date('H:i:s');
		    $new_educators = $data['assign_to_educator'];

		   
		    $old_course_details = $this->GetCourseProductDetailsbyID($Course_id);
		    $old_educators = $old_course_details['AssignedEducators'];

		    
		    sort($new_educators);
		    sort($old_educators);
		    $educators_changed = $new_educators !== $old_educators;


		    $course_changed = !(
		        $course_name == $old_course_details['ProductName'] &&
		        $course_type == $old_course_details['ProductType'] &&
		        $course_des  == $old_course_details['ProductDescription']
		    );

		    if (!$course_changed && !$educators_changed) {
		        $response['message'] = "No changes to update";
		        $response['error'] = true;
		    } else {
		        if ($course_changed) {
		            
		            $update_param = " 
		                ProductName = '$course_name', 
		                ProductType = '$course_type', 
		                ProductDescription = '$course_des',
		                CreatedBy = '$CreatedBy',
		                CreatedDate = '$UpdatedDate',
		                CreatedTime = '$UpdatedTime'
		                WHERE ID = $Course_id
		            ";

		            $response = $this->_UpdateTableRecords($this->conn, 'courseproduct', $update_param);
		            $response['message'] = "Course Product updated.";
		        } else {
		            $response['message'] = "Educator access updated.";
		            $response['error'] = false;
		        }

		        $response['last_insert_id'] = $Course_id;
		    }

		    return $response;
		}




		public function InsertProductAccess($data)
		{
		    $productID = $data['productID'];
		    $educatorIDs = $data['assign_to_educator'];
		    foreach ($educatorIDs as $educatorID) {
		        $sql = "INSERT INTO course_product_access (ProductID, EducatorID) VALUES ('$productID', '$educatorID')";
		        $this->_InsertTableRecords($this->conn, $sql);
		    }
		}


       public function InsertBatchProduct($data)
			{
			    $BatchId = $data['BatchID'];
			    $productIds = $data['add_product'];
			    $results = [];

			    foreach ($productIds as $p_Id) {
			        $sql = "INSERT INTO assosiative_course_product (ProductID, BatchId) VALUES ($p_Id, $BatchId)";
			        $insertResult = $this->_InsertTableRecords($this->conn, $sql);
			        if ($insertResult) {
			            $results[] = ["product_id" => $p_Id, "status" => "success"];
			        } else {
			            $results[] = ["product_id" => $p_Id, "status" => "failed"];
			        }
			    }
			    $all_success = array_reduce($results, function ($carry, $item) {
			        return $carry && $item['status'] === "success";
			    }, true);

			    return [
			        "error" => !$all_success,
			        "message" => $all_success ? "All products added successfully." : "Some products failed to insert.",
			        "details" => $results
			    ];
			}
		
		public function UpdateProductAccess($data)
		{
		    $productID = $data['productID'];
		    $educatorIDs = $data['assign_to_educator'];
		    $sqlDelete = "WHERE ProductID = '$productID'";
		    $this->delete_identity_filter($this->conn,"course_product_access",$sqlDelete);
		    $this->InsertProductAccess($data);
		}

	function DeleteCourseProduct($data)
	{
		$CourseID = $data['ID'];
		// Delete course
		$where = " where ID = $CourseID";
		$response = $this->delete_identity_filter($this->conn,"courseproduct",$where);
		return $response;
	}

	function DeleteCourseProductDawpWriting($data)
	{
		$DawpID = $data['ID'];
		$where = " where ID = $DawpID";
		$response = $this->delete_identity_filter($this->conn,"dawp",$where);
		return $response;
	}

	function DeleteCourseProductTestSeriesWriting($data)
	{
		$TestseriesID = $data['ID'];
		$where = " where ID = $TestseriesID";
		$response = $this->delete_identity_filter($this->conn," cptestseries",$where);
		return $response;
	}

	function GetCourseProductDetailsbyID($ID)
	{
		  $where = " WHERE ID = $ID";
		    $product_details = $this->_getTableDetails($this->conn, 'courseproduct', $where);

		    if (!empty($product_details)) {
		        $educator_sql = "SELECT EducatorID FROM course_product_access WHERE ProductID = $ID";
		        $educator_result = mysqli_query($this->conn, $educator_sql);
		        $educators = [];

		        while ($row = mysqli_fetch_assoc($educator_result)) {
		            $educators[] = $row['EducatorID'];
		        }

		        $product_details['AssignedEducators'] = $educators;
		    }

		    return $product_details;
	}


	function InsertDawp($data)
	{
				$daw_title           = $data['daw_title'];
				$daw_daw_product_id = $data['daw_product_id'];
				$daw_course          = $data['daw_course'];
				$daw_type            = $data['daw_type'];
				$daw_mode            = $data['daw_mode'];
				$daw_start_date      = date('Y-m-d', strtotime($data['daw_start_date']));
				$daw_end_date        = date('Y-m-d', strtotime($data['daw_end_date']));
				$daw_duration        = $data['daw_duration'];
				$daw_question        = $data['daw_question'];
				$daw_total_marks     = $data['daw_total_marks'];
				$daw_language        = $data['daw_language'];
				$daw_question_format = $data['daw_question_format'];
				$daw_question_file   = $data['daw_question_file'];
				$daw_answer_file     = $data['daw_answer_file'];
				$daw_qcab_file       = $data['daw_QCAB_File'];
				$daw_question_status = $data['daw_question_status'];
				$daw_description     = $data['daw_description'];
				$CreatedDate         = date('Y-m-d');
				$CreatedTime         = date('H:i:s');
				$CreatedBy           = $data['CreatedBy']; // Ensure this is passed in payload
				$IsActive            = 1;

				$sql = "INSERT INTO dawp (
				   ProductID, Title, Course, Type, Mode, StartDate, EndDate, Duration,TotalQuestion,TotalMark, 
				    Language, QuestionFormat, QuestionFile, SolutionFile,QCABFile, Status, Description, 
				    CreatedDate, CreatedTime, CreatedBy, IsActive
				) VALUES (
				  '$daw_daw_product_id',  '$daw_title', '$daw_course', '$daw_type', '$daw_mode', '$daw_start_date', '$daw_end_date', '$daw_duration','$daw_question', '$daw_total_marks',
				    '$daw_language', '$daw_question_format', '$daw_question_file', '$daw_answer_file', '$daw_qcab_file','$daw_question_status', '$daw_description',
				    '$CreatedDate', '$CreatedTime', '$CreatedBy', '$IsActive'
				)";

				$response_insert_dawp_details = $this->_InsertTableRecords($this->conn, $sql);
				$response_insert_dawp_details['message'] = "DAWP Product is Successfully Added!";

				return $response_insert_dawp_details;

	}

	function UpdateDawp($data)
	{

		$daw_id              = $data['daw_form_id'];
		$daw_title           = $data['daw_title'];
		$daw_course          = $data['daw_course'];
		$daw_type            = $data['daw_type'];
		$daw_mode            = $data['daw_mode'];
		$daw_start_date      = date('Y-m-d', strtotime($data['daw_start_date']));
		$daw_end_date        = date('Y-m-d', strtotime($data['daw_end_date']));
		$daw_duration        = $data['daw_duration'];
		$daw_question        = $data['daw_question'];
		$daw_total_marks     = $data['daw_total_marks'];
		$daw_language        = $data['daw_language'];
		$daw_question_format = $data['daw_question_format'];
		$daw_question_file   = $data['daw_question_file'];
		$daw_answer_file     = $data['daw_answer_file'];
		$daw_qcab_file       = $data['daw_QCAB_File'];
		$daw_question_status = $data['daw_question_status'];
		$daw_description     = $data['daw_description'];
		$CreatedBy           = $data['CreatedBy'];
		$UpdatedDate         = date('Y-m-d');
		$UpdatedTime         = date('H:i:s');

		// Fetch old DAWP details
		$old_dawp_details = $this->GetDawDetailsbyID($daw_id);

		// Check if any value has changed
		if (
		    $daw_title           == $old_dawp_details['Title'] &&
		    $daw_course          == $old_dawp_details['Course'] &&
		    $daw_type            == $old_dawp_details['Type'] &&
		    $daw_mode            == $old_dawp_details['Mode'] &&
		    $daw_start_date      == $old_dawp_details['StartDate'] &&
		    $daw_end_date        == $old_dawp_details['EndDate'] &&
		    $daw_duration        == $old_dawp_details['Duration'] &&
		    $daw_question        == $old_dawp_details['TotalQuestion'] &&
		    $daw_total_marks     == $old_dawp_details['TotalMark'] &&
		    $daw_language        == $old_dawp_details['Language'] &&
		    $daw_question_format == $old_dawp_details['QuestionFormat'] &&
		    $daw_question_file   == $old_dawp_details['QuestionFile'] &&
		    $daw_answer_file     == $old_dawp_details['SolutionFile'] &&
			$daw_qcab_file     == $old_dawp_details['QCABFile'] &&
		    $daw_question_status == $old_dawp_details['Status'] &&
		    $daw_description     == $old_dawp_details['Description']
		) {
		    $response['message'] = "No changes to update";
		    $response['error'] = true;
		} else {
		    // Prepare update query
		    $update_param = "
		        Title = '$daw_title',
		        Course = '$daw_course',
		        Type = '$daw_type',
		        Mode = '$daw_mode',
		        StartDate = '$daw_start_date',
		        EndDate = '$daw_end_date',
		        Duration = '$daw_duration',
		        TotalQuestion = '$daw_question',
		        TotalMark = '$daw_total_marks',
		        Language = '$daw_language',
		        QuestionFormat = '$daw_question_format',
		        QuestionFile = '$daw_question_file',
		        SolutionFile = '$daw_answer_file',
				QCABFile = '$daw_qcab_file',
		        Status = '$daw_question_status',
		        Description = '$daw_description',
		        CreatedBy = '$CreatedBy',
		        CreatedDate = '$UpdatedDate',
		        CreatedTime = '$UpdatedTime'
		        WHERE ID = $daw_id
		    ";

		    $response = $this->_UpdateTableRecords($this->conn, 'dawp', $update_param);
		    $response['message'] = "DAWP Product successfully updated!";
		}

		return $response;


	}


	// ------course product testseries-------------------
	function InsertTestSeriesCP($data)
	{
				$testseries_title           = $data['testseries_title'];
				$testseries_product_id = $data['testseries_product_id'];
				$testseries_course          = $data['testseries_course'];
				$testseries_type            = $data['testseries_type'];
				$testseries_mode            = $data['testseries_mode'];
				$testseries_start_date      = date('Y-m-d', strtotime($data['testseries_start_date']));
				$testseries_end_date        = date('Y-m-d', strtotime($data['testseries_end_date']));
				$testseries_duration        = $data['testseries_duration'];
				$testseries_question        = $data['testseries_question'];
				$testseries_total_marks     = $data['testseries_total_marks'];
				$testseries_language        = $data['testseries_language'];
				$testseries_question_format = $data['testseries_question_format'];
				$testseries_question_file   = $data['testseries_question_file'];
				$testseries_answer_file     = $data['testseries_answer_file'];
				$testseries_qcab_file   = $data['testseries_QCAB_File'];
				$testseries_question_status = $data['testseries_question_status'];
				$testseries_description     = $data['testseries_description'];
				$CreatedDate         = date('Y-m-d');
				$CreatedTime         = date('H:i:s');
				$CreatedBy           = $data['CreatedBy']; // Ensure this is passed in payload
				$IsActive            = 1;

				$sql = "INSERT INTO cptestseries (
				   ProductID, Title, Course, Type, Mode, StartDate, EndDate, Duration,TotalQuestion,TotalMark, 
				    Language, QuestionFormat, QuestionFile, SolutionFile,QCABFile, Status, Description, 
				    CreatedDate, CreatedTime, CreatedBy, IsActive
				) VALUES (
				  '$testseries_product_id',  '$testseries_title', '$testseries_course', '$testseries_type', '$testseries_mode', '$testseries_start_date', '$testseries_end_date', '$testseries_duration','$testseries_question', '$testseries_total_marks',
				    '$testseries_language', '$testseries_question_format', '$testseries_question_file', '$testseries_answer_file','$testseries_qcab_file', '$testseries_question_status', '$testseries_description',
				    '$CreatedDate', '$CreatedTime', '$CreatedBy', '$IsActive'
				)";

				$response_insert_testseries_details = $this->_InsertTableRecords($this->conn, $sql);
				$response_insert_testseries_details['message'] = "testseries Product is Successfully Added!";

				return $response_insert_testseries_details;

	}

	function UpdateTestSeriesCP($data)
{
    $testseries_id              = $data['testseries_form_id'];
    $testseries_title           = $data['testseries_title'];
    $testseries_product_id      = $data['testseries_product_id'];
    $testseries_course          = $data['testseries_course'];
    $testseries_type            = $data['testseries_type'];
    $testseries_mode            = $data['testseries_mode'];
    $testseries_start_date      = date('Y-m-d', strtotime($data['testseries_start_date']));
    $testseries_end_date        = date('Y-m-d', strtotime($data['testseries_end_date']));
    $testseries_duration        = $data['testseries_duration'];
    $testseries_question        = $data['testseries_question'];
    $testseries_total_marks     = $data['testseries_total_marks'];
    $testseries_language        = $data['testseries_language'];
    $testseries_question_format = $data['testseries_question_format'];
    $testseries_question_file   = $data['testseries_question_file'];
		$testseries_qcab_file       = $data['testseries_QCAB_File'];
    $testseries_answer_file     = $data['testseries_answer_file'];
    $testseries_question_status = $data['testseries_question_status'];
    $testseries_description     = $data['testseries_description'];
    $CreatedBy                  = $data['CreatedBy'];
    $UpdatedDate                = date('Y-m-d');
    $UpdatedTime                = date('H:i:s');

    // Fetch old details
    $old_testseries_details = $this->GetcptestseriesDetailsbyID($testseries_id);

    // Check for changes
    if (
        $testseries_title           == $old_testseries_details['Title'] &&
        $testseries_product_id      == $old_testseries_details['ProductID'] &&
        $testseries_course          == $old_testseries_details['Course'] &&
        $testseries_type            == $old_testseries_details['Type'] &&
        $testseries_mode            == $old_testseries_details['Mode'] &&
        $testseries_start_date      == $old_testseries_details['StartDate'] &&
        $testseries_end_date        == $old_testseries_details['EndDate'] &&
        $testseries_duration        == $old_testseries_details['Duration'] &&
        $testseries_question        == $old_testseries_details['TotalQuestion'] &&
        $testseries_total_marks     == $old_testseries_details['TotalMark'] &&
        $testseries_language        == $old_testseries_details['Language'] &&
        $testseries_question_format == $old_testseries_details['QuestionFormat'] &&
        $testseries_question_file   == $old_testseries_details['QuestionFile'] &&
        $testseries_answer_file     == $old_testseries_details['SolutionFile'] &&
				$testseries_qcab_file     == $old_testseries_details['QCABFile'] &&
        $testseries_question_status == $old_testseries_details['Status'] &&
        $testseries_description     == $old_testseries_details['Description']
    ) {
        $response['message'] = "No changes to update";
        $response['error'] = true;
    } else {
        // Prepare update query
        $update_param = "
            ProductID = '$testseries_product_id',
            Title = '$testseries_title',
            Course = '$testseries_course',
            Type = '$testseries_type',
            Mode = '$testseries_mode',
            StartDate = '$testseries_start_date',
            EndDate = '$testseries_end_date',
            Duration = '$testseries_duration',
            TotalQuestion = '$testseries_question',
            TotalMark = '$testseries_total_marks',
            Language = '$testseries_language',
            QuestionFormat = '$testseries_question_format',
            QuestionFile = '$testseries_question_file',
            SolutionFile = '$testseries_answer_file',
						QCABFile = '$testseries_qcab_file',
            Status = '$testseries_question_status',
            Description = '$testseries_description',
            CreatedBy = '$CreatedBy',
            CreatedDate = '$UpdatedDate',
            CreatedTime = '$UpdatedTime'
            WHERE ID = $testseries_id
        ";

        $response = $this->_UpdateTableRecords($this->conn, 'cptestseries', $update_param);
        $response['message'] = "Test Series Product successfully updated!";
    }

    return $response;
}



	public function GetDawDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$details = $this->_getTableDetails($this->conn,'dawp', $where);
		return $details;
	}

	public function GetcptestseriesDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$details = $this->_getTableDetails($this->conn,'cptestseries', $where);
		return $details;
	}

  public function InsertDawpStudentresponse($data)
{
 

      $response = [
        'error' => false,
        'message' => 'Data inserted successfully'
    ];

    // File upload handling
    // $student_response_pdf = null;

    // if (isset($_FILES['student_response']) && $_FILES['student_response']['name'] != '') {
    //     $file_ext = pathinfo($_FILES['student_response']['name'], PATHINFO_EXTENSION);

    //     // Only allow PDF files
    //     if (strtolower($file_ext) !== 'pdf') {
    //         return [
    //             'error' => true,
    //             'message' => 'Only PDF files are allowed for student response.'
    //         ];
    //     }

    //     // Define file name and path
    //     $filename = "DAW_" . time() . "." . $file_ext;
    //     $upload_dir = "../studentresponse/dawresponse/";
    //     $target_path = $upload_dir . $filename;

    //     // Ensure upload directory exists
    //     if (!is_dir($upload_dir)) {
    //         mkdir($upload_dir, 0755, true);
    //     }

    //     if (!move_uploaded_file($_FILES['student_response']['tmp_name'], $target_path)) {
    //         return [
    //             'error' => true,
    //             'message' => 'Failed to upload student response PDF.'
    //         ];
    //     }

    //     // Store relative path or filename in DB
    //     $student_response_pdf = "../studentresponse/dawresponse/" . $filename;
    // }


    // $evaluated_pdf = null;

    // if (isset($_FILES['evaluated_sheet']) && $_FILES['evaluated_sheet']['name'] != '') {
    //     $file_ext = pathinfo($_FILES['evaluated_sheet']['name'], PATHINFO_EXTENSION);

    //     // Only allow PDF files
    //     if (strtolower($file_ext) !== 'pdf') {
    //         return [
    //             'error' => true,
    //             'message' => 'Only PDF files are allowed for student response.'
    //         ];
    //     }

    //     // Define file name and path
    //     $filename = "DAW_" . time() . "." . $file_ext;
    //     $upload_dir = "../studentresponse/dawevaluatedsheet/";
    //     $target_path = $upload_dir . $filename;

    //     // Ensure upload directory exists
    //     if (!is_dir($upload_dir)) {
    //         mkdir($upload_dir, 0755, true);
    //     }

    //     if (!move_uploaded_file($_FILES['evaluated_sheet']['tmp_name'], $target_path)) {
    //         return [
    //             'error' => true,
    //             'message' => 'Failed to upload student response PDF.'
    //         ];
    //     }

    //     // Store relative path or filename in DB
    //     $evaluated_pdf = "../studentresponse/dawresponse/" . $filename;
    // }


     $student_response_pdf = $data['student_response_pdf'] ?? null;
    $evaluated_pdf = $data['evaluated_pdf'] ?? null;
    // Prepare DB insert data
    $table = "student_daw_response";
    $columns = [
        "DawID", "Student", "Email", "PhoneNumber", "ResponseSheet",
        "SubmittedAt", "EvaluatedSheet","Score","EvaluatedVideo"
    ];

    $values = [
        $data['daw_id'],
        $data['user_name'],
        $data['user_email'],
        $data['user_mobile'],
        $student_response_pdf,
        date("Y-m-d H:i:s"), // SubmittedAt
        $evaluated_pdf ,
        $data['score'],               // EvaluatedSheet
        $data['evaluated_video_url']
      
       
    ];

    // Call insert helper
    $result = $this->_InsertPreparedData($this->conn, $table, $columns, $values);

    return $result;
}




  public function InsertCptestseriesStudentresponse($data)
{
 

      $response = [
        'error' => false,
        'message' => 'Data inserted successfully'
    ];

    // File upload handling
    // $student_response_pdf = null;

    // if (isset($_FILES['student_response']) && $_FILES['student_response']['name'] != '') {
    //     $file_ext = pathinfo($_FILES['student_response']['name'], PATHINFO_EXTENSION);

    //     // Only allow PDF files
    //     if (strtolower($file_ext) !== 'pdf') {
    //         return [
    //             'error' => true,
    //             'message' => 'Only PDF files are allowed for student response.'
    //         ];
    //     }

    //     // Define file name and path
    //     $filename = "DAW_" . time() . "." . $file_ext;
    //     $upload_dir = "../studentresponse/dawresponse/";
    //     $target_path = $upload_dir . $filename;

    //     // Ensure upload directory exists
    //     if (!is_dir($upload_dir)) {
    //         mkdir($upload_dir, 0755, true);
    //     }

    //     if (!move_uploaded_file($_FILES['student_response']['tmp_name'], $target_path)) {
    //         return [
    //             'error' => true,
    //             'message' => 'Failed to upload student response PDF.'
    //         ];
    //     }

    //     // Store relative path or filename in DB
    //     $student_response_pdf = "../studentresponse/dawresponse/" . $filename;
    // }


    // $evaluated_pdf = null;

    // if (isset($_FILES['evaluated_sheet']) && $_FILES['evaluated_sheet']['name'] != '') {
    //     $file_ext = pathinfo($_FILES['evaluated_sheet']['name'], PATHINFO_EXTENSION);

    //     // Only allow PDF files
    //     if (strtolower($file_ext) !== 'pdf') {
    //         return [
    //             'error' => true,
    //             'message' => 'Only PDF files are allowed for student response.'
    //         ];
    //     }

    //     // Define file name and path
    //     $filename = "DAW_" . time() . "." . $file_ext;
    //     $upload_dir = "../studentresponse/dawevaluatedsheet/";
    //     $target_path = $upload_dir . $filename;

    //     // Ensure upload directory exists
    //     if (!is_dir($upload_dir)) {
    //         mkdir($upload_dir, 0755, true);
    //     }

    //     if (!move_uploaded_file($_FILES['evaluated_sheet']['tmp_name'], $target_path)) {
    //         return [
    //             'error' => true,
    //             'message' => 'Failed to upload student response PDF.'
    //         ];
    //     }

    //     // Store relative path or filename in DB
    //     $evaluated_pdf = "../studentresponse/dawresponse/" . $filename;
    // }


     $student_response_pdf = $data['student_response_pdf'] ?? null;
    $evaluated_pdf = $data['evaluated_pdf'] ?? null;
    // Prepare DB insert data
    $table = "student_testseries_response";
    $columns = [
        "TestseriesID", "Student", "Email", "PhoneNumber", "ResponseSheet",
        "SubmittedAt", "EvaluatedSheet","Score","EvaluatedVideo"
    ];

    $values = [
        $data['testseries_id'],
        $data['user_name'],
        $data['user_email'],
        $data['user_mobile'],
        $student_response_pdf,
        date("Y-m-d H:i:s"), // SubmittedAt
        $evaluated_pdf ,
        $data['score'],               // EvaluatedSheet
        $data['evaluated_video_url']
      
       
    ];

    // Call insert helper
    $result = $this->_InsertPreparedData($this->conn, $table, $columns, $values);

    return $result;
}

public function UpdateEvaluatedDetails($data)
{
	    $ResponseID=$data['daw_response_id'];
	    $EvaluatedAt=$data['evaluated_at'];
		$update_sql = "EvaluatedAt= '$EvaluatedAt' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_daw_response', $update_sql);
		return $response;
}

 public function UpdateEvaluatedScoreLink($data)
{
	    $ResponseID=$data['edit_id'];
	    $Score=$data['score'];
	    $Video=$data['evaluated_video_url'];
		$update_sql = "Score= $Score , EvaluatedVideo='$Video' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_daw_response', $update_sql);
		return $response;
}


 public function UpdateResourceTitle($data)
{
	    $resource_edit_id=$data['resource_edit_id'];
	    $resource_edit_title=$data['resource_edit_title'];
		$update_sql = "Title='$resource_edit_title'  where ID = $resource_edit_id";
		$response = $this->_UpdateTableRecords($this->conn, 'open_creator', $update_sql);
		return $response;
}

public function DeleteResource($ID)
{
	    
		$update_sql = "IsActive='0'  where ID = $ID";
		$response = $this->_UpdateTableRecords($this->conn, 'open_creator', $update_sql);
		return $response;
}

public function UpdateEvaluatedScoreLinkTestSeries($data)
{
	    $ResponseID=$data['edit_id_testseries'];
	    $Score=$data['testseries_score'];
	    $Video=$data['testseries_evaluated_video_url'];
		$update_sql = "Score= $Score , EvaluatedVideo='$Video' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_testseries_response', $update_sql);
		return $response;
}

public function UpdateAssignDetails($data)
{
	   $ResponseID=$data['s_daw_response_id'];
	    $assing_to=$data['assing_to'];
		$update_sql = "AssignedTo= '$assing_to' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_daw_response', $update_sql);
		return $response;
}


public function UpdatecptestseriesEvaluatedDetails($data)
{
	    $ResponseID=$data['testseries_id'];
	    $EvaluatedAt=$data['evaluated_at'];
		$update_sql = "EvaluatedAt= '$EvaluatedAt' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_testseries_response', $update_sql);
		return $response;
}

public function UpdatecptestseriesAssignDetails($data)
{
	    $ResponseID=$data['cptest_response_id'];
	    $assing_to=$data['assing_to'];
		$update_sql = "AssignedTo= '$assing_to' where ID = $ResponseID";
		$response = $this->_UpdateTableRecords($this->conn, 'student_testseries_response', $update_sql);
		return $response;
}

function GetAllCourseProduct()
	{
		$where = " where 1 ORDER BY ID DESC";
        $product_details = $this->_getTableRecords($this->conn, "courseproduct", $where);
        return $product_details;
	}


public function GetAllAssociativeProductofBatchByID($ID)
{
     $sql="SELECT * from assosiative_course_product acp LEFT JOIN courseproduct cp ON acp.ProductID=cp.ID WHERE acp.BatchID=$ID ";
    $data=$this->_getRecords($this->conn,$sql);
    return $data;
}

public function GetAllRecordedResourcesByProductID($ID)
{
	 $sql="SELECT * from recorded_classes WHERE CourseProductID=$ID ";
    $data=$this->_getRecords($this->conn,$sql);
     foreach ($data as &$item) {
        $videoId = ltrim($item['Content'], '/');
        $item['Content'] = "https://youtube.com/embed/" . $videoId;
    }
    return $data;
}

public function GetAllLiveResourcesByProductID($ID)
{
    $sql = "SELECT * FROM live_calsses WHERE CourseProductID = $ID";
    $data = $this->_getRecords($this->conn, $sql);

    foreach ($data as &$item) {
        if (strtolower($item['Source']) === 'zoom' && !empty($item['ZoomID'])) {
            $zoomID = trim($item['ZoomID']);
            $zoomPass = trim($item['ZoomPass']);
            $encodedPass = urlencode($zoomPass); 
            $item['JoinLink'] = "https://zoom.us/j/{$zoomID}?pwd={$encodedPass}";
        }
        elseif (strtolower($item['Source']) === 'youtube' && !empty($item['Content'])) {
            $videoId = ltrim($item['Content'], '/');
            $item['JoinLink'] = "https://youtube.com/embed/" . $videoId;
        } else {
            $item['JoinLink'] = '';
        }
    }

    return $data;
}

public function GetAllNotesResourcesByProductID($ID)
{
	 $sql="SELECT * from class_notes WHERE CourseProductID=$ID ";
    $data=$this->_getRecords($this->conn,$sql);
    return $data;
}

public function GetClassRecordedResourcesByClassID($ID)
{
    $ID = intval($ID);
    $sql = "SELECT * FROM recorded_classes WHERE ID = $ID";
    $data = $this->_getSQLDetails($this->conn, $sql);

    if (!empty($data) && isset($data['Content'])) {
        $videoId = ltrim($data['Content'], '/');

        if (isset($data['Visiblity']) && strtolower($data['Visiblity']) === 'private') {
            $data['Content'] = "https://youtu.be/" . $videoId;
        } else {
            $data['Content'] = "https://youtube.com/embed/" . $videoId;
        }
    }

    return $data;
}



public function GetAllliveClass($class_id , $course_product_id)
{
	 $query = "SELECT `ID`, `CourseProductID`, `ClassID`, `Title`, `Visibility`, `StartDate`, `StartTime`, `Duration`, `Source`, `Studio`, `Scheduling`, `ZoomType`, `Content`, `ZoomID`, `ZoomPass` 
          FROM `live_calsses` 
          WHERE `ClassID` = '$class_id' AND `CourseProductID` = '$course_product_id' 
          ORDER BY StartDate DESC, StartTime DESC";
    $result = $this->_getRecords($this->conn, $query);
    return $result;
}

public function DeleteLiveClass($data)
{
	    $ID=$data['id'];
	     $where = " where ID = $ID";
		$response = $this->delete_identity_filter($this->conn,"live_calsses",$where);
		return $response;
}

public function GetAllRecordersClass($class_id , $course_product_id)
{
	$query = "SELECT *
          FROM `recorded_classes` 
          WHERE `ClassID` = '$class_id' AND `CourseProductID` = '$course_product_id' 
          ORDER BY ID DESC";
    $result = $this->_getRecords($this->conn, $query);
    return $result;
}

public function DeleteRecordedClass($data)
{
	      $ID=$data['id'];
	     $where = " where ID = $ID";
		$response = $this->delete_identity_filter($this->conn,"recorded_classes",$where);
		return $response;
}

 public function GetAllNotesClass($class_id , $course_product_id)
 {
 	$query = "SELECT *
          FROM `class_notes`
          WHERE `ClassID` = '$class_id' AND `CourseProductID` = '$course_product_id' 
          ORDER BY ID DESC";
    $result = $this->_getRecords($this->conn, $query);
    return $result;
 }

 public function DeleteNotesClass($data)
 {
 	      $ID=$data['id'];
	     $where = " where ID = $ID";
		$response = $this->delete_identity_filter($this->conn,"class_notes",$where);
		return $response;
 }

	

}