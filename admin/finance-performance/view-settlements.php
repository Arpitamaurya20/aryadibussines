<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_audit.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'settlements');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpTabs = array();
if ((int) $fpActor['employee_id'] > 0) {
    $fpTabs['mine'] = 'My advances';
}
if (fp_can($fpActor, 'approve') && (int) $fpActor['employee_id'] > 0) {
    $fpTabs['review'] = 'To review (branch)';
}
if (fp_can($fpActor, 'pay')) {
    $fpTabs['verify'] = 'To verify (Finance)';
}
if (fp_scope_beyond_self($fpActor['grants']['view'])) {
    $fpTabs['outstanding'] = 'Outstanding advances';
}
$fpFirstTab = key($fpTabs);
$fpCanExport = fp_can($fpActor, 'export');
$fpOffices = array();
if (isset($fpTabs['outstanding'])) {
    foreach (fp_db_rows(
        $fpDb,
        "SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode
         FROM branch_expense_requests r
         INNER JOIN branch_expense_offices o ON o.ID = r.OfficeID
         LEFT JOIN state st ON st.ID = r.StateID
         WHERE r.RequestType = 'advance' AND r.Status = 'approved' AND " . fp_scope_where($fpDb, $fpActor, 'view', array('employee_id' => 'r.EmployeeID', 'state_name' => 'st.StateName', 'office_id' => 'r.OfficeID'))
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
    <title>Settlements - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        .fp-settlement-table td { vertical-align: middle; }
        .fp-purpose { max-width: 240px; white-space: normal; }
        .fp-bucket { cursor: pointer; }
        .fp-bucket.active { border-color: #2196f3 !important; background: #f2f8ff; }
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
                        <li class="breadcrumb-item active">Settlements</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Advance settlements&nbsp;<span class="fw-300">show what a cash advance was spent on, return what was not spent</span></h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <?php if (!$fpTabs) { ?>
                                    <p class="text-muted mb-0">There is nothing for you here.</p>
                                <?php } else { ?>
                                <ul class="nav nav-tabs mb-3" role="tablist" id="fp-settlement-tabs">
                                    <?php foreach ($fpTabs as $key => $label) { ?>
                                        <li class="nav-item"><a class="nav-link<?php echo $key === $fpFirstTab ? ' active' : ''; ?>" data-toggle="tab" href="#fp-tab-<?php echo fp_h($key); ?>" data-tab="<?php echo fp_h($key); ?>" role="tab"><?php echo fp_h($label); ?></a></li>
                                    <?php } ?>
                                </ul>
                                <div class="tab-content">
                                    <?php if (isset($fpTabs['mine'])) { ?>
                                        <div class="tab-pane fade<?php echo $fpFirstTab === 'mine' ? ' show active' : ''; ?>" id="fp-tab-mine" role="tabpanel">
                                            <p class="small text-muted">Cash advances paid to you. Open one to list what you spent and attach the bills. Settle within <?php echo (int) fp_settlement_due_days(); ?> days of receiving the money.</p>
                                            <table class="table table-bordered table-hover table-striped w-100 fp-settlement-table" id="fp-mine-table">
                                                <thead class="thead-light">
                                                    <tr><th>Advance</th><th>Purpose</th><th class="text-right">Paid to you</th><th class="text-right">Accepted</th><th class="text-right">Returned / extra</th><th>Position</th><th>Status</th><th></th></tr>
                                                </thead>
                                            </table>
                                        </div>
                                    <?php } ?>
                                    <?php foreach (array('review' => 'Settlements your branch must check before Finance verifies them. You cannot review your own.', 'verify' => 'Settlements confirmed by the branch, waiting for a Finance person to accept or reject each line. The branch reviewer cannot also verify.') as $view => $help) {
                                        if (!isset($fpTabs[$view])) { continue; } ?>
                                        <div class="tab-pane fade<?php echo $fpFirstTab === $view ? ' show active' : ''; ?>" id="fp-tab-<?php echo $view; ?>" role="tabpanel">
                                            <p class="small text-muted"><?php echo fp_h($help); ?></p>
                                            <table class="table table-bordered table-hover table-striped w-100 fp-settlement-table" id="fp-<?php echo $view; ?>-table">
                                                <thead class="thead-light">
                                                    <tr><th>Settlement</th><th>Advance</th><th>Employee</th><th>Office</th><th class="text-right">Claimed</th><th>Lines</th><th>Submitted</th><th></th></tr>
                                                </thead>
                                            </table>
                                        </div>
                                    <?php } ?>
                                    <?php if (isset($fpTabs['outstanding'])) { ?>
                                        <div class="tab-pane fade<?php echo $fpFirstTab === 'outstanding' ? ' show active' : ''; ?>" id="fp-tab-outstanding" role="tabpanel">
                                            <div class="row mb-2" id="fp-buckets">
                                                <?php foreach (fp_settlement_aging_buckets() as $key => $label) { ?>
                                                    <div class="col-6 col-md-2 mb-2"><div class="border rounded p-2 text-center fp-bucket" data-bucket="<?php echo fp_h($key); ?>"><div class="small text-muted"><?php echo fp_h($label); ?></div><strong data-total="<?php echo fp_h($key); ?>">-</strong></div></div>
                                                <?php } ?>
                                                <div class="col-6 col-md-2 mb-2"><div class="border rounded p-2 text-center"><div class="small text-muted">Held by employees</div><strong id="fp-total-holding">-</strong></div></div>
                                                <div class="col-6 col-md-2 mb-2"><div class="border rounded p-2 text-center"><div class="small text-muted">Owed to employees</div><strong id="fp-total-owed">-</strong></div></div>
                                            </div>
                                            <form id="fp-out-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                                <input type="hidden" name="bucket" id="fp-of-bucket" value="">
                                                <div class="form-group col-md-4">
                                                    <label for="fp-of-q">Search</label>
                                                    <input type="search" class="form-control form-control-sm" name="q" id="fp-of-q" maxlength="100" placeholder="Expense no., purpose, employee or office">
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-of-status">Show</label>
                                                    <select class="form-control form-control-sm" name="status" id="fp-of-status">
                                                        <option value="open">Not yet closed</option>
                                                        <option value="settled">Closed</option>
                                                        <option value="all">All</option>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="fp-of-office">Branch office</label>
                                                    <select class="form-control form-control-sm" name="office" id="fp-of-office">
                                                        <option value="">All offices</option>
                                                        <?php foreach ($fpOffices as $id => $label) { ?>
                                                            <option value="<?php echo (int) $id; ?>"><?php echo fp_h($label); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group col-md-3 text-md-right">
                                                    <?php if ($fpCanExport) { ?>
                                                        <a href="#" class="btn btn-sm btn-outline-secondary" id="fp-out-export"><i class="fa-solid fa-file-csv mr-1"></i>Export CSV</a>
                                                    <?php } ?>
                                                </div>
                                            </form>
                                            <p class="small text-muted">Age counts from the first payment of the advance. Advances still held after <span id="fp-due-days"><?php echo (int) fp_settlement_due_days(); ?></span> days are overdue.</p>
                                            <table class="table table-bordered table-hover table-striped w-100 fp-settlement-table" id="fp-out-table">
                                                <thead class="thead-light">
                                                    <tr><th>Advance</th><th>Employee</th><th>Office</th><th class="text-right">Paid out</th><th class="text-right">Accepted</th><th class="text-right">Returned / extra</th><th>Position</th><th>Age</th><th>Status</th></tr>
                                                </thead>
                                            </table>
                                        </div>
                                    <?php } ?>
                                </div>
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
    <script src="../js/modules/fp_settlements.js"></script>
</body>
</html>
