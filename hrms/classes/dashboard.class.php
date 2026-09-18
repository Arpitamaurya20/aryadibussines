<?php 
class Dashboard extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function GetAdminDashboardStats($center_filter)
	{
		$stats = array();
		$filter_branch = "";
		$filter_center = "";
		$FinalStatus="";
		if($center_filter != -1)
		{
			$filter_branch = " AND BranchID = $center_filter";
			$filter_center = " AND CenterID = $center_filter";
		}
		$filter =" where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_final_status = "";
		if($FinalStatus != "")
		{
			$filter_final_status = " AND Status != '$FinalStatus'";
		}
		$filter = " where IsActive = 1".$filter_branch.$filter_final_status;
		$stats['AllLead'] = $this->_getTotalRows($this->conn,'all_lead', $filter);

		$filter = " where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'telecaller_lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_telecaller_final_status = "";
		if($FinalStatus != "")
		{
			$filter_telecaller_final_status = " AND Status != '$FinalStatus'";
		}
		$filter = " where IsActive = 1".$filter_branch.$filter_telecaller_final_status;
		$stats['TotalTelecallerLeads'] = $this->_getTotalRows($this->conn,'telecaller_leads', $filter);

		$filter = " where AdmissionStatus = 'Approved'".$filter_branch;
		$stats['TotalAdmissions'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		$filter = " where AdmissionStatus = 'Pending By Account'".$filter_branch;
		$stats['PendingAccountApproval'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		if($center_filter == -1)
		{
			$filter = " where IsActive = 1";
			$stats['TotalRevenue'] = $this->_getTotalSUM($this->conn,'student_transaction','Amount', $filter);
		}
		else
		{
			$sql = "SELECT COALESCE(SUM(st.Amount),0) AS TotalAmount FROM student_transaction st JOIN student_admission sa ON st.AdmissionID = sa.ID WHERE sa.BranchID = $center_filter";
			$result = mysqli_query($this->conn, $sql);
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc();
				$stats['TotalRevenue'] = $row['TotalAmount'];
			} 
			else 
			{
				$stats['TotalRevenue'] = 0;
			}
		}

		$filter = " where 1";
		$stats['Payment_Links_generated'] = $this->_getTotalRows($this->conn,'student_fee', $filter);

		$filter = " where 1";
		$stats['TotalCurrentAffairs'] = $this->_getTotalRows($this->conn,'current_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMonthlyAffairs'] = $this->_getTotalRows($this->conn,'monthly_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMagazine'] = $this->_getTotalRows($this->conn,'magazine', $filter);

		$filter = " where Status = 'Placed'";
		$stats['TotalPlacedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Dispatched'";
		$stats['TotalDispatchedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Delivered'";
		$stats['TotalDeliveredOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where IsActive = 1".$filter_center;
		$stats['TotalEducator'] = $this->_getTotalRows($this->conn,'educator', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducatorQuestion'] = $this->_getTotalRows($this->conn,'educator_essay', $filter);

		$filter = " where IsActive = 1".$filter_center;
		$stats['TotalUsers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Counsellor' AND IsActive = 1 ".$filter_center;
		$stats['TotalCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Lead Counsellor' AND IsActive = 1 ".$filter_center;
		$stats['TotalLeadCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Acountant' AND IsActive = 1 ".$filter_center;
		$stats['TotalAccounts'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Batch Manager' AND IsActive = 1 ".$filter_center;
		$stats['TotalBatchManagers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'ContentTeam' AND IsActive = 1 ".$filter_center;
		$stats['TotalContentTeam'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Book Dispatch' AND IsActive = 1 ".$filter_center;
		$stats['TotalBookDispatchUser'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where 1".$filter_center;
		$stats['TotalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Main'".$filter_center;
		$stats['TotalMainCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Optional'".$filter_center;
		$stats['TotalOptionalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Module'".$filter_center;
		$stats['TotalModuleCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		/*$filter = " where Mode = 'Offline'".$filter_center;
		$stats['TotalOfflineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where Mode = 'Online'".$filter_center;
		$stats['TotalOnlineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);*/

		$filter = " where IsActive = 1";
		$stats['TotalCoursesBooks'] = $this->_getTotalRows($this->conn,'course_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesCodinator'] = $this->_getTotalRows($this->conn,'course_cordinator', $filter);

		return $stats;
	}
	public function GetAdmissionDashboardStats()
	{
		$stats = array();
		$filter = " where IsActive = 1";
		$stats['Enquiries'] = 0;

		$filter = " where PaymentStatus = 'Yes'";
		$stats['Payment_Links_successfull'] = 0;

		$filter = " where 1";
		$stats['Payment_Links_generated'] =0;

		$filter = " where Status = 'Placed'";
		$stats['TotalPlacedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Dispatched'";
		$stats['TotalDispatchedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Delivered'";
		$stats['TotalDeliveredOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducator'] = $this->_getTotalRows($this->conn,'educator', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalUsers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Counsellor'";
		$stats['TotalCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'ContentTeam'";
		$stats['TotalContentTeam'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Book Dispatch'";
		$stats['TotalBookDispatchUser'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where 1";
		$stats['TotalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Main'";
		$stats['TotalMainCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Optional'";
		$stats['TotalOptionalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Module'";
		$stats['TotalModuleCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where Mode = 'Offline'";
		$stats['TotalOfflineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where Mode = 'Online'";
		$stats['TotalOnlineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesBooks'] = $this->_getTotalRows($this->conn,'course_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesCodinator'] = $this->_getTotalRows($this->conn,'course_cordinator', $filter);

		return $stats;
	}
	public function GetCenterDashboardStats($CenterID)
	{
		$stats = array();
		$filter = " where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_final_status = "";
		if($FinalStatus != "")
		{
			$filter_final_status = " AND Status != '$FinalStatus'";
		}

		$filter = " where IsActive = 1 AND BranchID = $CenterID".$filter_final_status;
		$stats['AllLead'] = $this->_getTotalRows($this->conn,'all_lead', $filter);

		$filter = " where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'telecaller_lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_telecaller_final_status = "";
		if($FinalStatus != "")
		{
			$filter_telecaller_final_status = " AND Status != '$FinalStatus'";
		}
		$filter = " where IsActive = 1 AND BranchID = $CenterID".$filter_telecaller_final_status;
		$stats['TotalTelecallerLeads'] = $this->_getTotalRows($this->conn,'telecaller_leads', $filter);

		$filter = " where IsActive = 1 AND BranchID = $CenterID".$filter_final_status;
		$stats['AllLead'] = $this->_getTotalRows($this->conn,'all_lead', $filter);

		$filter = " where AdmissionStatus = 'Approved By Account' AND BranchID = $CenterID";
		$stats['TotalAdmissions'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		$filter = " where AdmissionStatus = 'Pending By Account' AND BranchID = $CenterID";
		$stats['PendingAccountApproval'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		$filter = " where PaymentStatus = 'Yes'";
		$stats['Payment_Links_successfull'] = $this->_getTotalRows($this->conn,'student_payment', $filter);

		$filter = " where 1";
		$stats['Payment_Links_generated'] = $this->_getTotalRows($this->conn,'student_fee', $filter);

		$filter = " where Status = 'Placed'";
		$stats['TotalPlacedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Dispatched'";
		$stats['TotalDispatchedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Delivered'";
		$stats['TotalDeliveredOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducator'] = $this->_getTotalRows($this->conn,'educator', $filter);

		$filter = " where IsActive = 1 and CenterID = $CenterID";
		$stats['TotalUsers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Lead Counsellor' and CenterID = $CenterID";
		$stats['TotalLeadCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Acountant' and CenterID = $CenterID";
		$stats['TotalAccounts'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Batch Manager' and CenterID = $CenterID";
		$stats['TotalBatchManagers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Counsellor' and CenterID = $CenterID";
		$stats['TotalCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'ContentTeam' and CenterID = $CenterID";
		$stats['TotalContentTeam'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Book Dispatch' and CenterID = $CenterID";
		$stats['TotalBookDispatchUser'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where 1 and CenterID = $CenterID";
		$stats['TotalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Main' and CenterID = $CenterID";
		$stats['TotalMainCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Optional' and CenterID = $CenterID";
		$stats['TotalOptionalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Module' and CenterID = $CenterID";
		$stats['TotalModuleCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where Mode = 'Offline'";
		$stats['TotalOfflineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where Mode = 'Online'";
		$stats['TotalOnlineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesBooks'] = $this->_getTotalRows($this->conn,'course_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesCodinator'] = $this->_getTotalRows($this->conn,'course_cordinator', $filter);

		return $stats;
	}
	public function GetCounsellorDashboardStats($User_ID)
	{
		$stats = array();
		$filter = " where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_final_status = "";
		if($FinalStatus != "")
		{
			$filter_final_status = " AND Status != '$FinalStatus'";
		}
		$filter = " where IsActive = 1 and AssignedTo = $User_ID".$filter_final_status;
		$stats['AllAssignedLead'] = $this->_getTotalRows($this->conn,'all_lead', $filter);
		
		// $filter = " where and PaymentStatus='Yes'";
		// $stats['Payment_Links_successfull'] = $this->_getTotalRows($this->conn,'student_payment', $filter);

		// $filter = " where 1";
		// $stats['Payment_Links_generated'] = $this->_getTotalRows($this->conn,'student_fee', $filter);

		return $stats;
	}

	public function GetContentTeamDashboardStats()
	{
		$stats = array();
		
		$filter = " where 1";
		$stats['TotalCurrentAffairs'] = $this->_getTotalRows($this->conn,'current_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMonthlyAffairs'] = $this->_getTotalRows($this->conn,'monthly_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMagazine'] = $this->_getTotalRows($this->conn,'magazine', $filter);

		return $stats;
	}

	public function GetAcountantDashboardStats($CenterID)
	{
		$stats = array();
		$filter_branch = "";
		$filter_center = "";

		if($CenterID != -1)
		{
			$filter_branch = " AND BranchID = $CenterID";
			$filter_center = " AND CenterID = $CenterID";
		}
		
		$filter = " where AdmissionStatus = 'Approved By Account'".$filter_branch;
		$stats['TotalStudentAdmisssion'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		$filter = " where AdmissionStatus = 'Pending By Account'".$filter_branch;
		$stats['TotalPendingAdmisssion'] = $this->_getTotalRows($this->conn,'student_admission', $filter);

		if($CenterID != -1)
		{
			$sql = "SELECT COUNT(*) AS TotalEnrollments FROM student_enrollment se JOIN student_admission sa ON se.AdmissionID = sa.ID WHERE sa.BranchID = $CenterID";
			$result = mysqli_query($this->conn, $sql);
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc();
				$stats['TotalEnrollment'] = $row['TotalEnrollments'];
			} 
			else 
			{
				$stats['TotalEnrollment'] = 0;
			}
		}
		else
		{
			$filter = " where 1";
			$stats['TotalEnrollment'] = $this->_getTotalRows($this->conn,'student_enrollment', $filter);
		}

		if($CenterID != -1)
		{
			$sql = "SELECT COUNT(*) AS TotalTransactions FROM student_transaction st JOIN student_admission sa ON st.AdmissionID = sa.ID WHERE sa.BranchID = $CenterID";
			$result = mysqli_query($this->conn, $sql);
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc();
				$stats['TotalTransaction'] = $row['TotalTransactions'];
			} 
			else 
			{
				$stats['TotalTransaction'] = 0;
			}
		}
		else
		{
			$filter = " where Refunded = 1";
			$stats['TotalTransaction'] = $this->_getTotalRows($this->conn,'student_enrollment', $filter);
		}


		if($CenterID != -1)
		{
			$sql = "SELECT COUNT(*) AS TotalEnrollments FROM student_enrollment se JOIN student_admission sa ON se.AdmissionID = sa.ID WHERE sa.BranchID = $CenterID AND se.Refunded = 1";
			$result = mysqli_query($this->conn, $sql);
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc();
				$stats['TotalRefunded'] = $row['TotalEnrollments'];
			} 
			else 
			{
				$stats['TotalRefunded'] = 0;
			}
		}
		else
		{
			$filter = " where Refunded = 1";
			$stats['TotalRefunded'] = $this->_getTotalRows($this->conn,'student_enrollment', $filter);
		}

		if($CenterID != -1)
		{
			$sql = "SELECT COUNT(*) AS TotalEnrollments FROM student_enrollment se JOIN student_admission sa ON se.AdmissionID = sa.ID WHERE sa.BranchID = $CenterID AND se.Dropped = 1";
			$result = mysqli_query($this->conn, $sql);
			if ($result->num_rows > 0) 
			{
				$row = $result->fetch_assoc();
				$stats['TotalDropped'] = $row['TotalEnrollments'];
			} 
			else 
			{
				$stats['TotalDropped'] = 0;
			}
		}
		else
		{
			$filter = " where Dropped = 1";
			$stats['TotalDropped'] = $this->_getTotalRows($this->conn,'student_enrollment', $filter);
		}

		return $stats;
	}

	public function GetMarketingDashboardStats()
	{
		$stats = array();
		$filter = " where IsActive = 1";
		$stats['Enquiries'] = $this->_getTotalRows($this->conn,'allcourse_form_payment', $filter);

		$filter = " where 1";
		$stats['MSLVCourse'] = $this->_getTotalRows($this->conn,'mslv_course', $filter);

		$filter = " where 1";
		$stats['MentorshipEnquiries'] = $this->_getTotalRows($this->conn,'mentorship_enquiry', $filter);

		$filter = " where 1";
		$stats['bookletdownloads'] = $this->_getTotalRows($this->conn,'booklet_form', $filter);

		$filter = " where 1";
		$stats['scholarship_enquiries'] = $this->_getTotalRows($this->conn,'scholarship_form', $filter);

		$filter = " where 1";
		$stats['TotalPhilosophyWebinar'] = $this->_getTotalRows($this->conn,'philosophy_webinar', $filter);

		$filter = " where PaymentStatus = 'Yes'";
		$stats['Payment_Links_successfull'] = $this->_getTotalRows($this->conn,'student_payment', $filter);

		$filter = " where 1";
		$stats['Payment_Links_generated'] = $this->_getTotalRows($this->conn,'student_fee', $filter);

		$filter = " where 1";
		$stats['TotalCurrentAffairs'] = $this->_getTotalRows($this->conn,'current_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMonthlyAffairs'] = $this->_getTotalRows($this->conn,'monthly_affairs', $filter);

		$filter = " where 1";
		$stats['TotalMagazine'] = $this->_getTotalRows($this->conn,'magazine', $filter);

		$filter = " where Status = 'Placed'";
		$stats['TotalPlacedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Dispatched'";
		$stats['TotalDispatchedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Delivered'";
		$stats['TotalDeliveredOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducator'] = $this->_getTotalRows($this->conn,'educator', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducatorQuestion'] = $this->_getTotalRows($this->conn,'educator_essay', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalUsers'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Counsellor'";
		$stats['TotalCounsellors'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'ContentTeam'";
		$stats['TotalContentTeam'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where UserType = 'Book Dispatch'";
		$stats['TotalBookDispatchUser'] = $this->_getTotalRows($this->conn,'users', $filter);

		$filter = " where 1";
		$stats['TotalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Main'";
		$stats['TotalMainCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Optional'";
		$stats['TotalOptionalCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where CourseType = 'Module'";
		$stats['TotalModuleCourses'] = $this->_getTotalRows($this->conn,'courses_for_display', $filter);

		$filter = " where Mode = 'Offline'";
		$stats['TotalOfflineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where Mode = 'Online'";
		$stats['TotalOnlineCourses'] = $this->_getTotalRows($this->conn,'courses_fee', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesBooks'] = $this->_getTotalRows($this->conn,'course_book', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalCoursesCodinator'] = $this->_getTotalRows($this->conn,'course_cordinator', $filter);

		return $stats;
	}

	public function GetBookDispatchDashboardStats()
	{
		$stats = array();
		
		$filter = " where Status = 'Placed'";
		$stats['TotalPlacedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Dispatched'";
		$stats['TotalDispatchedOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		$filter = " where Status = 'Delivered'";
		$stats['TotalDeliveredOrders'] = $this->_getTotalRows($this->conn,'student_book', $filter);

		return $stats;
	}

	public function GetEducatorDashboardStats()
	{
		$stats = array();
		
		$filter = " where IsActive = 1";
		$stats['TotalEducator'] = $this->_getTotalRows($this->conn,'educator', $filter);

		$filter = " where IsActive = 1";
		$stats['TotalEducatorQuestion'] = $this->_getTotalRows($this->conn,'educator_essay', $filter);

		return $stats;
	}
	public function GetLeadCounsellorDashboardStats($CenterID)
	{
		$stats = array();
		$filter = " where FinalStatus = 1";
		$response_final_status = $this->_getTableDetails($this->conn,'lead_status',$filter);
		if($response_final_status != NULL)
		{
			$FinalStatus = $response_final_status['Status'];
		}
		$filter_final_status = "";
		if($FinalStatus != "")
		{
			$filter_final_status = " AND Status != '$FinalStatus'";
		}
		$filter = " where BranchID = $CenterID and IsActive = 1".$filter_final_status;
		$stats['TotalLead'] = $this->_getTotalRows($this->conn,'all_lead', $filter);

		return $stats;
	}
	public function GetBatchManagerDashboardStats($CenterID)
	{
		$stats = array();
		
		$filter = " where IsActive = 1";
		$stats['TotalBatch'] = $this->_getTotalRows($this->conn,'course_batch', $filter);


		return $stats;
	}
	public function GetTelecallerDashboardStats($CenterID)
	{
		$stats = array();
		$center_filter = "";
		if($CenterID != -1)
		{
			$center_filter = " AND BranchID = $CenterID";
		}
		$filter = " where IsActive = 1".$center_filter;
		$stats['TotalTelecallerLeads'] = $this->_getTotalRows($this->conn,'telecaller_leads', $filter);
		return $stats;
	}
}
?>