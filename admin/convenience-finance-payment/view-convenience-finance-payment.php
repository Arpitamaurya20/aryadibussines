<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../controllers/common_controllers.php');
        include('../employees-convenience/controller/convenience_controller.php');
        setNavigation($_SESSION['Roles']);
    ?>
    <meta charset="utf-8">
    <title>Convenience Finance Payment - Aryadibussines</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    body.modal-open #convenience_finance_modal { z-index: 1060 !important; }
    body.modal-open .modal-backdrop { z-index: 1050 !important; }
    body.modal-open #js-page-content .select2-container,
    body.modal-open #js-page-content .select2-dropdown { z-index: 1 !important; }
    </style>
    <?php echo buildConvenienceDashboardStylesHtml(); ?>
</head>
<?php
    $UserType = SessionCheck();
    $roles = $_SESSION['Roles'] ?? array();
    if (!hasFinanceConveniencePaymentAccess($roles)) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }

    $conn = _connectodb();
    $core = new Core();
    $employees_array = $core->_getTableRecords($conn, 'employees', 'where IsActive = 1 and Vendor = 0 ORDER BY Name ASC');
    $filter_options = getConvenienceFilterOptions($conn);
    $current_date = date('Y-m-d');
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . ' - ' . $current_date;
    $filter_param = '?filter_date=' . urlencode($date_range) . '&EmployeeID=-1&status=finance_actionable';
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
                        <li class="breadcrumb-item active">Convenience Finance Payment</li>
                    </ol>

                    <div class="alert alert-info mb-3">
                        <strong>Level 3 – Finance:</strong> Process HR-approved convenience reimbursements. Mark payment as done with reference and mode, or reject if the claim should not be paid. Employees are notified when payment is completed or rejected.
                    </div>

                    <?php echo buildConvenienceDashboardCardsHtml('finance'); ?>

                    <div class="panel mb-2">
                        <div class="panel-hdr"><h2>HR Approved - Pending Payment</h2></div>
                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="row mb-2">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-sm btn-outline-warning mr-1" onclick="setConvenienceFinanceQuickFilter('pending_payment')">Pending Payment</button>
                                        <button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="setConvenienceFinanceQuickFilter('payment_done')">Payment Done</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="setConvenienceFinanceQuickFilter('365days')">Last 1 Year</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" onclick="setConvenienceFinanceQuickFilter('all')">All Time</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee Name</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="employee_filter" multiple="multiple" data-placeholder="All Employees">
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
                                        <label class="form-label small text-muted">Payment Status</label>
                                        <select class="select2 form-control w-100 convenience-multi-filter" id="status_filter" multiple="multiple" data-placeholder="All Status">
                                            <option value="finance_actionable" selected>Pending Payment</option>
                                            <option value="<?php echo convenienceStatusPaid(); ?>">Payment Done</option>
                                            <option value="<?php echo convenienceStatusHrApproved(); ?>">HR Approved - Pending</option>
                                            <option value="<?php echo convenienceStatusRejectedFinance(); ?>">Rejected by Finance</option>
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
                                        <label class="form-label small text-muted">Designation</label>
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
                                        <input type="text" class="form-control" id="filter_date" value="<?php echo htmlspecialchars($date_range); ?>">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-primary w-100" onclick="RefreshConvenienceFinancePayment();">Search</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="mb-2 d-flex flex-wrap align-items-center">
                                    <button type="button" class="btn btn-sm btn-success mr-1 mb-1" id="btn_finance_bulk_paid" onclick="openConvenienceFinanceBulkModal('paid')" disabled>Mark Selected Paid</button>
                                    <button type="button" class="btn btn-sm btn-danger mr-1 mb-1" id="btn_finance_bulk_reject" onclick="openConvenienceFinanceBulkModal('reject')" disabled>Reject Selected</button>
                                    <span class="small text-muted mb-1" id="convenience_finance_selected_count">0 selected</span>
                                </div>
                                <table id="view-convenience-finance-payment" class="table table-bordered table-hover table-striped w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" id="convenience_finance_select_all" title="Select all pending payment rows"></th>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Ticket</th>
                                            <th>From - To</th>
                                            <th>Approved Amount</th>
                                            <th>Date</th>
                                            <th>State</th>
                                            <th>Department</th>
                                            <th>Payment Status</th>
                                            <th>Approval / Payment Info</th>
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

    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/convenience-dashboard.js"></script>
    <script src="../js/modules/convenience-finance-payment.js"></script>
    <script>
        $(document).ready(function () {
            initConvenienceFinancePaymentPage('<?php echo $filter_param; ?>');
        });
    </script>
</body>
</html>

<div class="modal fade" id="convenience_finance_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal_header">
                <h5 class="modal-title">Finance Payment Action</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="convenience_finance_action_id" value="">
                <input type="hidden" id="convenience_finance_action_mode" value="single">
                <input type="hidden" id="convenience_finance_action_type" value="paid">
                <p class="small text-muted d-none" id="convenience_finance_bulk_info"></p>
                <div id="convenience_finance_paid_fields">
                    <div class="form-group">
                        <label>Payment Reference (UTR / Cheque / Batch)</label>
                        <input type="text" class="form-control" id="convenience_finance_payment_reference" placeholder="Payment reference">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode</label>
                        <select class="form-control" id="convenience_finance_payment_mode">
                            <option value="">Select mode</option>
                            <option value="NEFT">NEFT</option>
                            <option value="RTGS">RTGS</option>
                            <option value="UPI">UPI</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label id="convenience_finance_remarks_label">Remarks</label>
                    <input type="text" class="form-control" id="convenience_finance_payment_remarks" placeholder="Optional remarks">
                </div>
                <button type="button" class="btn btn-success" id="btn_convenience_finance_modal_paid" onclick="submitConvenienceFinanceMarkPaid()">Mark Payment Done</button>
                <button type="button" class="btn btn-danger" id="btn_convenience_finance_modal_reject" onclick="submitConvenienceFinanceReject()">Reject Payment</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
