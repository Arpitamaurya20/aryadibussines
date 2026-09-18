<?php 
class Payment extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	public function checkduplicatePayment($conn,$data)
	{  
		$CourseID=$data['order_id'];
		$Email=$data['billing_email'];
		$MobileNumber=$data['billing_tel'];
		$where = "where CourseID=$CourseID And (Email='$Email')";
        $status = $this->check_unique_identity_filter($conn, "payment_records", $where);
        return $status;
	}

	public function GetAllPayment($conn)
	{
		$where = " where 1 ORDER BY ID DESC";
        $booklet_Details = $this->_getTableRecords($conn, "student_payment", $where);
        return $booklet_Details;
	}

	public function GetPaymentSuccessCheck($conn,$ID)
	{
		$where = " where FeeID = $ID and PaymentStatus = 'YES'";
        $payment_check = $this->check_unique_identity_filter($conn, "student_payment", $where);
        return $payment_check;
	}
	public function getTotalPayment($table)
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
	public function GetOrderDetailsByID($ID)
	{
		$where = " where ID = $ID";
		$order_details = $this->_getTableDetails($this->conn, "student_temp_enrollement", $where);
		return $order_details;
	}
}
?>