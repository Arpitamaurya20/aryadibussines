<?php 
require_once('../../includes/autoloader.inc.php');
$dbh = new Dbh();
$conn = $dbh->_connectodb();
$core = new Core();
$core->setTimeZone();
$current_date = date("Y-m-d");
$total_employees = $core->_getTotalRows($conn, 'employees', 'where IsActive = 1 and Vendor = 0');
$where = " where RecordDate = '$current_date'";
$total_present = $core->_getTotalRows($conn, 'employee_attendance',$where);
?>
<div class="col-sm-3 col-xl-6">
</div>

<div class="col-sm-6 col-xl-3">
    <div class="p-3 border-0 rounded overflow-hidden position-relative mb-g shadow-sm" style="background: linear-gradient(135deg, #003f88 0%, #0056b3 100%);">
        <div class="position-relative z-index-1">
            <h3 class="display-4 d-block l-h-n m-0 fw-500 text-white">
                <?php echo $total_employees;?>
                <small class="m-0 l-h-n text-white-50" style="font-size: 14px; font-weight: 600; display: block; margin-top: 5px !important;">Total Employees</small>
            </h3>
        </div>
        <i class="fal fa-users position-absolute pos-right pos-bottom mb-n1 mr-n1" style="font-size:5rem; opacity: 0.15; color: #ffffff; z-index: 0;"></i>
    </div>
</div>
<div class="col-sm-6 col-xl-3">
    <div class="p-3 border-0 rounded overflow-hidden position-relative mb-g shadow-sm" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
        <div class="position-relative z-index-1">
            <h3 class="display-4 d-block l-h-n m-0 fw-500 text-white">
                <?php echo $total_present;?>
                <small class="m-0 l-h-n text-white-50" style="font-size: 14px; font-weight: 600; display: block; margin-top: 5px !important;">Total Present</small>
            </h3>
        </div>
        <i class="fal fa-user-check position-absolute pos-right pos-bottom mb-n1 mr-n4" style="font-size: 5rem; opacity: 0.15; color: #ffffff; z-index: 0;"></i>
    </div>
</div>