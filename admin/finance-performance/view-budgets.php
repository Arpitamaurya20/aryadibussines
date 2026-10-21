<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'budgets');
$UserType = $fpActor['user_type'];

setNavigation($_SESSION['Roles'] ?? array());

function fp_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$fpCurrentFy = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Budgets - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        #fp-budget-table td { vertical-align: middle; font-size: 12.5px; }
        #fp-budget-table .fp-num { text-align: right; white-space: nowrap; }
        .fp-bar { height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden; display: flex; margin-top: 3px; min-width: 90px; }
        .fp-bar span { display: block; height: 100%; }
        .fp-bar .fp-bar-used { background: #1e7e34; }
        .fp-bar .fp-bar-reserved { background: #8fb8e8; }
        .fp-bar.fp-over .fp-bar-used { background: #c82333; }
        .fp-history td { font-size: 12px; vertical-align: top; }
        .fp-period-fields { display: none; }
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
                        <li class="breadcrumb-item active">Budgets</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Budgets</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-info btn-sm mr-3" id="fp-budget-add"><i class="fa-solid fa-plus mr-1"></i>Add budget</button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="form-row mb-2">
                                    <div class="col-md-3">
                                        <label class="small mb-1" for="fp-filter-company">Company</label>
                                        <select class="form-control form-control-sm" id="fp-filter-company"><option value="all">All</option></select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-1" for="fp-filter-level">Level</label>
                                        <select class="form-control form-control-sm" id="fp-filter-level">
                                            <option value="all">All</option>
                                            <option value="company">Company</option>
                                            <option value="state">State</option>
                                            <option value="office">Branch office</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-1" for="fp-filter-category">Category</label>
                                        <select class="form-control form-control-sm" id="fp-filter-category"><option value="all">All</option><option value="overall">Overall budgets only</option></select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-1" for="fp-filter-period">Period</label>
                                        <select class="form-control form-control-sm" id="fp-filter-period">
                                            <option value="current">Current</option>
                                            <option value="upcoming">Upcoming</option>
                                            <option value="past">Past</option>
                                            <option value="all">All</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="small mb-1" for="fp-filter-status">Status</label>
                                        <select class="form-control form-control-sm" id="fp-filter-status">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                            <option value="all">All</option>
                                        </select>
                                    </div>
                                </div>
                                <table id="fp-budget-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Scope</th>
                                            <th>Category</th>
                                            <th>Period</th>
                                            <th class="text-right">Budget</th>
                                            <th class="text-right">Pending approval</th>
                                            <th class="text-right">Approved</th>
                                            <th class="text-right">Recognised</th>
                                            <th class="text-right">Available</th>
                                            <th>If exceeded</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div class="small text-muted mt-2">
                                    <p class="mb-1"><strong>How budgets work.</strong> A budget covers a company, a state or a branch office for a month, quarter, financial year or custom period.
                                        A budget without a category is the overall cap for that scope. Category budgets are carved out of it: they must sit inside its period and together cannot exceed it.
                                        Money reserved for category budgets is not available to other categories, so <em>Available</em> on an overall budget is what is left after committed spend and unused category reservations.</p>
                                    <p class="mb-1">Committed spend = pending approval + approved. Recognised = approved reimbursements and direct expenses, plus expenses Finance accepted when verifying an advance settlement.
                                        An advance itself is never an expense: it is committed until it is settled, then only the accepted expenses remain.
                                        A line counts in the period of its expense date; advances without one count on the date they were submitted.</p>
                                    <p class="mb-0">When a request would exceed a budget, <em>Warn</em> lets it through with a warning for the requester and approvers; <em>Block</em> stops submission.
                                        Amounts change only through <em>Adjust</em> with a reason. Budgets are never deleted; deactivate them instead. Every change is in the history.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-budget-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-budget-modal-title">Add budget</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-budget-form" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="ID" id="fp-bud-id" value="0">
                                        <input type="hidden" name="RowVersion" id="fp-bud-version" value="0">
                                        <div id="fp-bud-locked" class="alert alert-light border small" style="display:none"></div>
                                        <div id="fp-bud-new-fields">
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="fp-bud-level">Level <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="Level" id="fp-bud-level">
                                                        <option value="company">Company</option>
                                                        <option value="state">State</option>
                                                        <option value="office">Branch office</option>
                                                    </select>
                                                    <div class="invalid-feedback" data-for="Level"></div>
                                                </div>
                                                <div class="form-group col-md-8">
                                                    <label for="fp-bud-company">Legal entity <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="CompanyID" id="fp-bud-company"></select>
                                                    <div class="invalid-feedback" data-for="CompanyID"></div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-6" id="fp-bud-state-group">
                                                    <label for="fp-bud-state">State <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="StateID" id="fp-bud-state"></select>
                                                    <div class="invalid-feedback" data-for="StateID"></div>
                                                </div>
                                                <div class="form-group col-md-6" id="fp-bud-office-group">
                                                    <label for="fp-bud-office">Branch office <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="OfficeID" id="fp-bud-office"></select>
                                                    <div class="invalid-feedback" data-for="OfficeID"></div>
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label for="fp-bud-category">Category</label>
                                                    <select class="form-control" name="CategoryID" id="fp-bud-category"></select>
                                                    <div class="invalid-feedback" data-for="CategoryID"></div>
                                                    <small class="form-text text-muted">Leave as "All categories" for the overall budget.</small>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="fp-bud-period-type">Period <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="PeriodType" id="fp-bud-period-type">
                                                        <option value="month">Month</option>
                                                        <option value="quarter">Quarter</option>
                                                        <option value="year">Financial year</option>
                                                        <option value="custom">Custom period</option>
                                                    </select>
                                                    <div class="invalid-feedback" data-for="PeriodType"></div>
                                                </div>
                                                <div class="form-group col-md-4 fp-period-fields" data-period="month">
                                                    <label for="fp-bud-month">Month <span class="text-danger">*</span></label>
                                                    <input type="month" class="form-control" name="PeriodMonth" id="fp-bud-month">
                                                    <div class="invalid-feedback" data-for="PeriodMonth"></div>
                                                </div>
                                                <div class="form-group col-md-4 fp-period-fields" data-period="quarter year">
                                                    <label for="fp-bud-fy">Financial year <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="FinancialYear" id="fp-bud-fy">
                                                        <?php for ($fy = $fpCurrentFy - 1; $fy <= $fpCurrentFy + 2; $fy++) { ?>
                                                            <option value="<?php echo $fy; ?>"<?php echo $fy === $fpCurrentFy ? ' selected' : ''; ?>>FY <?php echo $fy . '-' . substr((string) ($fy + 1), -2); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <div class="invalid-feedback" data-for="FinancialYear"></div>
                                                </div>
                                                <div class="form-group col-md-4 fp-period-fields" data-period="quarter">
                                                    <label for="fp-bud-quarter">Quarter <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="Quarter" id="fp-bud-quarter">
                                                        <option value="1">Q1 (Apr-Jun)</option>
                                                        <option value="2">Q2 (Jul-Sep)</option>
                                                        <option value="3">Q3 (Oct-Dec)</option>
                                                        <option value="4">Q4 (Jan-Mar)</option>
                                                    </select>
                                                    <div class="invalid-feedback" data-for="Quarter"></div>
                                                </div>
                                                <div class="form-group col-md-4 fp-period-fields" data-period="custom">
                                                    <label for="fp-bud-from">From <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" name="PeriodFrom" id="fp-bud-from">
                                                    <div class="invalid-feedback" data-for="PeriodFrom"></div>
                                                </div>
                                                <div class="form-group col-md-4 fp-period-fields" data-period="custom">
                                                    <label for="fp-bud-to">To <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" name="PeriodTo" id="fp-bud-to">
                                                    <div class="invalid-feedback" data-for="PeriodTo"></div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-4">
                                                    <label for="fp-bud-amount">Budget amount (INR) <span class="text-danger">*</span></label>
                                                    <input type="text" inputmode="decimal" class="form-control" name="Amount" id="fp-bud-amount" maxlength="16" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="Amount"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="fp-bud-enforcement">If exceeded <span class="text-danger">*</span></label>
                                                <select class="form-control" name="Enforcement" id="fp-bud-enforcement">
                                                    <option value="warn">Warn, allow submission</option>
                                                    <option value="block">Block submission</option>
                                                </select>
                                                <div class="invalid-feedback" data-for="Enforcement"></div>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label for="fp-bud-notes">Notes</label>
                                                <input type="text" class="form-control" name="Notes" id="fp-bud-notes" maxlength="500" autocomplete="off">
                                                <div class="invalid-feedback" data-for="Notes"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="fp-budget-save">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-adjust-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">Adjust budget amount</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-adjust-form" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="ID" id="fp-adj-id">
                                        <input type="hidden" name="RowVersion" id="fp-adj-version">
                                        <p class="mb-2" id="fp-adj-label"></p>
                                        <p class="small text-muted mb-3" id="fp-adj-current"></p>
                                        <div class="form-group">
                                            <label for="fp-adj-amount">New amount (INR) <span class="text-danger">*</span></label>
                                            <input type="text" inputmode="decimal" class="form-control" name="NewAmount" id="fp-adj-amount" maxlength="16" autocomplete="off">
                                            <div class="invalid-feedback" data-for="NewAmount"></div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="fp-adj-reason">Reason (required)</label>
                                            <textarea class="form-control" name="Reason" id="fp-adj-reason" rows="3" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Reason"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="fp-adjust-save">Save adjustment</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-reason-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
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
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger" id="fp-reason-confirm">Confirm</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-history-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-history-title">History</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <h6>Amount changes</h6>
                                    <table class="table table-sm table-bordered fp-history mb-3">
                                        <thead class="thead-light"><tr><th style="width:150px">When</th><th style="width:120px">By</th><th class="text-right">From</th><th class="text-right">To</th><th class="text-right">Change</th><th>Reason</th></tr></thead>
                                        <tbody id="fp-adjustments-body"></tbody>
                                    </table>
                                    <h6>All changes</h6>
                                    <table class="table table-sm table-bordered fp-history mb-0">
                                        <thead class="thead-light"><tr><th style="width:150px">When</th><th style="width:120px">By</th><th style="width:110px">Action</th><th>Changes</th><th style="width:220px">Reason</th></tr></thead>
                                        <tbody id="fp-history-body"></tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
    <script src="../js/modules/fp_budgets.js"></script>
</body>
</html>
