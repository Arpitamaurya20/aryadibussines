<?php 
if(isset($_POST))
{
	require_once('../../includes/autoloader.inc.php');
	$dbh = new Dbh();
	$conn = $dbh->_connectodb();
	$employee_obj = new Employee($conn);
	$data = $_POST;
	//$attendance_records = $employee_obj->getEmployeeAttendanceRecords($data);
	$currentYear = $data['s_year'];
	$currentMonth = $data['s_month'];
	$ID = $data['EmployeeID'];
	
	$filter = " WHERE `year`= '".$currentYear."' AND `month` = '".$currentMonth."'";
    $sql_calender = "SELECT `date`, `is_weekend` FROM `calendar`".$filter;
    $result_calender = mysqli_query($conn, $sql_calender);

	$sql_attendance = "SELECT * FROM `employee_attendance` WHERE EmployeeID = '".$ID."' AND YEAR(RecordDate) = '$currentYear' AND MONTH(RecordDate) = '$currentMonth'";
    $result_attendance = mysqli_query($conn, $sql_attendance);

	// Store attendance in array with date as key
    $attendance_data = [];
    while($row = mysqli_fetch_assoc($result_attendance)) {
        $attendance_data[$row['RecordDate']] = $row;
    }
	
	// print_r($result_attendance);
	// exit();

	foreach($result_calender as $calender)
	{

		$RecordDate = $calender['date'];
		$record = isset($attendance_data[$RecordDate]) ? $attendance_data[$RecordDate] : null;
		
		$InTime_html = $record['InTime'] ?? '';
		$CheckinImage = $record['CheckinImage'] ?? '';
		$OutTime_html = $record['OutTime'] ?? '';
		$CheckoutImage = $record['CheckoutImage'] ?? '';
		if($CheckinImage != "")
		{
			$InTime_html = $InTime_html." "."<a onclick='ViewAttendanceImage(\"".$CheckinImage."\")'><i class='fal fa-eye'></i></a>";
		}
		if($CheckoutImage != "")
		{
			$OutTime_html = $OutTime_html." "."<a onclick='ViewAttendanceImage(\"".$CheckoutImage."\")'><i class='fal fa-eye'></i></a>";
		}
		?>
		<tr>
	        <td style='background:#184384; color:#fff; text-align:center !important;'><?php echo $RecordDate; ?></td>
	        <td style="text-align:center !important;"><?php echo $record ?  $InTime_html : '-'; ?></td>
	        <td style="text-align:center !important;"><?php echo $record ?  $OutTime_html : '-'; ?></td>
			<td style="text-align:center !important;"><?php echo "TWH"; ?></td>
			<td style="text-align:center !important;"><?php echo "OT"; ?></td>
			<td style="text-align:center !important;"><?php echo "ST"; ?></td>
			<td style="text-align:center !important;"><?php echo "STATUS"; ?></td>
	    </tr>
		<?php
	}
}

?>