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
$core = new Core();
$core->setTimeZone();
$CompanyName = "All";
if($CorporateID != -1)
{
    $where = " where ID = $CorporateID";
    $CompanyName = $core->_getTableDetails($conn,'company',$where)['CompanyName'];
}
if(isset($_POST['sql_in_state_string']))
{
    $filter['sql_in_state_string'] = $_POST['sql_in_state_string'];
}
if(isset($_POST['sql_in_branch_account_string']))
{
    $filter['sql_in_branch_account_string'] = $_POST['sql_in_branch_account_string'];
}
$corporate_tickets_obj = new Corporateticket($conn);   
$filter['state'] = $state_filter;
$filter['region'] = $region_filter;
$filter['ticket_type'] = $ticket_type_filter;
$filter['ticket_status'] = $ticket_status_filter;
$filter['filter_date'] = $filter_date;
$corporate_tickets_count = $corporate_tickets_obj->GetCorporateTicketsCountByFilter($CorporateID,$filter);
?>
<div class="panel-hdr">
    <div class="col-lg-6 col-xl-6">
        <span><?=$CompanyName;?></span>
    </div>
    <div class="col-lg-6 col-xl-6">
        <span class="d-inline-block badge badge-info text-center p-1"> Count of Tickets (excluding PPM) - <?=$corporate_tickets_count;?> </span>
    </div>
</div>