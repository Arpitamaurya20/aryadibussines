<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../attendance-list/controller/attendance_controller.php');
        include('../employees-convenience/controller/convenience_controller.php');
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Team Convenience Approval - Aryadibussines</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
  body.modal-open #convenience_supervisor_modal { z-index: 1060 !important; }
  body.modal-open .modal-backdrop { z-index: 1050 !important; }
  body.modal-open #js-page-content .select2-container,
  body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
    </style>
    <?php echo buildConvenienceDashboardStylesHtml(); ?>
</head>
<?php
    $UserType = SessionCheck();
    $conn = _connectodb();
    $roles = $_SESSION['Roles'] ?? array();
    $supervisor_employee_id = isset($roles['EmployeeID']) ? (int) $roles['EmployeeID'] : -1;
    if ($supervisor_employee_id <= 0 || !employeeHasConvenienceTeam($conn, $supervisor_employee_id)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $employees_array = getConvenienceTeamEmployeesList($conn, $supervisor_employee_id, false);
    $filter_options = getConvenienceFilterOptions($conn);
    $current_date = date('Y-m-d');
    $date_range = '2020-01-01 - ' . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&status=-1&Supervisor_EmployeeID=' . $supervisor_employee_id;
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
                        <li class="breadcrumb-item active">Team Convenience Approval</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Level 1 – Supervisor:</strong> Approve or reject your team convenience claims within <strong>24 hours</strong> of submission. After 24 hours, action is disabled and HR will approve or reject.
                    </div>

                    <?php echo buildConvenienceDashboardCardsHtml('supervisor'); ?>

                    <div class="panel mb-2">
                        <div class="panel-hdr"><h2>Team Convenience Requests</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-sm btn-outline-warning mr-1" onclick="setConvenienceQuickFilter('pending')">Pending Supervisor</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setConvenienceQuickFilter('7days')">Last 7 Days</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setConvenienceQuickFilter('365days')">Last 1 Year</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="setConvenienceQuickFilter('all')">All Time</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Team Member</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Team Members">
                                            <?php foreach ($employees_array as $emp) {
                                                $label = htmlspecialchars($emp['Name']);
                                                if ((int) ($emp['IsActive'] ?? 1) === 0) {
                                                    $label .= ' (Inactive)';
                                                }
                                            ?>
                                                <option value="<?php echo (int) $emp['ID']; ?>"><?php echo $label; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee ID / No.</label>
                                        <input type="text" class="form-control" id="employee_number_filter" placeholder="Employee ID">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Status</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                            <option value="<?php echo convenienceStatusPendingSupervisor(); ?>">Pending Supervisor</option>
                                            <option value="<?php echo convenienceStatusSupervisorApproved(); ?>">Pending HR Final</option>
                                            <option value="<?php echo convenienceStatusHrApproved(); ?>">Approved (HR Final)</option>
                                            <option value="<?php echo convenienceStatusRejectedSupervisor(); ?>">Rejected by Supervisor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">State</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="state_filter" multiple="multiple" data-placeholder="All States">
                                            <?php foreach ($filter_options['states'] as $state) { ?>
                                                <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Department</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="department_filter" multiple="multiple" data-placeholder="All Departments">
                                            <?php foreach ($filter_options['departments'] as $department) { ?>
                                                <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Designation / Post</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="designation_filter" multiple="multiple" data-placeholder="All Designations">
                                            <?php foreach ($filter_options['designations'] as $designation) { ?>
                                                <option value="<?php echo htmlspecialchars($designation); ?>"><?php echo htmlspecialchars($designation); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Ticket ID</label>
                                        <input type="number" class="form-control" id="ticket_filter" placeholder="Ticket PK">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Date Range</label>
                                        <input type="text" class="form-control" id="filter_date" value="<?php echo $date_range; ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="RefreshConvenienceApproval();">Search</button>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-info w-100" onclick="ExportConvenienceSupervisorData();">Export</button>
                                    </div>
                                    <input type="hidden" id="supervisor_employee_id" value="<?php echo $supervisor_employee_id; ?>">
                                    <form id="export_form" class="d-none">
                                        <input type="hidden" name="filter_date_export" id="filter_date_export">
                                        <input type="hidden" name="employee_filter_export" id="employee_filter_export">
                                        <input type="hidden" name="status_export" id="status_export">
                                        <input type="hidden" name="state_export" id="state_export">
                                        <input type="hidden" name="department_export" id="department_export">
                                        <input type="hidden" name="designation_export" id="designation_export">
                                        <input type="hidden" name="ticket_export" id="ticket_export">
                                        <input type="hidden" name="employee_number_export" id="employee_number_export">
                                        <input type="hidden" name="Supervisor_EmployeeID" id="Supervisor_EmployeeID_export" value="<?php echo $supervisor_employee_id; ?>">
                                        <input type="hidden" name="scope_export" value="supervisor">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="mb-2 d-flex flex-wrap align-items-center">
                                    <button type="button" class="btn btn-sm btn-primary mr-1 mb-1" id="btn_supervisor_bulk_approve" onclick="openConvenienceSupervisorBulkModal('approve')" disabled>Approve Selected</button>
                                    <button type="button" class="btn btn-sm btn-danger mr-1 mb-1" id="btn_supervisor_bulk_reject" onclick="openConvenienceSupervisorBulkModal('reject')" disabled>Reject Selected</button>
                                    <span class="small text-muted mb-1" id="convenience_supervisor_selected_count">0 selected</span>
                                </div>
                                <table id="view-convenience-approval" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="convenience_supervisor_select_all" title="Select all actionable rows"></th>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Ticket</th>
                                            <th>From - To</th>
                                            <th>Amount</th>
                                            <th>Remarks</th>
                                            <th>Date</th>
                                            <th>State</th>
                                            <th>Department</th>
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

    <div class="modal fade" id="convenience_supervisor_modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title">Supervisor Approval</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="convenience_action_id" value="">
                    <input type="hidden" id="convenience_action_mode" value="single">
                    <input type="hidden" id="convenience_bulk_action_type" value="">
                    <p class="small text-muted d-none" id="convenience_supervisor_bulk_info"></p>
                    <div class="form-group">
                        <label>Remarks</label>
                        <input type="text" class="form-control" id="convenience_action_remarks" placeholder="Optional remarks">
                    </div>
                    <button type="button" class="btn btn-primary" id="btn_convenience_supervisor_modal_approve" onclick="submitConvenienceSupervisorApprove()">Approve</button>
                    <button type="button" class="btn btn-danger" id="btn_convenience_supervisor_modal_reject" onclick="submitConvenienceSupervisorReject()">Reject</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/convenience-dashboard.js"></script>
    <script src="../js/modules/convenience-approval.js"></script>
    <script>
        $(document).ready(function () {
            initConvenienceApprovalPage('<?php echo $filter_param; ?>');
        });
    </script>
</body>
</html>
