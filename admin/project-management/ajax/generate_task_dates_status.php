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
	$checked = "checked";
	if($Status == 0)
	{
		$checked = "";
	}
	$current_date_dt = new DateTime($current_date);
	$task_date_dt = new DateTime($TaskDate);
	$disabled = "";
	if($task_date_dt > $current_date_dt)
		$disabled = "disabled";
?>
	<div class="row mt-2">
	    <div class="col-3 ">
	        <label> Date <span class="text-danger">*</span></label>
	        <input type="text" class="form-control" name="task_date[]" value="<?php echo $TaskDate; ?>" readonly="true" />
	    </div>
	    <div class="col-3 ">
	        <div class="custom-control custom-checkbox custom-control-inline mt-4">
	            <input type="checkbox" class="custom-control-input" name="task_completion_date_<?php echo $TaskDate;?>" id="task_date_<?php echo $TaskDate;?>" <?php echo $checked; ?> <?php echo $disabled;?>>
	            <label class="custom-control-label" for="task_date_<?php echo $TaskDate;?>">Task Completed</label>
	        </div>
	    </div>
	    <div class="col-5">
	        <label>Remarks <span class="text-danger">*</span> </label>
	        <textarea class="form-control" name="task_remarks_<?php echo $TaskDate;?>" placeholder="Remarks" <?php echo $disabled; ?>><?php echo $Remarks; ?></textarea>
	    </div>
	</div>
<?php
}
?>	