<?php
@session_start();
include('../../includes/autoloader.inc.php');
$BranchID = $_POST['BranchID'];
$CorporateID = $_POST['CorporateID'];
$filter_date = $_POST['filter_date'];

$dbh = new Dbh();
$conn = $dbh->_connectodb();

$corporate_tickets_obj = new Corporateticket($conn);   
$corporate_status_array = $corporate_tickets_obj->getCorporateTicketStatusArray('All');

$filter = array();
$filter['branch'] = $BranchID;
$filter['filter_date'] = $filter_date;
$status_array = $corporate_tickets_obj->getTicketStatusArrayGorupedByTicketStatus($CorporateID,$filter);

?>
<div class="panel-content">
    <table class="table table-bordered m-0">
        <thead>
            <tr>
                <th>Status</th>
                <th>Count of Tickets</th>
            </tr>
        </thead>
        <tbody>
<?php
foreach($corporate_status_array as $corporate_status)
{
    $background_color = $corporate_status['Color'];
    $Status = $corporate_status['Status'];
    $count = 0;
    if(isset($status_array[$Status]))
    {
        $count = $status_array[$Status];
    }
    if($Status == "Raised")
    {
        $Status = "Open / Raised";
    }
   ?>
        <tr style="color: <?=$background_color;?>;">
            <td><?=$Status;?></td>
            <td><span class="badge ml-2 text-white fw-800 p-2" style="background:<?=$background_color;?> "><?=$count;?></span></td>
        </tr>
           
   <?php 
}
?>
     </tbody>
    </table>
</div>
