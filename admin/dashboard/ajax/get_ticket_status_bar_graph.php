<?php
@session_start();
include('../../includes/autoloader.inc.php');
include('../controller/dashboard_controller.php');
$CorporateID = $_POST['CorporateID'];
$state_filter = $_POST['state_filter'];
$region_filter = $_POST['region_filter'];
$ticket_type_filter = $_POST['ticket_type_filter'];
$ticket_status_filter = $_POST['ticket_status_filter'];
$filter_date = $_POST['filter_date'];
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$corporate_tickets_obj = new Corporateticket($conn);   
$filter['state'] = $state_filter;
$filter['region'] = $region_filter;
$filter['ticket_type'] = $ticket_type_filter;
$filter['ticket_status'] = $ticket_status_filter;
$filter['filter_date'] = $filter_date;
if(isset($_POST['sql_in_state_string']))
{
    $filter['sql_in_state_string'] = $_POST['sql_in_state_string'];
}
if(isset($_POST['sql_in_branch_account_string']))
{
    $filter['sql_in_branch_account_string'] = $_POST['sql_in_branch_account_string'];
}
applyAnalyticsBranchFilter($filter);
$corporate_tickets_status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);
$response = array();
$data = array();
$data_labels = array();
foreach ($corporate_tickets_status_array as $status => $status_count) {
  array_push($data,$status_count);
  array_push($data_labels,$status);
}
$response['chart_data']['data'] = $data;
$response['chart_data']['data_labels'] = $data_labels;
echo json_encode($response);
?>