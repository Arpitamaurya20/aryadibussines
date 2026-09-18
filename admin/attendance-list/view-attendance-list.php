<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
		include('../controllers/common_controllers.php');
		include('controller/attendance_controller.php');
        include('../employees/controller/employee_controller.php');
        include('../includes/autoloader.inc.php');

        setNavigation($_SESSION['Roles']);
	?>
    <meta charset="utf-8">
    <title>
    Employees Attendance Records
    </title>
    <meta name="description" content="View Schema">
    <?php
        include('../includes/common_head_content.php');
        ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print"
        href="../css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css">

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    #js-page-content { font-family: 'Inter', 'Segoe UI', sans-serif; }

    /* Premium panel styling */
    .panel { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: #fff; overflow: hidden; }
    .panel-hdr { border-bottom: 1px solid #f1f5f9; padding: 15px 20px; background: #fff; display: flex; align-items: center; }
    .panel-hdr h2 { color: #1e293b; font-size: 16px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    /* Sleek buttons */
    .btn { font-weight: 600; border-radius: 6px; padding: 6px 14px; font-size: 13px; transition: all 0.2s; }
    .btn-outline-secondary { color: #475569; border-color: #cbd5e1; background: #fff; }
    .btn-outline-secondary:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; }
    .btn-primary { background-color: #003f88 !important; border-color: #003f88 !important; color: #fff; }
    .btn-primary:hover { background-color: #002d62 !important; border-color: #002d62 !important; box-shadow: 0 4px 12px rgba(0,63,136,0.3); }

    /* Form Inputs */
    .form-control, .select2-container--default .select2-selection--single, .select2-container--default .select2-selection--multiple { border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; color: #334155; }
    .form-label { font-weight: 600; color: #64748b; font-size: 12px; margin-bottom: 4px; }

    /* Table styling */
    table.dataTable { border-collapse: collapse !important; width: 100% !important; margin-top: 15px !important; }
    table.dataTable thead th { background: #f8fafc; color: #475569; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; border-top: none; }
    table.dataTable tbody td { font-size: 13.5px; color: #334155; padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    table.dataTable tbody tr:hover { background-color: #f8fafc !important; }
    
    .dataTables_wrapper .dataTables_filter input { border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 13px; outline: none; }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: #003f88; box-shadow: 0 0 0 3px rgba(0,63,136,0.1); }
    .dataTables_paginate .paginate_button { border-radius: 6px !important; font-size: 12px !important; font-weight: 600 !important; border: 1px solid #e2e8f0 !important; padding: 4px 10px !important; background: #fff !important; }
    .dataTables_paginate .paginate_button.current { background: #003f88 !important; color: #fff !important; border-color: #003f88 !important; }
    .dataTables_paginate .paginate_button:hover:not(.current) { background: #f1f5f9 !important; color: #003f88 !important; }

    /* Specific overrides */
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    .select2-container { z-index: 2; }
    .select2-dropdown { z-index: 2000 !important; }
    .modal-image { width: 400px; height: 400px; object-fit: cover; }
    </style>
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<?php

	$UserType = SessionCheck();
    $core = new Core();
    $core->setTimeZone();
    $conn = _connectodb();
    $current_date = date("Y-m-d");
    $previous_date =  date('Y-m-d', strtotime('-7 days'));
    $date_range = $previous_date . " - " . $current_date;
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0 ORDER BY Name ASC');
    $filter_options = getAttendanceFilterOptions($conn);
    $date_range = $previous_date . " - " . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&ApprovalStatus=-1';
    
    $userRoles = $_SESSION['Roles']['EmployeeRoles'] ?? [];

    ?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <!-- DOC: script to save and load page settings -->
    <?php include('../js/theme_settings.js'); ?>
    <!-- BEGIN Page Wrapper -->

    <div class="page-wrapper">
        <div class="page-inner">
            <?php
                include('../navigation/admin_navigation.php');
                ?>
            <div class="page-content-wrapper">
                <!-- BEGIN Page Header -->
                <?php
                        include('../includes/common_header.php');
                        // $EmpID = '';
                    ?>
                <!-- END Page Header -->
                <!-- BEGIN Page Content -->
                <!-- the #js-page-content id is needed for some plugins to initialize -->

    


                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Attendance Details</li>
                    </ol>

                    <div class="row" id="today_attendance_stats">                            
                    </div>
                    <div class="panel mb-2">
                            <div class="panel-content p-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-sm mr-1 shadow-sm" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="setAttendanceListQuickFilter('7days')">Last 7 Days</button>
                                        <button type="button" class="btn btn-sm mr-1 shadow-sm" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="setAttendanceListQuickFilter('30days')">Last 30 Days</button>
                                        <button type="button" class="btn btn-sm mr-1 shadow-sm" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="setAttendanceListQuickFilter('365days')">Last 1 Year</button>
                                        <button type="button" class="btn btn-sm mr-1 shadow-sm" style="background-color: #e2e8f0; color: #334155; border: 1px solid #cbd5e1;" onclick="setAttendanceListQuickFilter('all')">All Time</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee</label>
                                        <select class="form-control w-100 attendance-list-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Employees">
                                            <?php foreach ($employees_array as $employee) { ?>
                                                <option value="<?php echo (int) $employee['ID']; ?>"><?php echo htmlspecialchars($employee['Name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee ID / No.</label>
                                        <input type="text" class="form-control" id="employee_number_filter" placeholder="Employee ID">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Approval Status</label>
                                        <select class="form-control w-100 attendance-list-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                            <option value="pending_supervisor">Pending Supervisor</option>
                                            <option value="SupervisorApproved">Supervisor Approved - Pending HR</option>
                                            <option value="Approved">Approved (HR Final)</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">State</label>
                                        <select class="form-control w-100 attendance-list-multi-filter" id="state_filter" multiple="multiple" data-placeholder="All States">
                                            <?php foreach ($filter_options['states'] as $state) { ?>
                                                <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Department</label>
                                        <select class="form-control w-100 attendance-list-multi-filter" id="department_filter" multiple="multiple" data-placeholder="All Departments">
                                            <?php foreach ($filter_options['departments'] as $department) { ?>
                                                <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Designation</label>
                                        <select class="form-control w-100 attendance-list-multi-filter" id="designation_filter" multiple="multiple" data-placeholder="All Designations">
                                            <?php foreach ($filter_options['designations'] as $designation) { ?>
                                                <option value="<?php echo htmlspecialchars($designation); ?>"><?php echo htmlspecialchars($designation); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Date Range</label>
                                        <input type="text" class="form-control" id="filter_date" placeholder="Select date range" value="<?php echo htmlspecialchars($date_range); ?>">
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" onclick="RefreshAttendanceList();" class="btn btn-sm btn-primary mr-2 shadow-sm" style="background-color: #003f88; border-color: #003f88;">Search</button>
                                        <button type="button" onclick="ExportAttendanceData();" class="btn btn-sm btn-primary shadow-sm" style="background-color: #eab308; border-color: #eab308; color: #fff;">Export</button>
                                    </div>

                              <?php  if (!in_array('CFO', $userRoles)) {
                                    ?>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-sm btn-primary shadow-sm" style="background-color: #0d9488; border-color: #0d9488;"
                                                    data-toggle="modal" data-target="#manulattendanceModal">
                                                Manual Attendance Entry
                                            </button>         
                                        </div>
                                    <?php } ?>
                            </div>
                        </div>
                    </div>

                    
                   
                    <!-- Page Container -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>
                                    Attendance Details</span>
                                    </h2>
                                    
                                </div>

                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <!-- datatable start -->
                                         <table id="view-attendance-records" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Date</th>
                                                    <th>Check In</th>
                                                    <th>Check Out</th>
                                                    <th>Duration</th>
                                                    <th>Approval Status</th>
                                                    <th>Approved By / Time</th>
                                                    <th>State</th>

                                                </tr>
                                            </thead>
                                        </table>
                                        <!-- datatable end -->
                                    </div>
                                </div>

                            </div><!-- panel-1 -->
                        </div><!-- col-xl-12 -->
                    </div> <!-- row -->
                    <!-- Datatable Container -->


                    <!-- Modal -->
                   

                </main>

                <!-- this overlay is activated only when mobile menu is triggered -->
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <!-- END Page Content -->
                <!-- BEGIN Page Footer -->
                <?php
                        include('../includes/common_footer.php')
                    ?>
                <!-- END Page Footer -->

            </div>
        </div>
    </div>
    <!-- END Page Wrapper -->

    <?php
        include('../includes/common_modules.php');
        include('../includes/common_scripts.php');
       ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/attendance-list.js"></script>
    <script src="../js/modules/employee.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            initAttendanceListPage('<?php echo $filter_param; ?>');
        });
    </script>

</body>


</html>

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


<!-- --------------------Employee Attendance manual entry model---------------- -->

<div class="modal fade" id="manulattendanceModal" tabindex="-1" aria-labelledby="manulattendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal_header">
        <h5 class="modal-title" id="manulattendanceModalLabel">Employee Attendance Entry</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="manualAttendanceForm">
          <div class="form-group">
            <label for="employee_select">Employee</label>
            <select class="form-control select2" id="employee_select" name="employee_id" required>
              <option value="">Select Employee</option>
              <?php foreach($employees_array as $employee): ?>
                <option value="<?= $employee['ID']; ?>"><?= $employee['Name']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="attendance_date">Date</label>
            <input type="date" class="form-control" id="attendance_date" name="record_date" value="<?= date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label for="checkin_time">Check-In Time</label>
            <input type="time" class="form-control" id="checkin_time" name="in_time" required>
          </div>
          <div class="form-group">
            <label for="checkout_time">Check-Out Time</label>
            <input type="time" class="form-control" id="checkout_time" name="out_time">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="saveManualAttendance">Save</button>
      </div>
    </div>
  </div>
</div>



<!-- Location Modal -->
<div id="locationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999;">
    <div style="background:#fff; width:500px; margin:8% auto; padding:20px; border-radius:6px; position:relative;">

        <h4>Location Details</h4>

        <div id="locationAddress" style="margin-bottom:10px;">Loading...</div>

        <iframe id="mapFrame" width="100%" height="300" style="border:0;"></iframe>

        <br><br>
        <button onclick="closeLocationModal()" style="padding:6px 15px;">Close</button>
    </div>
</div>


<script>
    
    $(document).ready(function() {
    $("#saveManualAttendance").click(function() {
        // Get field values
        var employee_id = $("#employee_select").val();
        var record_date = $("#attendance_date").val();
        var in_time = $("#checkin_time").val();
        var out_time = $("#checkout_time").val();
        if(employee_id === "" || record_date === "" || in_time === "" || out_time === ""){
            TechXAlert('Please fill in all fields!');
            return false; 
        }
        var formData = $("#manualAttendanceForm").serialize();

        $.ajax({
            url: 'action/save-manual-attendance.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                var res = JSON.parse(response);
                if(res.status == 'success'){
                    TechXAlert('Attendance saved successfully!');
                    $("#manulattendanceModal").modal('hide');
                    $('#view-attendance-records').DataTable().ajax.reload();
                } else {
                    TechXAlert('Error: ' + res.message);
                }
            },
            error: function() {
                TechXAlert('Something went wrong!');
            }
        });
    });

    $("#manulattendanceModal .select2").select2({
        dropdownParent: $("#manulattendanceModal")
    });
});

</script>

<script>
function openLocationModal(lat, lng) {

    document.getElementById("locationModal").style.display = "block";
    document.getElementById("locationAddress").innerHTML = "Loading...";

    // Load Google Map
    document.getElementById("mapFrame").src =
        "https://www.google.com/maps?q=" + lat + "," + lng + "&output=embed";

    // Fetch address from backend
    fetch("./action/get_address.php?lat=" + lat + "&lng=" + lng)
        .then(response => response.text())
        .then(data => {
            document.getElementById("locationAddress").innerHTML = data;
        })
        .catch(error => {
            document.getElementById("locationAddress").innerHTML = "Unable to fetch address.";
        });
}

function closeLocationModal() {
    document.getElementById("locationModal").style.display = "none";
}
</script>

