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
    <title>HR Attendance Final Approval - TechXpert</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    
    <style>
        /* Modern Design Overrides */
        :root {
            --primary-accent: #1e3a8a;
            --primary-hover: #1e40af;
            --surface-bg: #f8fafc;
            --card-border-color: #e2e8f0;
        }

        body {
            background-color: #f1f5f9;
        }

        .custom-card {
            border: 1px solid var(--card-border-color);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            background: #ffffff;
            transition: all 0.2s ease-in-out;
        }

        /* Banner Styling */
        .info-banner-modern {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #2563eb;
            border-radius: 10px;
            color: #1e40af;
            padding: 1rem 1.25rem;
        }

        /* Filter Section */
        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .btn-quick-filter {
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-selection--multiple {
            border-color: #cbd5e1 !important;
            border-radius: 8px !important;
            min-height: 38px;
        }

        .form-control {
            border-radius: 8px !important;
            border-color: #cbd5e1;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        /* Action Buttons Header */
        .bulk-actions-wrapper {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        /* Modern Table Formatting */
        .table-modern {
            border-collapse: separate !important;
            border-spacing: 0;
            width: 100% !important;
        }

        .table-modern thead th {
            background-color: #f8fafc !important;
            color: #475569 !important;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 12px 10px;
        }

        .table-modern tbody tr {
            transition: background-color 0.15s ease;
        }

        .table-modern tbody tr:hover {
            background-color: #f8fafc !important;
        }

        tr.attendance-row-highlight td {
            background-color: #fef3c7 !important;
            transition: background-color 0.3s ease;
        }

        /* Modal Enhancements */
        .modal-content {
            border: none;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .modal_header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 1.2rem 1.5rem;
        }

        .modal_header button.close {
            opacity: 0.8;
            color: #fff;
            text-shadow: none;
        }

        .modal_header button.close:hover {
            opacity: 1;
        }

        .modal-image {
            width: 100%;
            max-width: 420px;
            height: 380px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .select2-container { z-index: 1; }
        body.modal-open #attendance_hr_reject_modal { z-index: 1060 !important; }
        body.modal-open .modal-backdrop { z-index: 1050 !important; }
        body.modal-open #js-page-content .select2-container,
        body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
    </style>
</head>

<?php
    $UserType = SessionCheck();
    $roles = $_SESSION['Roles'] ?? array();
    if (!hasHrAttendanceApprovalAccess($roles)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }
    $core = new Core();
    $core->setTimeZone();
    $conn = _connectodb();
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0 ORDER BY Name ASC');
    $filter_options = getAttendanceFilterOptions($conn);
    $current_date = date('Y-m-d');
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . ' - ' . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&ApprovalStatus=hr_actionable';
?>

<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    
                    <!-- Breadcrumbs -->
                    <ol class="breadcrumb page-breadcrumb mb-3">
                        <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">TechXpert</a></li>
                        <li class="breadcrumb-item text-primary font-weight-bold">HR Final Attendance Approval</li>
                    </ol>

                    <!-- Header Banner -->
                    <div class="info-banner-modern mb-4 d-flex align-items-center">
                        <div class="mr-3 fs-xl">
                            <i class="fal fa-shield-check fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="font-weight-bold mb-1" style="color: #1e3a8a;">Level 2 – HR Final Authorization</h6>
                            <p class="mb-0 small">Review supervisor-approved attendance. Records are listed newest-first (LIFO). Approving a record seamlessly updates your view without forcing a full page reload.</p>
                        </div>
                    </div>

                    <!-- Filter Card -->
                    <div class="custom-card p-3 mb-4">
                        <!-- Quick Filter Buttons -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between border-bottom pb-3 mb-3">
                            <span class="text-uppercase text-muted font-weight-bold small"><i class="fal fa-filter mr-1"></i> Quick Filters</span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-warning btn-quick-filter mr-1 active" onclick="setHrAttendanceQuickFilter('pending_hr')">Pending HR Final</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-quick-filter mr-1" onclick="setHrAttendanceQuickFilter('7days')">Last 7 Days</button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-quick-filter mr-1" onclick="setHrAttendanceQuickFilter('365days')">Last 1 Year</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-quick-filter" onclick="setHrAttendanceQuickFilter('all')">All Time</button>
                            </div>
                        </div>

                        <!-- Dropdown & Input Filters Grid -->
                        <div class="row">
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">Employee</label>
                                <select class="select2 form-control w-100 attendance-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Employees">
                                    <?php foreach ($employees_array as $emp) { ?>
                                        <option value="<?php echo (int) $emp['ID']; ?>"><?php echo htmlspecialchars($emp['Name']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">Emp. ID / No.</label>
                                <input type="text" class="form-control form-control-sm" id="employee_number_filter" placeholder="e.g. TECHX001">
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">Approval Status</label>
                                <select class="select2 form-control w-100 attendance-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                    <option value="hr_actionable" selected>Pending HR Final</option>
                                    <option value="SupervisorApproved">Supervisor Approved - Pending HR</option>
                                    <option value="Pending">Pending Supervisor</option>
                                    <option value="Approved">Approved (HR Final)</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">State</label>
                                <select class="select2 form-control w-100 attendance-multi-filter" id="state_filter" multiple="multiple" data-placeholder="All States">
                                    <?php foreach ($filter_options['states'] as $state) { ?>
                                        <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">Department</label>
                                <select class="select2 form-control w-100 attendance-multi-filter" id="department_filter" multiple="multiple" data-placeholder="All Depts">
                                    <?php foreach ($filter_options['departments'] as $department) { ?>
                                        <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                                <label class="filter-label">Designation</label>
                                <select class="select2 form-control w-100 attendance-multi-filter" id="designation_filter" multiple="multiple" data-placeholder="All Roles">
                                    <?php foreach ($filter_options['designations'] as $designation) { ?>
                                        <option value="<?php echo htmlspecialchars($designation); ?>"><?php echo htmlspecialchars($designation); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="row align-items-end mt-1">
                            <div class="col-md-9 mb-2 mb-md-0">
                                <label class="filter-label">Date Range</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white border-right-0"><i class="fal fa-calendar-alt text-muted"></i></span>
                                    </div>
                                    <input type="text" class="form-control border-left-0" id="filter_date" placeholder="Select date range" value="<?php echo htmlspecialchars($date_range); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="button" onclick="RefreshHrAttendanceApproval();" class="btn btn-primary btn-block font-weight-bold" style="border-radius: 8px; height: 38px;">
                                    <i class="fal fa-search mr-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table Card -->
                    <div class="custom-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                            <h5 class="m-0 font-weight-bold text-dark"><i class="fal fa-list-alt text-primary mr-2"></i>Attendance Approvals</h5>
                        </div>

                        <!-- Bulk Action Controls -->
                        <div class="bulk-actions-wrapper mb-3 d-flex flex-wrap align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-sm text-white mr-2 font-weight-bold px-3 shadow-sm" id="btn_attendance_hr_bulk_approve" onclick="submitAttendanceHrBulkApprove()" disabled style="background-color: #0284c7; border: none; border-radius: 6px;">
                                    <i class="fal fa-check-circle mr-1"></i> Approve Selected
                                </button>
                                <button type="button" class="btn btn-sm text-white mr-3 font-weight-bold px-3 shadow-sm" id="btn_attendance_hr_bulk_reject" onclick="openAttendanceHrBulkRejectModal()" disabled style="background-color: #003f88; border: none; border-radius: 6px;">
                                    <i class="fal fa-times-circle mr-1"></i> Reject Selected
                                </button>
                                <span class="badge badge-light border text-muted font-weight-normal px-2 py-1" id="attendance_hr_selected_count">0 selected</span>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table id="view-attendance-hr-approval-records" class="table table-bordered table-hover table-modern w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="attendance_hr_select_all" title="Select all actionable rows"></th>
                                        <th>Employee</th>
                                        <th>Emp. No.</th>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th>Approval Details</th>
                                        <th>HR Action</th>
                                        <th>State</th>
                                        <th>Department</th>
                                        <th>Designation</th>
                                    </tr>
                                </thead>
                            </table>
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
    <script src="../js/modules/attendance-hr-approval.js"></script>
    <script>
        $(document).ready(function () {
            initHrAttendanceApprovalPage('<?php echo $filter_param; ?>');
        });
    </script>

    <!-- Reject Modal -->
    <div class="modal fade" id="attendance_hr_reject_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title font-weight-bold text-white"><i class="fal fa-exclamation-triangle mr-2"></i>HR Reject Attendance</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="attendance_hr_reject_mode" value="single">
                    <input type="hidden" id="attendance_hr_reject_id" value="">
                    <p class="small text-muted d-none mb-3" id="attendance_hr_reject_bulk_info"></p>
                    <div class="form-group mb-4">
                        <label class="filter-label">Rejection Reason <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input type="text" class="form-control" id="attendance_hr_reject_reason" placeholder="Provide reason for rejection...">
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-light mr-2 font-weight-bold" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger font-weight-bold px-4" onclick="submitHrAttendanceReject()">Reject Record</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title font-weight-bold text-white"><i class="fal fa-camera mr-2"></i>Attendance Photo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body d-flex justify-content-center p-3">
                    <img id="modalImage" class="modal-image" alt="Preview">
                </div>
            </div>
        </div>
    </div>

    <!-- Location Modal Standardized to Bootstrap Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal_header">
                    <h5 class="modal-title font-weight-bold text-white"><i class="fal fa-map-marker-alt mr-2"></i>Location Details</h5>
                    <button type="button" class="close" onclick="closeLocationModal()">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div id="locationAddress" class="alert alert-light border mb-3 font-weight-bold text-dark">Loading address...</div>
                    <div class="embed-responsive embed-responsive-16by9 border rounded">
                        <iframe id="mapFrame" class="embed-responsive-item" style="border:0;" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary font-weight-bold" onclick="closeLocationModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>