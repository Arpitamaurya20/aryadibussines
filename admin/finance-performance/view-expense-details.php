<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'expense_details');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpCanSeeOthers = fp_scope_beyond_self($fpActor['grants']['view']);
$fpIsRequester = fp_is_requester($fpActor);
$fpCanAuditAny = fp_can($fpActor, 'audit');

$fpOffices = array();
if ($fpCanSeeOthers) {
    $fpVisibleWhere = fp_scope_where($fpDb, $fpActor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 's.StateName', 'office_id' => 'r.OfficeID'));
    foreach (fp_db_rows(
        $fpDb,
        'SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode
         FROM branch_expense_requests r
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN state s ON s.ID = r.StateID
         WHERE (' . $fpVisibleWhere . ") AND (r.Status <> 'draft' OR r.EmployeeID = " . (int) $fpActor['employee_id'] . ')'
    ) as $office) {
        $fpOffices[(int) $office['ID']] = $office['OfficeName'] . ' (' . $office['OfficeCode'] . ')';
    }
    asort($fpOffices);
}

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
    <title>Expense Details - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-details-table td { vertical-align: middle; }
        #fp-details-table .fp-purpose { max-width: 260px; white-space: normal; }
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
                        <li class="breadcrumb-item active">Expense Details</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Expense Details&nbsp;<span class="fw-300">find a request and open its full record and timeline</span></h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <p class="text-muted small mb-3">
                                    Each request page shows the details, line items, documents, approval history, payments, settlement and a timeline of every change (who, what, when, why).
                                    <?php if ($fpCanAuditAny) { ?>You can also see the technical audit record (IP address, device, all stored values) and export it, for requests in your area.<?php } ?>
                                </p>
                                <form id="fp-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                    <input type="hidden" name="view" value="<?php echo $fpCanSeeOthers ? 'all' : 'mine'; ?>">
                                    <input type="hidden" name="activity" value="1">
                                    <div class="form-group col-md-4">
                                        <label for="fp-f-q">Search</label>
                                        <input type="search" class="form-control form-control-sm" name="q" id="fp-f-q" maxlength="100" placeholder="Expense no., purpose, requester or office" autofocus>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-status">Status</label>
                                        <select class="form-control form-control-sm" name="status" id="fp-f-status">
                                            <option value="">All statuses</option>
                                            <?php foreach (fp_request_statuses() as $key => $label) { ?>
                                                <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <?php if ($fpOffices) { ?>
                                        <div class="form-group col-md-2">
                                            <label for="fp-f-office">Branch office</label>
                                            <select class="form-control form-control-sm" name="office" id="fp-f-office">
                                                <option value="">All offices</option>
                                                <?php foreach ($fpOffices as $id => $label) { ?>
                                                    <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    <?php } ?>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-from">Created from</label>
                                        <input type="date" class="form-control form-control-sm" name="from" id="fp-f-from">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-f-to">Created to</label>
                                        <input type="date" class="form-control form-control-sm" name="to" id="fp-f-to">
                                    </div>
                                </form>

                                <table class="table table-bordered table-hover table-striped w-100" id="fp-details-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Expense no.</th>
                                            <th>Requester</th>
                                            <th>Office</th>
                                            <th>Purpose</th>
                                            <th class="text-right">Amount</th>
                                            <th>Status</th>
                                            <th>Last activity</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                                <?php if (!$fpCanSeeOthers && !$fpIsRequester) { ?>
                                    <p class="small text-muted mb-0">Requests you approved are listed under Approvals &gt; My decisions.</p>
                                <?php } ?>
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
    <script src="../js/modules/fp_expense_details.js"></script>
</body>
</html>
