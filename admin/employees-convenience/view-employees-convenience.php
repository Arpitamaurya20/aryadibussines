<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include('../includes/autoloader.inc.php');
        include('../controllers/common_controllers.php');
        include('../employees/controller/employee_controller.php');
        include('../employees-convenience/controller/convenience_controller.php');
        $UserType = SessionCheck();
        setNavigation($_SESSION['Roles']);
        $conn = _connectodb();
    ?>
    <meta charset="utf-8">
    <title>Employees Convenience - Aryadibusiness</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <link rel="stylesheet" media="screen, print" href="../css/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.css">
    <style>
    .modal_header { background-color: #003f88; color: #fff; }
    .modal_header button { opacity: 1; color: #fff; }
    </style>
</head>
<?php
    $current_date = date('Y-m-d');
    $previous_date = date('Y-m-d', strtotime('-365 days'));
    $date_range = $previous_date . ' - ' . $current_date;
    $filter_options = getConvenienceFilterOptions($conn);
    $employee = new Employee($conn);
    $employees_array = $employee->setEmployeeArray('All');
    $filter_param = '?filter_date=' . urlencode($date_range);
?>
<body class="mod-bg-1 desktop chrome webkit pace-done nav-function-fixed blur">
    <?php include('../js/theme_settings.js'); ?>
    <div class="page-wrapper">
        <div class="page-inner">
            <?php include('../navigation/admin_navigation.php'); ?>
            <div class="page-content-wrapper">
                <?php include('../includes/common_header.php'); ?>
                <main id="js-page-content" role="main" class="page-content">
                    <div class="d-flex justify-content-between mb-3 align-items-center">
                        <ol class="breadcrumb page-breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="../dashboard/admin_dashboard">Aryadibusiness</a></li>
                            <li class="breadcrumb-item active">Employees Convenience</li>
                        </ol>
                    </div>

                    <div class="alert alert-secondary mb-3">
                        Admin view of all convenience claims. Supervisors and HR should use their dedicated approval pages for actions.
                    </div>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>View Employees Convenience</h2>
                            <button type="button" onclick="FilterEmployeeConvenienceData();" class="btn btn-sm btn-primary ml-3">Search</button>
                            <form id="export_form" class="d-inline">
                                <button type="button" onclick="ExportEmployeeConvenienceData();" class="btn btn-sm btn-info ml-3">Export Data</button>
                                <input type="hidden" name="filter_date_export" id="filter_date_export">
                                <input type="hidden" name="employee_filter_export" id="employee_filter_export">
                                <input type="hidden" name="status_export" id="status_export">
                                <input type="hidden" name="state_export" id="state_export">
                                <input type="hidden" name="department_export" id="department_export">
                                <input type="hidden" name="designation_export" id="designation_export">
                                <input type="hidden" name="ticket_export" id="ticket_export">
                                <input type="hidden" name="employee_number_export" id="employee_number_export">
                                <input type="hidden" name="scope_export" value="admin">
                            </form>
                        </div>

                        <div class="panel-container show">
                            <div class="panel-content p-3">
                                <div class="row">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee Name</label>
                                        <select class="select2 form-control w-100" id="employee_filter">
                                            <option value="-1">All Employees</option>
                                            <?php foreach ($employees_array as $emp_id => $emp_row) { ?>
                                                <option value="<?php echo (int) $emp_id; ?>"><?php echo htmlspecialchars($emp_row['Name']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Employee ID / No.</label>
                                        <input type="text" class="form-control" id="employee_number_filter" placeholder="Employee ID">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Status</label>
                                        <select class="form-control" id="c_status">
                                            <option value="">All Status</option>
                                            <option value="1">Pending Supervisor</option>
                                            <option value="2">Pending HR Final</option>
                                            <option value="5">HR Approved - Pending Payment</option>
                                            <option value="6">Payment Done</option>
                                            <option value="-1">Rejected by Supervisor</option>
                                            <option value="-2">Rejected by HR</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">State</label>
                                        <select class="form-control" id="state_filter">
                                            <option value="-1">All States</option>
                                            <?php foreach ($filter_options['states'] as $state) { ?>
                                                <option value="<?php echo htmlspecialchars($state); ?>"><?php echo htmlspecialchars($state); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Department</label>
                                        <select class="form-control" id="department_filter">
                                            <option value="-1">All Departments</option>
                                            <?php foreach ($filter_options['departments'] as $department) { ?>
                                                <option value="<?php echo htmlspecialchars($department); ?>"><?php echo htmlspecialchars($department); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Designation / Post</label>
                                        <select class="form-control" id="designation_filter">
                                            <option value="-1">All Designations</option>
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
                                </div>

                                <table id="view-employees-convenience" class="table table-bordered table-hover table-striped w-100 mt-3">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Employee</th>
                                            <th>Emp. ID</th>
                                            <th>Ticket</th>
                                            <th>From - To</th>
                                            <th>Amount</th>
                                            <th>Remarks</th>
                                            <th>Date</th>
                                            <th>State</th>
                                            <th>Department</th>
                                            <th>Designation</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Approval Info</th>
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
    <script src="../js/dependency/moment/moment.js"></script>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/formplugins/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
    <script src="../js/modules/employees-convenience.js"></script>
    <script>
        $(document).ready(function () {
            $('#employee_filter').select2();
            $('#filter_date').daterangepicker({ locale: { format: 'YYYY-MM-DD' } });
            initEmployeeConvenienceTable('<?php echo $filter_param; ?>');
        });
    </script>
</body>
</html>
