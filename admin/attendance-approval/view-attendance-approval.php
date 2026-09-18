<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../attendance-list/controller/attendance_controller.php');
        include('../employees/controller/employee_controller.php');
        include('../includes/autoloader.inc.php');

        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Team Attendance - Aryadibusiness</title>
    <meta name="description" content="Supervisor team attendance history and approval">
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    .select2-container { z-index: 1; }
    body.modal-open #attendance_reject_modal { z-index: 1060 !important; }
    body.modal-open .modal-backdrop { z-index: 1050 !important; }
    body.modal-open #js-page-content .select2-container,
    body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
    .modal-image { width: 400px; height: 400px; object-fit: cover; }
    tr.attendance-row-highlight td { background-color: #fff3cd !important; transition: background-color 0.3s ease; }
    </style>
</head>
<?php
    $UserType = SessionCheck();
    $core = new Core();
    $core->setTimeZone();
    $conn = _connectodb();
    $employee = new Employee($conn);

    $roles = $_SESSION['Roles'] ?? array();
    $supervisor_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
    if ($supervisor_employee_id <= 0 || !employeeHasSupervisedTeam($conn, $supervisor_employee_id)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $employees_array = getSupervisedEmployeesList($conn, $supervisor_employee_id, false);
    $filter_options = getAttendanceFilterOptions($conn);

    $current_date = date('Y-m-d');
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . ' - ' . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&ApprovalStatus=pending_supervisor&Supervisor_EmployeeID=' . $supervisor_employee_id;
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <ol class="breadcrumb page-breadcrumb">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                        <li class="breadcrumb-item active">Team Attendance</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Level 1 – Supervisor:</strong> Approve or reject your team attendance. Records are shown newest-first (LIFO). Click approve to process the next pending record without scrolling back to the top.
                    </div>

                    <div class="panel mb-2">
                        <div class="panel-content p-3">
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-sm btn-outline-warning mr-1" onclick="setAttendanceQuickFilter('pending')">Pending Supervisor</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setAttendanceQuickFilter('7days')">Last 7 Days</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setAttendanceQuickFilter('30days')">Last 30 Days</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setAttendanceQuickFilter('365days')">Last 1 Year</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="setAttendanceQuickFilter('all')">All Time</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Team Member</label>
                                    <select class="select2 form-control w-100 attendance-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Team Members">
                                        <?php foreach ($employees_array as $emp) {
                                            $emp_label = htmlspecialchars($emp['Name']);
                                            if (isset($emp['IsActive']) && (int) $emp['IsActive'] === 0) {
                                                $emp_label .= ' (Inactive)';
                                            }
                                        ?>
                                            <option value="<?php echo (int) $emp['ID']; ?>"><?php echo $emp_label; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Employee ID / No.</label>
                                    <input type="text" class="form-control" id="employee_number_filter" placeholder="Employee ID">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Status</label>
                                    <select class="select2 form-control w-100 attendance-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                        <option value="pending_supervisor" selected>Pending Supervisor</option>
                                        <option value="SupervisorApproved">Pending HR Final</option>
                                        <option value="Approved">Approved (HR Final)</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">State</label>
                                    <select class="select2 form-control w-100 attendance-multi-filter" id="state_filter" multiple="multiple" data-placeholder="All States">
                                        <?php foreach ($filter_options['states'] as $state) { ?>
                                            <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Department</label>
                                    <select class="select2 form-control w-100 attendance-multi-filter" id="department_filter" multiple="multiple" data-placeholder="All Departments">
                                        <?php foreach ($filter_options['departments'] as $department) { ?>
                                            <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Designation</label>
                                    <select class="select2 form-control w-100 attendance-multi-filter" id="designation_filter" multiple="multiple" data-placeholder="All Designations">
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
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" onclick="RefreshAttendanceApproval();" class="btn btn-sm btn-primary waves-effect waves-themed w-100">Search</button>
                                </div>
                                <input type="hidden" id="supervisor_employee_id" value="<?php echo $supervisor_employee_id; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-12">
                            <div id="panel-1" class="panel">
                                <div class="panel-hdr">
                                    <h2>Team Attendance History &amp; Approval</h2>
                                </div>
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="mb-2 d-flex flex-wrap align-items-center">
                                            <button type="button" class="btn btn-sm btn-primary mr-1 mb-1" id="btn_attendance_supervisor_bulk_approve" onclick="submitAttendanceSupervisorBulkApprove()" disabled>Approve Selected</button>
                                            <button type="button" class="btn btn-sm btn-danger mr-1 mb-1" id="btn_attendance_supervisor_bulk_reject" onclick="openAttendanceSupervisorBulkRejectModal()" disabled>Reject Selected</button>
                                            <span class="small text-muted mb-1" id="attendance_supervisor_selected_count">0 selected</span>
                                        </div>
                                        <table id="view-attendance-approval-records" class="table table-bordered table-hover table-striped w-100">
                                            <thead>
                                                <tr>
                                                    <th><input type="checkbox" id="attendance_supervisor_select_all" title="Select all actionable rows"></th>
                                                    <th>Employee</th>
                                                    <th>Emp. No.</th>
                                                    <th>Date</th>
                                                    <th>Check In</th>
                                                    <th>Check Out</th>
                                                    <th>Duration</th>
                                                    <th>Status</th>
                                                    <th>Approved By / Time</th>
                                                    <th>Action</th>
                                                    <th>State</th>
                                                    <th>Department</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/attendance-approval.js"></script>
    <script src="../js/modules/employee.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            initAttendanceApprovalPage('<?php echo $filter_param; ?>');
        });
    </script>

    <div class="modal fade" id="attendance_reject_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Reject Attendance</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="attendance_reject_mode" value="single">
                    <input type="hidden" id="attendance_reject_id" value="">
                    <p class="small text-muted d-none" id="attendance_reject_bulk_info"></p>
                    <div class="form-group">
                        <label>Rejection reason (optional)</label>
                        <input type="text" class="form-control" id="attendance_reject_reason" placeholder="Reason">
                    </div>
                    <button type="button" class="btn btn-danger" onclick="submitAttendanceReject()">Reject</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Attendance Photo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body d-flex justify-content-center">
        <img id="modalImage" alt="Preview" class="modal-image">
      </div>
    </div>
  </div>
</div>

<div id="locationModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999;">
    <div style="background:#fff; width:500px; margin:8% auto; padding:20px; border-radius:6px; position:relative;">
        <h4>Location Details</h4>
        <div id="locationAddress" style="margin-bottom:10px;">Loading...</div>
        <iframe id="mapFrame" width="100%" height="300" style="border:0;"></iframe>
        <br><br>
        <button onclick="closeLocationModal()" style="padding:6px 15px;">Close</button>
    </div>
</div>
