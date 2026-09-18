<?php
require_once('common_api_header.php');
require_once('../admin/includes/autoloader.inc.php');
$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw,true);
$response = array();
if(isset($data['EmployeeID']))
{
	$dbh = new Dbh();
	$core = new Core();
	$conn = $dbh->_connectodb();
	if(isset($data['EmployeeID']))
	{
	    $EmployeeID = $data['EmployeeID'];
	    if($EmployeeID!=-1)
	    {
	        // Get cities mapped to Employees
	        $company = new Company($conn);
	        $company_array_mapped_raw = $company->getMappedAccountsofAccountManager($EmployeeID);
	        $company_array_mapped = array();
	        foreach($company_array_mapped_raw as $company_mapped)
	        {
	            array_push($company_array_mapped,$company_mapped['ID']);
	        }
	        $sql_in_account = "'" . implode("', '", $company_array_mapped) . "'";
	        $where = " where CorporateID IN (".$sql_in_account.") ORDER BY ID DESC";
	        $tickets_array = $core->_getTableRecords($conn,'corporate_tickets', $where);
	        $response['data'] = $tickets_array;
	        $response['error'] = false;
			$response['message'] = "Tickets fetched";
	    }
	}
}
else
{
	$response["error"] = true;
	$response["message"] = "Missing User Fields";
}
echo json_encode($response);
?>