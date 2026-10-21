<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
require_once('controller/fp_audit.php');
require_once('controller/fp_requests.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'recurring_expenses');
$UserType = $fpActor['user_type'];

$fpDb = fp_db_connect();
$fpCanCreate = fp_recurring_can_create($fpActor) && fp_recurring_offices_for_actor($fpDb, $fpActor);
$fpFilter = fp_recurring_list_filter($fpDb, $fpActor, array());
$fpOffices = array();
foreach (fp_db_rows($fpDb, 'SELECT DISTINCT o.ID, o.OfficeName, o.OfficeCode' . fp_recurring_list_from() . 'WHERE ' . $fpFilter['base']) as $office) {
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
    <title>Recurring Expenses - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <style>
        #fp-rule-table td { vertical-align: middle; }
        .fp-dl dt { font-weight: 600; color: #666; font-size: 12px; }
        .fp-dl dd { margin-bottom: 8px; }
        .fp-history li { margin-bottom: 6px; }
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
                        <li class="breadcrumb-item active">Recurring Expenses</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Recurring expenses&nbsp;<span class="fw-300">rent, internet, electricity and other regular bills</span></h2>
                            <div class="panel-toolbar">
                                <a href="view-direct-expenses.php" class="btn btn-outline-secondary btn-sm mr-2"><i class="fa-solid fa-file-invoice mr-1"></i>Direct expenses</a>
                                <?php if ($fpCanCreate) { ?>
                                    <button type="button" class="btn btn-primary btn-sm mr-3" id="fp-rule-new"><i class="fa-solid fa-plus mr-1"></i>New recurring expense</button>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <p class="small text-muted">For every period a <strong>draft</strong> direct expense is prepared a few days before the bill is due, and its owner is notified.
                                    The owner checks the amount, attaches the bill and submits it; it is then approved and paid like any other direct expense. Nothing is submitted or paid automatically.</p>
                                <form id="fp-rule-filters" class="form-row align-items-end mb-2" onsubmit="return false;">
                                    <div class="form-group col-md-4">
                                        <label for="fp-rf-q">Search</label>
                                        <input type="search" class="form-control form-control-sm" name="q" id="fp-rf-q" maxlength="100" placeholder="Rule no., name, vendor, office or owner">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-rf-status">Status</label>
                                        <select class="form-control form-control-sm" name="status" id="fp-rf-status">
                                            <option value="">All</option>
                                            <?php foreach (fp_recurring_statuses() as $key => $label) { ?>
                                                <option value="<?php echo fp_h($key); ?>"<?php echo $key === 'active' ? ' selected' : ''; ?>><?php echo fp_h($label); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="fp-rf-frequency">Frequency</label>
                                        <select class="form-control form-control-sm" name="frequency" id="fp-rf-frequency">
                                            <option value="">All</option>
                                            <?php foreach (fp_recurring_frequencies() as $key => $frequency) { ?>
                                                <option value="<?php echo fp_h($key); ?>"><?php echo fp_h($frequency['label']); ?></option>
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
                                    <div class="form-group col-md-2">
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input" name="attention" id="fp-rf-attention" value="1">
                                            <label class="custom-control-label" for="fp-rf-attention">Needs attention only</label>
                                        </div>
                                    </div>
                                </form>
                                <table class="table table-bordered table-hover table-striped w-100" id="fp-rule-table">
                                    <thead class="thead-light">
                                        <tr><th>Recurring expense</th><th>Pay to</th><th>Office / owner</th><th class="text-right">Amount</th><th>Frequency</th><th>Next bill</th><th>Drafts</th><th>Status</th><th></th></tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-rule-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <form class="modal-content" id="fp-rule-form" onsubmit="return false;" novalidate>
                                <input type="hidden" name="ID" value="0">
                                <input type="hidden" name="RowVersion" value="0">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="fp-rule-title">New recurring expense</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info py-2 small" id="fp-rule-locked" style="display:none">Drafts were already created from this rule, so its frequency and start month cannot change. Changes apply to future drafts only.</div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="fp-rule-name">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="Title" id="fp-rule-name" maxlength="150" placeholder="e.g. Office rent - Pune">
                                            <div class="invalid-feedback" data-for="Title"></div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="fp-rule-office">Branch office <span class="text-danger">*</span></label>
                                            <select class="form-control" name="OfficeID" id="fp-rule-office"></select>
                                            <div class="invalid-feedback" data-for="OfficeID"></div>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="fp-rule-owner">Owner (checks and submits the bills) <span class="text-danger">*</span></label>
                                            <select class="form-control" name="OwnerEmployeeID" id="fp-rule-owner"></select>
                                            <div class="invalid-feedback" data-for="OwnerEmployeeID"></div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label for="fp-rule-category">Category <span class="text-danger">*</span></label>
                                            <select class="form-control" name="CategoryID" id="fp-rule-category"></select>
                                            <small class="form-text text-muted">Only categories that allow recurring expenses.</small>
                                            <div class="invalid-feedback" data-for="CategoryID"></div>
                                        </div>
                                        <div class="form-group col-md-8">
                                            <label for="fp-rule-line">Line description on each bill</label>
                                            <input type="text" class="form-control" name="LineDescription" id="fp-rule-line" maxlength="500" placeholder="Defaults to the name; the period is added automatically">
                                            <div class="invalid-feedback" data-for="LineDescription"></div>
                                        </div>
                                    </div>
                                    <div class="border rounded p-3 mb-3">
                                        <h6 class="mb-2">Pay to</h6>
                                        <div id="fp-rule-vendor"></div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="fp-rule-amount">Amount per period (INR) <span class="text-danger">*</span></label>
                                            <input type="text" inputmode="decimal" class="form-control" name="Amount" id="fp-rule-amount" maxlength="14">
                                            <div class="invalid-feedback" data-for="Amount"></div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fp-rule-amount-type">Amount is</label>
                                            <select class="form-control" name="AmountType" id="fp-rule-amount-type"></select>
                                            <div class="invalid-feedback" data-for="AmountType"></div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fp-rule-frequency">Frequency <span class="text-danger">*</span></label>
                                            <select class="form-control" name="Frequency" id="fp-rule-frequency"></select>
                                            <div class="invalid-feedback" data-for="Frequency"></div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="fp-rule-start">First period starts (month) <span class="text-danger">*</span></label>
                                            <input type="month" class="form-control" id="fp-rule-start">
                                            <input type="hidden" name="StartDate" value="">
                                            <div class="invalid-feedback d-block" data-for="StartDate"></div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="fp-rule-due-day">Bill due on day <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="DueDay" id="fp-rule-due-day" min="1" max="28" value="5">
                                            <small class="form-text text-muted">1-28 of the month.</small>
                                            <div class="invalid-feedback" data-for="DueDay"></div>
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="fp-rule-timing">The bill is due</label>
                                            <select class="form-control" name="DueTiming" id="fp-rule-timing"></select>
                                            <div class="invalid-feedback" data-for="DueTiming"></div>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="fp-rule-reminder">Prepare draft</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="ReminderDays" id="fp-rule-reminder" min="0" max="60" value="7">
                                                <div class="input-group-append"><span class="input-group-text">days before</span></div>
                                            </div>
                                            <div class="invalid-feedback d-block" data-for="ReminderDays"></div>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="fp-rule-end">Ends on (optional)</label>
                                            <input type="date" class="form-control" name="EndDate" id="fp-rule-end">
                                            <div class="invalid-feedback" data-for="EndDate"></div>
                                        </div>
                                    </div>
                                    <div class="alert alert-light border small py-2" id="fp-rule-preview"></div>
                                    <div class="form-group mb-0">
                                        <label for="fp-rule-notes">Notes</label>
                                        <textarea class="form-control" name="Notes" id="fp-rule-notes" rows="2" maxlength="1000" placeholder="e.g. agreement valid till March 2027, 5% increase each April"></textarea>
                                        <div class="invalid-feedback" data-for="Notes"></div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary" id="fp-rule-save">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="fp-detail-title"></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body" id="fp-detail-body"></div>
                                <div class="modal-footer" id="fp-detail-actions"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-reason-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="fp-reason-title">Confirm</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <p id="fp-reason-text" class="mb-2"></p>
                                    <label for="fp-reason-input" id="fp-reason-label">Reason</label>
                                    <textarea class="form-control" id="fp-reason-input" rows="3" maxlength="500"></textarea>
                                    <div class="invalid-feedback">Please give a reason (at least 5 characters).</div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Back</button>
                                    <button type="button" class="btn btn-danger" id="fp-reason-confirm">Confirm</button>
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
    <?php include('../includes/common_modules.php'); include('../includes/common_scripts.php'); ?>
    <script src="../js/datagrid/datatables/datatables.bundle.js"></script>
    <script src="../js/modules/fp_common.js"></script>
    <script src="../js/modules/fp_vendor_picker.js"></script>
    <script src="../js/modules/fp_recurring_expenses.js"></script>
</body>
</html>
