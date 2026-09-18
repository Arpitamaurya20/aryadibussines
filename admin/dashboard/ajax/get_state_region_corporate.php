<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
include('../../includes/autoloader.inc.php');
include('../controller/dashboard_controller.php');
$CorporateID = $_POST['CorporateID'];
$state_filter = $_POST['state_filter'];
$region_filter = $_POST['region_filter'];
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$region = new Region($conn);
if($CorporateID != -1)
{
    $where = " where CompanyID = $CorporateID";
}
else
{
    $where = " where 1";
}
$where_state = "";
if(isset($_POST['sql_in_state_string']))
{
    if($_POST['sql_in_state_string'] != "")
    {
        $filter['sql_in_state_string'] = $_POST['sql_in_state_string'];
        $where_state = " AND BranchState IN (".$filter['sql_in_state_string'].")";
    }
}
if(isset($_POST['sql_in_branch_account_string']))
{
    if($_POST['sql_in_branch_account_string'] != "")
    {
        $filter['sql_in_branch_account_string'] = $_POST['sql_in_branch_account_string'];
        $where_state = " AND ID IN (".$filter['sql_in_branch_account_string'].")";
    }
}
$filter['state'] = $state_filter;
$filter['region'] = $region_filter;
$analyticsBranchID = applyAnalyticsBranchFilter($filter);
if($analyticsBranchID != -1)
{
    $where = $where." AND ID = ".$analyticsBranchID;
}

if($region_filter != "")
{
    $where_region_id = " where RegionName = '".$filter['region']."'";
    $RegionID = $core->_getTableDetails($conn,'region',$where_region_id)['ID'];
    $where = $where." AND BranchState IN (Select StateName from state where RegionID = $RegionID)";
}
$where = $where.$where_state;
$state_array = $core->_getDistinctTableRecords($conn,'branch','BranchState',$where);
?>

    
        

    <select class="form-control" name="ticket_state_filter" id="ticket_state_filter" onchange="GenerateBranchAnalytics(<?=$CorporateID;?>)">
            <option value="">Select State</option>
            <?php

            $state_name = array();
            foreach ($state_array as $state) 
            {
                if($state_filter == "")
                {
                    array_push($state_name,$state['BranchState']);
                }
                else
                {
                    if($state_filter == $state['BranchState'])
                    {
                        array_push($state_name,$state['BranchState']);
                    }
                }
                ?>
                   <option value="<?=$state['BranchState']; ?>"><?=$state['BranchState']; ?></option>
                <?php
            }
            ?>
    </select>

<?php
$commaSeparatedState = implode("','", $state_name);
$region_array = $region->GetDistinctRegionByStateNames($commaSeparatedState);
?>


<!-- Regions -->


<?php
foreach($region_array as $region)
{
    ?>
    <!--div class="col-xl-12 col-lg-12">
        <span class="badge badge-danger"><?=$region['RegionName'];?></span>
    </div-->
    <?php
}
?>

    <select class="form-control mt-2" name="ticket_region_filter" id="ticket_region_filter" onchange="GenerateBranchAnalytics(<?=$CorporateID;?>)">
            <option value="">Select Region</option>
            <?php
            $selected = "";
            if($state_filter != "")
            {
                $selected = "selected";
            }
            foreach ($region_array as $region) 
            {
                ?>
                   <option value="<?=$region['RegionName']; ?>" <?=$selected;?>><?=$region['RegionName']; ?></option>
                <?php
            }
            ?>
    </select>

          

