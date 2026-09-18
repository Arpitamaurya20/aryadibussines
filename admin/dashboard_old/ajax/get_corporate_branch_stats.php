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
$branch = new Branch($conn);
$filter = array();
if(isset($_POST['sql_in_state_string']))
{
    $filter['sql_in_state_string'] = $_POST['sql_in_state_string'];
}
if(isset($_POST['sql_in_branch_account_string']))
{
    $filter['sql_in_branch_account_string'] = $_POST['sql_in_branch_account_string'];
}
$branch_array = $branch->setBranchArrayByCorporateIDv2($CorporateID,'All',$filter);
$corporate_tickets_obj = new Corporateticket($conn);   
$filter['state'] = $state_filter;
$filter['region'] = $region_filter;
$filter['ticket_type'] = $ticket_type_filter;
$filter['ticket_status'] = $ticket_status_filter;
$filter['filter_date'] = $filter_date;
$branch_corporate_tickets = $corporate_tickets_obj->GetCorporateTicketsGroupedByBranchID($CorporateID,$filter);
?>
<div class="panel-hdr">
    <h2>
       Data Date - <?= date('Y-m-d'); ?>
    </h2>
    <span class="badge badge-primary cursor-pointer" onclick="BranchWiseTicketsModal()">Branch Wise Tickets</span>
</div>

    <div class="subheader">  
        <select class="select2 form-control w-100" id="ad_branch_name" name="ad_branch_name" onchange="AD_RefreshBranchAnalytics(<?=$CorporateID;?>,this.value)" style="width:100%;">
            <option value="-1">Select Branch</option>
            <?php
            foreach($branch_array as $branch_id=>$branch)
            {
            ?>
                <option value="<?php echo $branch_id;?>">
                    <?php echo $branch['BranchName'];?>
                </option>
            <?php
            }
            ?>
        </select>
    </div>
    <div class="panel-container show mt-3" id="branch_wise_status_div">
        
    </div>

<!-- Modal Structure -->
<div class="modal fade" id="branchTicketsModal" tabindex="-1" role="dialog" aria-labelledby="branchModalLabel" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="branchModalLabel">Branch Wise Tickets</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
               
                <div class="panel-container show mt-3">
                    <div class="panel-content">
                        <table class="table table-bordered m-0">
                            <thead>
                                <tr>
                                    <th>Branch Name</th>
                                    <th>Count of Tickets</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach($branch_corporate_tickets as $branch_corporate_ticket)
                                {
                                    $BranchID = $branch_corporate_ticket['BranchID'];
                                    if(isset($branch_array[$BranchID]))
                                    {
                                        $BranchName = $branch_array[$BranchID]['BranchName'];
                                        ?>
                                        <tr>
                                            <th scope="row"><?php echo $BranchName; ?></th>
                                            <td><?= $branch_corporate_ticket['ticket_counts']; ?></td>
                                        </tr>
                                        <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>