<?php
@session_start();
include('../../includes/autoloader.inc.php');
$CorporateID = $_POST['CorporateID'];
$state_filter = $_POST['state_filter'];
$region_filter = $_POST['region_filter'];
$ticket_type_filter = $_POST['ticket_type_filter'];
$ticket_status_filter = $_POST['ticket_status_filter'];
$filter_date = $_POST['filter_date'];
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$region = new Region($conn);  
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
$corporate_tickets_region_status_array = $region->getTicketStatusArrayGroupedByRegion($CorporateID,$filter);
$response = array();
$data = array();
$data_labels = array();
$data_bg = array();
$region_bg = array(
    'SOUTH' => '#FF5733',
    'EAST' => '#33FF57',
    'WEST' => '#5733FF',
    'NORTH' => '#FFFF33',
    'NESA' => '#33FFFF',
    'MPCG' => '#FF33FF',
    'WEST CENTRAL' => '#FF33FF',
);
foreach ($corporate_tickets_region_status_array as $i_region => $status_count) {
  array_push($data,$status_count);
  array_push($data_labels,$i_region);
  array_push($data_bg,$region_bg[$i_region]);

}
$response['chart_data']['data'] = $data;
$response['chart_data']['data_labels'] = $data_labels;
$response['chart_data']['data_bg'] = $data_bg;
echo json_encode($response);
?>