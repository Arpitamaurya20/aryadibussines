<?php
@session_start();
require_once('../../includes/autoloader.inc.php');
$data = $_POST;
$dbh = new Dbh();
$core = new Core();
$core->setTimeZone();
$conn = $dbh->_connectodb();
$project_obj = new Projects($conn);
$daily_progress_for_task_array = $project_obj->GetTasksDailyProgress($data);
$current_date = date('Y-m-d');
foreach($daily_progress_for_task_array as $daily_progress)
{
    extract($daily_progress);

    // Use Status as completion percentage (default 0 if not set)
    $completion = isset($Status) ? $Status : 0;

    $current_date_dt = new DateTime($current_date);
    $task_date_dt = new DateTime($TaskDate);
    $disabled = "";
    if($task_date_dt > $current_date_dt)
        $disabled = "disabled";
?>
    <div class="row mt-2 align-items-center">
        <div class="col-3">
            <label>Date <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="task_date[]" value="<?php echo $TaskDate; ?>" readonly />
        </div>

        <div class="col-3">
            <label>Completion (%) <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center">
                <input type="range" class="form-range" name="task_completion_<?php echo $TaskDate; ?>" id="task_completion_<?php echo $TaskDate; ?>" 
                       min="0" max="100" step="1" value="<?php echo $completion; ?>" <?php echo $disabled; ?> 
                       oninput="document.getElementById('percent_<?php echo $TaskDate; ?>').innerText = this.value + '%'">
                <span class="ms-2 fw-bold" id="percent_<?php echo $TaskDate; ?>"><?php echo $completion; ?>%</span>
            </div>
        </div>

        <!--  <div class="col-md-3">
			    <label>Evidence Images <span class="text-danger">*</span></label>

			    
			    <input type="file" class="form-control"
			           name="task_evidence_<?php echo $TaskDate; ?>[]" 
			           id="task_evidence_<?php echo $TaskDate; ?>"
			           accept="image/png, image/jpeg, image/jpg, image/webp"
			           multiple <?php echo $disabled; ?>
			           onchange="addMoreImages(this, 'preview_<?php echo $TaskDate; ?>')">

			    <small class="text-muted">You can select more images again to add more.</small>
			    
			  
			    <div id="preview_<?php echo $TaskDate; ?>" class="d-flex flex-wrap mt-2 gap-2"></div>
			</div> -->


        <div class="col-3">
            <label>Remarks <span class="text-danger">*</span></label>
            <textarea class="form-control" name="task_remarks_<?php echo $TaskDate; ?>" placeholder="Remarks" <?php echo $disabled; ?>><?php echo $Remarks; ?></textarea>
        </div>
    </div>
<?php
}
?>
