<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../employees/controller/employee_controller.php');
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>HR Leave Approval - TechXpert</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    body.modal-open #leave_hr_modal { z-index: 1060 !important; }
    body.modal-open .modal-backdrop { z-index: 1050 !important; }
    body.modal-open #js-page-content .select2-container,
    body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
    </style>
</head>
<?php
    $UserType = SessionCheck();
    $roles = $_SESSION['Roles'] ?? array();
    if (!hasHrLeaveApprovalAccess($roles)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $conn = _connectodb();
    $core = new Core();
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0 ORDER BY Name ASC');
    $filter_options = getLeaveFilterOptions($conn);
    $current_date = date('Y-m-d');
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . ' - ' . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&status=hr_actionable';
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
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item active">HR Leave Approval</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Level 2 – HR Final:</strong> Review supervisor-approved leave requests and give HR final approval or rejection. Use multi-select filters and bulk actions to process multiple records at once.
                    </div>

                    <div class="panel mb-2">
                        <div class="panel-hdr"><h2>Employee Leave Requests</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-sm btn-outline-warning mr-1" onclick="setLeaveHrQuickFilter('pending_hr')">Pending HR Action</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setLeaveHrQuickFilter('365days')">Last 1 Year</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="setLeaveHrQuickFilter('all')">All Time</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Employees">
                                            <?php foreach ($employees_array as $emp) { ?>
                                                <option value="<?php echo (int) $emp['ID']; ?>"><?php echo htmlspecialchars($emp['Name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee ID / No.</label>
                                        <input type="text" class="form-control" id="employee_number_filter" placeholder="Employee ID">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Status</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                            <option value="hr_actionable" selected>Pending HR Action</option>
                                            <option value="SupervisorApproved">Supervisor Approved - Pending HR</option>
                                            <option value="Pending">Pending Supervisor</option>
                                            <option value="Approved">Approved (HR Final)</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Leave Type</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="leave_type_filter" multiple="multiple" data-placeholder="All Types">
                                            <?php foreach ($filter_options['leave_types'] as $leave_type) { ?>
                                                <option value="<?php echo htmlspecialchars($leave_type); ?>"><?php echo htmlspecialchars($leave_type); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">State</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="state_filter" multiple="multiple" data-placeholder="All States">
                                            <?php foreach ($filter_options['states'] as $state) { ?>
                                                <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Department</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="department_filter" multiple="multiple" data-placeholder="All Departments">
                                            <?php foreach ($filter_options['departments'] as $department) { ?>
                                                <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Designation / Post</label>
                                        <select class="select2 form-control w-100 leave-multi-filter" id="designation_filter" multiple="multiple" data-placeholder="All Designations">
                                            <?php foreach ($filter_options['designations'] as $designation) { ?>
                                                <option value="<?php echo htmlspecialchars($designation); ?>"><?php echo htmlspecialchars($designation); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Leave From Date Range</label>
                                        <input type="text" class="form-control" id="filter_date" value="<?php echo htmlspecialchars($date_range); ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="RefreshLeaveHrApproval();">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="mb-2 d-flex flex-wrap align-items-center">
                                    <button type="button" class="btn btn-sm btn-primary mr-1 mb-1" id="btn_leave_hr_bulk_approve" onclick="openLeaveHrBulkModal('approve')" disabled>Approve Selected</button>
                                    <button type="button" class="btn btn-sm btn-danger mr-1 mb-1" id="btn_leave_hr_bulk_reject" onclick="openLeaveHrBulkModal('reject')" disabled>Reject Selected</button>
                                    <span class="small text-muted mb-1" id="leave_hr_selected_count">0 selected</span>
                                </div>
                                <table id="view-leave-hr-approval" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="leave_hr_select_all" title="Select all actionable rows"></th>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Type Of Leave</th>
                                            <th>Reason</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Duration</th>
                                            <th>Requested On</th>
                                            <th>State</th>
                                            <th>Department</th>
                                            <th>Designation</th>
                                            <th>Status</th>
                                            <th>Approval Info</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="leave_hr_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">HR Final Leave Approval</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="leave_hr_action_id" value="">
                    <input type="hidden" id="leave_hr_action_mode" value="single">
                    <input type="hidden" id="leave_hr_bulk_action_type" value="">
                    <p class="small text-muted d-none" id="leave_hr_bulk_info"></p>
                    <div class="form-group">
                        <label>Remarks / Rejection Reason</label>
                        <input type="text" class="form-control" id="leave_hr_remarks" placeholder="Optional">
                    </div>
                    <button type="button" class="btn btn-primary" id="btn_leave_hr_modal_approve" onclick="submitLeaveHrApprove()">Approve</button>
                    <button type="button" class="btn btn-danger" id="btn_leave_hr_modal_reject" onclick="submitLeaveHrReject()">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/leave-hr-approval.js"></script>
    <script>
        $(document).ready(function () {
            initLeaveHrApprovalPage('<?php echo $filter_param; ?>');
        });
    </script>
</body>
</html>
