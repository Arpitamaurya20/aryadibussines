<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_audit.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'payments');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpCanRecord = (int) $fpActor['employee_id'] > 0;
$fpOffices = array();
foreach (fp_db_rows(
    $fpDb,
    'SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode
     FROM branch_expense_requests r
     INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
     LEFT JOIN state s ON s.ID = r.StateID
     WHERE r.Status = \'approved\' AND ' . fp_payment_scope_where($fpDb, $fpActor)
) as $office) {
    $fpOffices[(int) $office['ID']] = $office['OfficeName'] . ' (' . $office['OfficeCode'] . ')';
}
asort($fpOffices);

setNavigation($_SESSION['Roles'] ?? array());

function fp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payments - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-payable-table td, #fp-register-table td { vertical-align: middle; }
        .fp-purpose { max-width: 240px; white-space: normal; }
    </style>
</head>
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
                        <li class="breadcrumb-item">Finance &amp; Performance</li>
                        <li class="breadcrumb-item active">Payments</li>
                    </ol>
                    <?php if (!$fpCanRecord) { ?>
                        <div class="alert alert-warning">Your login is not linked to an employee record, so you can view payments but not record them.</div>
                    <?php } ?>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Payments&nbsp;<span class="fw-300">approval is not payment: a request is paid only when a payment is recorded here</span></h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#fp-tab-payable" role="tab">To be paid</a></li>
                                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fp-tab-register" id="fp-register-link" role="tab">Payment register</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="fp-tab-payable" role="tabpanel">
                                        <form id="fp-payable-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                            <div class="form-group col-md-4">
                                                <label for="fp-pf-q">Search</label>
                                                <input type="search" class="form-control form-control-sm" name="q" id="fp-pf-q" maxlength="100" placeholder="Expense no., purpose, requester or office">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fp-pf-type">Type</label>
                                                <select class="form-control form-control-sm" name="type" id="fp-pf-type">
                                                    <option value="">All types</option>
                                                    <?php foreach (fp_request_types() as $key => $label) { ?>
                                                        <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="fp-pf-office">Branch office</label>
                                                <select class="form-control form-control-sm" name="office" id="fp-pf-office">
                                                    <option value="">All offices</option>
                                                    <?php foreach ($fpOffices as $id => $label) { ?>
                                                        <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3 text-md-right">
                                                <div class="small text-muted">Unpaid balance in this list</div>
                                                <strong id="fp-payable-balance">-</strong>
                                            </div>
                                        </form>
                                        <table class="table table-bordered table-hover table-striped w-100" id="fp-payable-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Expense no.</th>
                                                    <th>Requester</th>
                                                    <th>Office</th>
                                                    <th>Purpose</th>
                                                    <th>Approved</th>
                                                    <th class="text-right">Approved amount</th>
                                                    <th class="text-right">Paid</th>
                                                    <th class="text-right">Balance</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>

                                    <div class="tab-pane fade" id="fp-tab-register" role="tabpanel">
                                        <form id="fp-register-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                            <div class="form-group col-md-3">
                                                <label for="fp-rf-q">Search</label>
                                                <input type="search" class="form-control form-control-sm" name="q" id="fp-rf-q" maxlength="100" placeholder="Payment no., expense no., reference or payee">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fp-rf-status">Status</label>
                                                <select class="form-control form-control-sm" name="status" id="fp-rf-status">
                                                    <option value="">All</option>
                                                    <?php foreach (fp_payment_statuses() as $key => $label) { ?>
                                                        <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fp-rf-mode">Mode</label>
                                                <select class="form-control form-control-sm" name="mode" id="fp-rf-mode">
                                                    <option value="">All modes</option>
                                                    <?php foreach (fp_payment_modes() as $key => $label) { ?>
                                                        <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fp-rf-office">Branch office</label>
                                                <select class="form-control form-control-sm" name="office" id="fp-rf-office">
                                                    <option value="">All offices</option>
                                                    <?php foreach ($fpOffices as $id => $label) { ?>
                                                        <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-1">
                                                <label for="fp-rf-from">Paid from</label>
                                                <input type="date" class="form-control form-control-sm" name="from" id="fp-rf-from">
                                            </div>
                                            <div class="form-group col-md-1">
                                                <label for="fp-rf-to">Paid to</label>
                                                <input type="date" class="form-control form-control-sm" name="to" id="fp-rf-to">
                                            </div>
                                            <div class="form-group col-md-1">
                                                <a href="#" class="btn btn-sm btn-outline-secondary btn-block" id="fp-register-export" title="Export the filtered register"><i class="fa-solid fa-file-csv"></i> CSV</a>
                                            </div>
                                        </form>
                                        <div class="small text-muted mb-2">Completed payments in this list: <strong id="fp-register-total">-</strong> (failed and reversed payments are not counted)</div>
                                        <table class="table table-bordered table-hover table-striped w-100" id="fp-register-table">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Payment no.</th>
                                                    <th>Date</th>
                                                    <th>Expense no.</th>
                                                    <th>Paid to</th>
                                                    <th class="text-right">Amount</th>
                                                    <th>Mode / reference</th>
                                                    <th>Status</th>
                                                    <th>Recorded by</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('partials/payment_modals.php'); ?>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_payments.js"></script>
</body>
</html>
