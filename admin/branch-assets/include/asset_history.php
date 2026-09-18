<style type="text/css">
.timeline {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.timeline-item {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    position: relative;
}

.status-card {
    width: 40%;
    text-align: center;
    background-color: #f0f0f0;
    border: 1px solid #ccc;
    border-radius: 5px;
    padding: 10px;
    margin-bottom: 20px;
    position: relative;
    z-index: 1; /* Set z-index for the status cards */
}

.status-content {
    margin-top: 10px;
}

.line {
    position: absolute;
    width: 2px;
    height: calc(100% + 20px); /* Adjust the height to cover the space between cards */
    background-color: #000; /* Black color for the line */
    left: 50%;
    transform: translateX(-50%);
    z-index: 0; /* Set z-index for the connecting line */
}
</style>
<?php 

$where = " where BranchAssetID = $EquipmentID ORDER BY PPMDate DESC, ID DESC ";
$ppm_tickets = $core->_getTableRecords($conn,'ppm_tickets',$where);

?>
<div class="alert alert-primary">
    <div class="d-table w-100">
        <div class="d-table-cell align-top width-6">
            <span class="icon-stack icon-stack-lg">
                <i class="base base-6 icon-stack-3x opacity-100 color-primary-500"></i>
                <i class="base base-10 icon-stack-2x opacity-100 color-primary-300 fa-flip-vertical"></i>
                <i class="fal fa-info icon-stack-1x opacity-100 color-white"></i>
            </span>
        </div>
        <div class="d-table-cell pl-1">
            <span class="h5">PPM Tickets History</span>
            <br> Full History of PPM Tickets executed on the Asset, order by PPM Date
        </div>
    </div>
</div>
<div class="panel-container show">
    <div class="panel-content p-0">
        <div class="row">
            <div class="col-md-12">
                <div class="timeline">
                    <?php 
                    foreach($ppm_tickets as $ppm_ticket)
                    {
                        $t_ID = $ppm_ticket['ID'];
                        $TicketID = $ppm_ticket['TicketID'];
                        $TicketID_html = cleantext($TicketID)."&nbsp;<a onclick='ba_ViewPPMTicketDetails($t_ID)'><i class='fal fa-external-link'></i></a>";
                        $PPMDate = $ppm_ticket['PPMDate'];
                        $DueDate = $core->getValueorNotSet($ppm_ticket['DueDate']);
                        $CloseDate = $ppm_ticket['CloseDate'];
                        $CloseTime = $ppm_ticket['CloseTime'];
                        $Status = $ppm_ticket['Status'];
                        
                    ?>
                        <!-- Example timeline item -->
                        <div class="timeline-item">
                            <div class="status-card">
                                <span class="badge badge-primary"><?=$Status;?></span>
                                <div class="status-content">
                                    <p>Ticket ID - <?php echo $TicketID_html; ?></p>
                                    <p>PPM Date - <?php echo $PPMDate; ?></p>
                                    <p>Due Date - <?php echo $DueDate; ?></p>
                                    <?php 
                                    if($CloseDate != "")
                                    {
                                    ?>
                                        <p>Ticket Close Information <?=$CloseDate; ?> on <?php echo $CloseTime; ?></p>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <!-- Add more timeline items as needed -->
                    <?php 
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php 

$where = " where BranchAssetID = $EquipmentID ORDER BY CreatedDate DESC, ID DESC ";
$amc_tickets = $core->_getTableRecords($conn,'corporate_tickets',$where);

?>
<div class="alert alert-primary">
    <div class="d-table w-100">
        <div class="d-table-cell align-top width-6">
            <span class="icon-stack icon-stack-lg">
                <i class="base base-6 icon-stack-3x opacity-100 color-primary-500"></i>
                <i class="base base-10 icon-stack-2x opacity-100 color-primary-300 fa-flip-vertical"></i>
                <i class="fal fa-info icon-stack-1x opacity-100 color-white"></i>
            </span>
        </div>
        <div class="d-table-cell pl-1">
            <span class="h5">AMC Breakdown History</span>
            <br> Full History of AMC Breakdown Tickets executed on the Asset, order by Raised Date
        </div>
    </div>
</div>
<?php 
if(sizeof($amc_tickets) > 0)
{
    ?>

<div class="panel-container show">
    <div class="panel-content p-0">
        <div class="row">
            <div class="col-md-12">
                <div class="timeline">
                    <?php 
                    foreach($amc_tickets as $amc_ticket)
                    {
                        $t_ID = $amc_ticket['ID'];
                        $TicketID = $amc_ticket['TicketID'];
                        $TicketID_html = cleantext($TicketID)."&nbsp;<a onclick='ba_ViewTicketDetails($t_ID)'><i class='fal fa-external-link'></i></a>";
                        $CreatedDate = $amc_ticket['CreatedDate'];
                        $DueDate = $core->getValueorNotSet($amc_ticket['DueDate']);
                        $CloseDate = $amc_ticket['CloseDate'];
                        $CloseTime = $amc_ticket['CloseTime'];
                        $Status = $amc_ticket['Status'];
                        
                    ?>
                        <!-- Example timeline item -->
                        <div class="timeline-item">
                            <div class="status-card">
                                <span class="badge badge-primary"><?=$Status;?></span>
                                <div class="status-content">
                                    <p>Ticket ID - <?php echo $TicketID_html; ?></p>
                                    <p>Raised Date - <?php echo $CreatedDate; ?></p>
                                    <p>Due Date - <?php echo $DueDate; ?></p>
                                    <?php 
                                    if($CloseDate != "")
                                    {
                                    ?>
                                        <p>Ticket Close Information <?=$CloseDate; ?> on <?php echo $CloseTime; ?></p>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <!-- Add more timeline items as needed -->
                    <?php 
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
}
else
{
    ?>
    <h5> No AMC Tickets Executed on this Asset</h5>
    <?php
}
?>