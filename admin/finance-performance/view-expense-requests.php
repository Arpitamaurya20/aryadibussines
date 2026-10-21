<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'my_requests');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpIsRequester = fp_is_requester($fpActor);
$fpCanSeeOthers = fp_can($fpActor, 'view') && fp_scope_beyond_self($fpActor['grants']['view']);
$fpCanExport = fp_can($fpActor, 'export');
$fpDefaultView = $fpIsRequester ? 'mine' : 'all';

$fpOffices = array();
foreach (fp_request_offices_for_actor($fpDb, $fpActor) as $office) {
    $fpOffices[$office['ID']] = $office['OfficeName'] . ' (' . $office['OfficeCode'] . ')';
}
$fpVisibleWhere = fp_scope_where($fpDb, $fpActor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'));
$fpMine = (int) $fpActor['employee_id'];
foreach (fp_db_rows(
    $fpDb,
    'SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode
     FROM branch_expense_requests r
     INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
     LEFT JOIN state s ON s.ID = r.StateID
     WHERE (' . $fpVisibleWhere . ") AND (r.Status <> 'draft' OR r.EmployeeID = " . $fpMine . ')'
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
    <title>My Requests - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-request-table td { vertical-align: middle; }
        #fp-request-table .fp-purpose { max-width: 280px; white-space: normal; }
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
                        <li class="breadcrumb-item">Expense Booking</li>
                        <li class="breadcrumb-item active">My Requests</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Expense requests</h2>
                            <div class="panel-toolbar">
                                <?php if ($fpCanExport) { ?>
                                    <a href="#" class="btn btn-outline-secondary btn-sm mr-2" id="fp-export"><i class="fa-solid fa-file-csv mr-1"></i>Export CSV</a>
                                <?php } ?>
                                <?php if ($fpIsRequester) { ?>
                                    <a href="view-expense-request-form.php" class="btn btn-primary btn-sm mr-3"><i class="fa-solid fa-plus mr-1"></i>New request</a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <form id="fp-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                    <?php if ($fpCanSeeOthers) { ?>
                                        <div class="form-group col-md-2">
                                            <label for="fp-f-view">Show</label>
                                            <select class="form-control form-control-sm" name="view" id="fp-f-view">
                                                <?php if ($fpIsRequester) { ?><option value="mine">My requests</option><?php } ?>
                                                <option value="all"<?php echo $fpDefaultView === 'all' ? ' selected' : ''; ?>>All I can see</option>
                                            </select>
                                        </div>
                                    <?php } else { ?>
                                        <input type="hidden" name="view" value="<?php echo fp_h($fpDefaultView); ?>">
                                    <?php } ?>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-status">Status</label>
                                        <select class="form-control form-control-sm" name="status" id="fp-f-status">
                                            <option value="">All statuses</option>
                                            <?php foreach (fp_request_statuses() as $key => $label) { ?>
                                                <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-type">Type</label>
                                        <select class="form-control form-control-sm" name="type" id="fp-f-type">
                                            <option value="">All types</option>
                                            <?php foreach (fp_request_types() as $key => $label) { ?>
                                                <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-office">Branch office</label>
                                        <select class="form-control form-control-sm" name="office" id="fp-f-office">
                                            <option value="">All offices</option>
                                            <?php foreach ($fpOffices as $id => $label) { ?>
                                                <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-from">Created from</label>
                                        <input type="date" class="form-control form-control-sm" name="from" id="fp-f-from">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-to">Created to</label>
                                        <input type="date" class="form-control form-control-sm" name="to" id="fp-f-to">
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="fp-f-q">Search</label>
                                        <input type="search" class="form-control form-control-sm" name="q" id="fp-f-q" maxlength="100" placeholder="Expense no., purpose, requester or office">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="fp-f-reset">Clear filters</button>
                                    </div>
                                </form>

                                <table class="table table-bordered table-hover table-striped w-100" id="fp-request-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Expense no.</th>
                                            <th>Type</th>
                                            <th>Requester</th>
                                            <th>Office</th>
                                            <th>Purpose</th>
                                            <th class="text-right">Amount</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
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
    <script src="../js/modules/fp_my_requests.js"></script>
</body>
</html>
