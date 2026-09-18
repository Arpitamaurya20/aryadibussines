<?php 
require_once('../../includes/autoloader.inc.php');
$response = array();
if(isset($_POST))
{
	$QuotationLineItemID = $_POST['QuotationLineItemID'];
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$core = new Core();
	$query = " where ID = $QuotationLineItemID";
	$response_boolean = $core->delete_identity_filter($conn,'corporate_ticket_quotation_items', $query);
	if($response_boolean)
	{
		$response['error'] = false;
		$response['message'] = "Line Item Deleted";
	}
	else
	{
		$response['error'] = true;
		$response['message'] = "A Technical problem occured!";
	}
}
else
{
	$response['error'] = true;
	$response['message'] = "A Technical problem occured!";
}
echo json_encode($response);
?>