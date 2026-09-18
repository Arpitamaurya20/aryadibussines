<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);
$response = $corporate_tickets_obj->GetTopStatesRevenue();
$states = array();
$states_techxpert_cost = array();
$states_customer_cost = array();
$response_data = array();
foreach($response as $i_response)
{
	extract($i_response);
	array_push($states,$BranchState);
	array_push($states_techxpert_cost,$TotalTechXpertPrice);
	array_push($states_customer_cost,$TotalCustomerPrice);
}
$response_data['states'] = $states;
$response_data['states_techxpert_cost'] = $states_techxpert_cost;
$response_data['states_customer_cost'] = $states_customer_cost;
echo json_encode($response_data);
?>