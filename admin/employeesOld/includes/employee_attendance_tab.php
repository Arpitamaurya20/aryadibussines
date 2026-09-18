<?php 
    $currentYear = date('Y');
    $currentMonth = date('m');
    $dbh = new Dbh();
	$conn = $dbh->_connectodb();

    $filter = " WHERE `year`= '".$currentYear."' AND `month` = '".$currentMonth."'";
    $sql_calender = "SELECT `date`, `is_weekend` FROM `calendar`".$filter;
    $result_calender = mysqli_query($conn, $sql_calender);

    // Fetch all attendance records for this employee and month in one go
    
    $sql_attendance = "SELECT * FROM `employee_attendance` WHERE EmployeeID = '".$ID."' AND YEAR(RecordDate) = '$currentYear' AND MONTH(RecordDate) = '$currentMonth'";
    $result_attendance = mysqli_query($conn, $sql_attendance);

    // Store attendance in array with date as key
    $attendance_data = [];
    while($row = mysqli_fetch_assoc($result_attendance)) {
        $attendance_data[$row['RecordDate']] = $row;
    }
?>
<div class="row">
    <div class="col-sm-12">
        <div class="card mb-g"> 
            <div class="card-body">
                <h5 class="frame-heading">
                    Select Duration
                </h5>
                <div class="frame-wrap bg-faded mb-5">
                    <div class="row">
                       <div class="col-3">
                            <select class="form-control" id="attendance_select_year" onchange="GenerateEmployeeAttendanceDetails(<?php echo $ID;?>)">
                                <option value="2024" <?php echo ($currentYear == '2024') ? 'selected' : ''; ?>>2024</option>
                                <option value="2025" <?php echo ($currentYear == '2025') ? 'selected' : ''; ?>>2025</option>
                            </select>
                        </div>

                        <div class="col-3">
                            <select class="form-control" id="attendance_select_month" onchange="GenerateEmployeeAttendanceDetails(<?php echo $ID;?>)">
                                <option value="">Select Month</option>
                                <?php
                                for ($month = 1; $month <= 12; $month++) {
                                    $monthValue = str_pad($month, 2, '0', STR_PAD_LEFT); // Pads single digits with a leading zero
                                    $selected = ($monthValue == $currentMonth) ? 'selected' : '';
                                    echo "<option value=\"$monthValue\" $selected>" . date('F', mktime(0, 0, 0, $month, 1)) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <h5 class="frame-heading p-0 bg-white mb-g">
                    Generated data:
                </h5>
                <div class="frame-wrap p-0 border-0 m-0">
                    <table class="table m-0 table-sm table-bordered" id="table-example" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>TWH</th>
                                <th>OT</th>
                                <th>ST</th>
                                <th>STATUS</th>
                                
                            </tr>
                        </thead>
                        <tbody id="employee_attendance_table">
                            <?php
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
                                    <td style="text-align:center !important;">OT</td>
                                    <td style="text-align:center !important;">ST</td>
                                    <td style="text-align:center !important;">STATUS</td>
                                </tr>
                            <?php 
                                } 
                            ?>
                        </tbody>
                    </table>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
      </div>
      <div class="modal-body d-flex justify-content-center">
        <!-- Display Image with Fixed Size -->
        <img id="modalImage" alt="Preview" class="modal-image">
      </div>
    </div>
  </div>
</div>
