<?php 
class Invoice extends Core
{
	private $conn;
	public function __construct($conn)
	{
		$this->conn = $conn;
		$this->setTimeZone();
	}

	function InsertInvoiceForm($data)
	{
		$Invoice_no = $data['invoice_no'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');

		$sql = "INSERT INTO invoice_no(InvoiceNo,CreatedDate,CreatedTime,CreatedBy) VALUES ('$Invoice_no','$Mode','$CreatedDate','$CreatedTime','$CreatedBy')";
		$response_insert_book_details = $this->_InsertTableRecords($this->conn,$sql);

		$response_insert_invoice_details['error'] = false;
		$response_insert_invoice_details['message'] = "Inoice No. is Successfully Added.";
		
		return $response_insert_invoice_details;
		

	}


	function UpdateInvoiceForm($data)
	{	
		$Invoice_no = $data['invoice_no'];
		$CreatedDate = date('Y-m-d');
		$CreatedBy = $data['CreatedBy'];
		$CreatedTime = date('H:i:s');
		$invoice_id = $data['form_id'];
		

	    $old_invoice_details = $this->GetInvoiceDetailsbyID($invoice_id);
	    if($Invoice_no == $old_invoice_details['InvoiceNo'] )
	    {
	    	$response['message'] = "No changes to update";
	    	$response['error'] = true;
	    }
	    else
	    {
	    	$update_param = " InvoiceNo = '$Invoice_no' where ID=$invoice_id";
	    	$response = $this->_UpdateTableRecords($this->conn,'invoice_no', $update_param);
	    	$response['error'] = false;
			$response['message'] = "Invoice No. Update";
	    	
	    }

	    return $response;
	}

	public function GetAllInvoice($conn)
	{
		$where = " where IsActive = 1 ORDER BY ID DESC";
        $invoice_no_details = $this->_getTableRecords($conn, "invoice_no", $where);
        return $invoice_no_details;
	}

	public function getTotalInvoice($table)
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

	function DeleteInvoice($data)
	{
		$InvoiceID = $data['ID'];
		// Delete course
		$where = " where ID = $InvoiceID";
		$response = $this->delete_identity_filter($this->conn,"invoice_no",$where);
		return $response;
	}

    function GetInvoiceDetailsbyID($ID)
	{
		$where = " where ID = $ID";
		$invoice_details = $this->_getTableDetails($this->conn,'invoice_no', $where);
		return $invoice_details;
	}

}