<?php 
if(isset($_POST))
{
	require_once('../../includes/autoloader.inc.php');
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$employee_obj = new Employee($conn);
	$data = $_POST;
	$attendance_records = $employee_obj->getEmployeeAttendanceRecords($data);
	
	foreach($attendance_records as $record)
	{
		extract($record);
		$duration = $employee_obj->calculatetimeDifference($InTime,$OutTime);
		$InTime_html = $InTime;
		if($CheckinImage != "")
		{
			$InTime_html = $InTime_html." "."<a onclick='ViewAttendanceImage(\"".$CheckinImage."\")'><i class='fal fa-eye'></i></a>";
		}
		$OutTime_html = $OutTime;
		if($CheckoutImage != "")
		{
			$OutTime_html = $OutTime_html." "."<a onclick='ViewAttendanceImage(\"".$CheckoutImage."\")'><i class='fal fa-eye'></i></a>";
		}
		?>
		<tr>
	        <td><?=$RecordDate;?></td>
	        <td><?=$InTime_html;?></td>
	        <td><?=$OutTime_html;?></td>
	        <td><?=$duration;?></td>
	    </tr>
		<?php
	}
}

?>