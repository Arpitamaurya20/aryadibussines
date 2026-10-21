<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_audit.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'direct_expenses');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpCanCreate = fp_is_requester($fpActor);
$fpCanExport = fp_can($fpActor, 'export');
$fpOffices = array();
foreach (fp_db_rows(
    $fpDb,
    "SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode
     FROM branch_expense_requests r
     INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
     LEFT JOIN state s ON s.ID = r.StateID
     WHERE r.RequestType = 'direct' AND " . fp_scope_where($fpDb, $fpActor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'))
) as $office) {
    $fpOffices[(int) $office['ID']] = $office['OfficeName'] . ' (' . $office['OfficeCode'] . ')';
}
asort($fpOffices);
$fpSoonDays = fp_direct_due_soon_days();

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
    <title>Direct Expenses - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-direct-table td { vertical-align: middle; }
        .fp-purpose { max-width: 240px; white-space: normal; }
        .fp-card { cursor: pointer; }
        .fp-card.active { border-color: #2196f3 !important; background: #f2f8ff; }
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
                        <li class="breadcrumb-item active">Direct Expenses</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Direct expenses&nbsp;<span class="fw-300">bills Head Office pays straight to vendors, landlords and utilities</span></h2>
                            <div class="panel-toolbar">
                                <a href="view-recurring-expenses.php" class="btn btn-outline-secondary btn-sm mr-2"><i class="fa-solid fa-arrows-rotate mr-1"></i>Recurring expenses</a>
                                <?php if ($fpCanCreate) { ?>
                                    <a href="view-expense-request-form.php?type=direct" class="btn btn-primary btn-sm mr-3"><i class="fa-solid fa-plus mr-1"></i>New direct expense</a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <p class="small text-muted">Each bill is approved through the normal approval route and then paid by Head Office Finance (part payments allowed). Nothing is paid automatically.</p>
                                <div class="row mb-2" id="fp-cards">
                                    <div class="col-6 col-md-3 mb-2"><div class="border rounded p-2 text-center fp-card" data-due="overdue"><div class="small text-muted">Overdue</div><strong class="text-danger" id="fp-sum-overdue">-</strong><div class="small text-muted" id="fp-sum-overdue-amount"></div></div></div>
                                    <div class="col-6 col-md-3 mb-2"><div class="border rounded p-2 text-center fp-card" data-due="due_soon"><div class="small text-muted">Due in the next <?php echo (int) $fpSoonDays; ?> days</div><strong class="text-warning" id="fp-sum-soon">-</strong></div></div>
                                    <div class="col-6 col-md-3 mb-2"><div class="border rounded p-2 text-center fp-card active" data-due="open"><div class="small text-muted">Not fully paid</div><strong id="fp-sum-open">-</strong><div class="small text-muted" id="fp-sum-open-amount"></div></div></div>
                                    <div class="col-6 col-md-3 mb-2"><div class="border rounded p-2 text-center fp-card" data-due=""><div class="small text-muted">Show</div><strong>All</strong></div></div>
                                </div>
                                <form id="fp-direct-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                    <input type="hidden" name="due" id="fp-df-due" value="open">
                                    <div class="form-group col-md-3">
                                        <label for="fp-df-q">Search</label>
                                        <input type="search" class="form-control form-control-sm" name="q" id="fp-df-q" maxlength="100" placeholder="Expense no., vendor, purpose, office, rule">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-df-status">Request status</label>
                                        <select class="form-control form-control-sm" name="status" id="fp-df-status">
                                            <option value="">Any</option>
                                            <?php foreach (fp_request_statuses() as $key => $label) { ?>
                                                <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-df-source">Source</label>
                                        <select class="form-control form-control-sm" name="source" id="fp-df-source">
                                            <option value="">All</option>
                                            <option value="recurring">From recurring expenses</option>
                                            <option value="one_off">One-off bills</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-df-office">Branch office</label>
                                        <select class="form-control form-control-sm" name="office" id="fp-df-office">
                                            <option value="">All offices</option>
                                            <?php foreach ($fpOffices as $id => $label) { ?>
                                                <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-1">
                                        <label for="fp-df-from">Due from</label>
                                        <input type="date" class="form-control form-control-sm" name="due_from" id="fp-df-from">
                                    </div>
                                    <div class="form-group col-md-1">
                                        <label for="fp-df-to">Due to</label>
                                        <input type="date" class="form-control form-control-sm" name="due_to" id="fp-df-to">
                                    </div>
                                    <div class="form-group col-md-1 text-md-right">
                                        <?php if ($fpCanExport) { ?>
                                            <a href="#" class="btn btn-sm btn-outline-secondary" id="fp-direct-export" title="Export CSV"><i class="fa-solid fa-file-csv"></i> CSV</a>
                                        <?php } ?>
                                    </div>
                                </form>
                                <table class="table table-bordered table-hover table-striped w-100" id="fp-direct-table">
                                    <thead class="thead-light">
                                        <tr><th>Expense</th><th>Pay to</th><th>Office / requester</th><th>Due date</th><th class="text-right">Amount</th><th class="text-right">Paid</th><th class="text-right">Balance</th><th>Status</th><th></th></tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
                <div class="page-content-overlay" data-action="toggle" data-class="mobile-nav-on"></div>
                <?php include('../includes/common_footer.php'); ?>
            </div>
        </div>
    </div>
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_direct_expenses.js"></script>
</body>
</html>
