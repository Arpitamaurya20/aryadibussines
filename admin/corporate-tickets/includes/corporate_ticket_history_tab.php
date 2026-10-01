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
$core = new Core();
$where = " where TicketID = $ID ORDER BY CreatedDate DESC, CreatedTime DESC";
$ticket_history_array = $core->_getTableRecords($conn,'corporate_ticket_status_history',$where);

?>

<div class="panel-container show">
    <div class="panel-content p-0">
        <div class="row">
            <div class="col-md-12">
                <div class="timeline">
                    <?php 
                    if (!is_array($ticket_history_array)) {
                        $ticket_history_array = array();
                    }
                    foreach($ticket_history_array as $ticket_history)
                    {
                        $EmployeeName = "";
                        $assignedTo = isset($ticket_history['AssignedTo']) ? $ticket_history['AssignedTo'] : '';
                        if ($assignedTo !== '' && (int) $assignedTo > 0)
                        {
                            $assigned_employee = getEmployeeDetailsfromID($conn, (int) $assignedTo);
                            if (is_array($assigned_employee) && !empty($assigned_employee['Name'])) {
                                $EmployeeName = $assigned_employee['Name'];
                            }
                        }
                    ?>
                        <!-- Example timeline item -->
                        <div class="timeline-item">
                            <div class="status-card">
                                <span class="badge badge-primary"><?=$ticket_history['Status'];?></span>
                                <div class="status-content">
                                    <?php 
                                    if($EmployeeName != "")
                                    {
                                        ?>
                                        <p>Assigned To - <?php echo $EmployeeName; ?></p>
                                        <?php
                                    }
                                    ?>
                                    
                                    <p>Updated by <?=$ticket_history['CreatedBy']; ?> on <?php echo $ticket_history['CreatedDate']." ".$ticket_history['CreatedTime']; ?></p>
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