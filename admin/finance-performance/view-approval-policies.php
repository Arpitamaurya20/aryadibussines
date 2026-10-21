<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_menu_page($conn, 'approval_policies');
$UserType = $fpActor['user_type'];

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
    <title>Approval Policies - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        #fp-policy-table td, #fp-delegation-table td { vertical-align: top; font-size: 12.5px; }
        .fp-stage-list { margin: 0; padding-left: 0; list-style: none; }
        .fp-stage-list li { white-space: normal; }
        .fp-stage-row { border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 10px 2px; margin-bottom: 10px; background: #fafbfc; }
        .fp-stage-row .form-group { margin-bottom: 8px; }
        .fp-stage-row label { font-size: 12px; margin-bottom: 2px; }
        .fp-stage-errors { font-size: 12px; }
        .fp-history td { font-size: 12px; vertical-align: top; }
        .fp-readonly .fp-edit-only { display: none !important; }
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
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active">Approval Policies</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Approval Policies</h2>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#fp-tab-policies" role="tab">Policies</a></li>
                                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fp-tab-delegations" role="tab">Delegations</a></li>
                                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fp-tab-test" role="tab">Test a request</a></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="fp-tab-policies" role="tabpanel">
                                        <div class="form-row mb-2 align-items-end">
                                            <div class="col-md-2">
                                                <label class="small mb-1" for="fp-filter-status">Status</label>
                                                <select class="form-control form-control-sm" id="fp-filter-status">
                                                    <option value="current">Active and draft</option>
                                                    <option value="active">Active</option>
                                                    <option value="draft">Draft</option>
                                                    <option value="retired">Retired</option>
                                                    <option value="all">All</option>
                                                </select>
                                            </div>
                                            <div class="col-md-10 text-right">
                                                <button type="button" class="btn btn-info btn-sm" id="fp-policy-add"><i class="fa-solid fa-plus mr-1"></i>New policy</button>
                                            </div>
                                        </div>
                                        <table id="fp-policy-table" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Policy</th>
                                                    <th>Applies to</th>
                                                    <th>Amount</th>
                                                    <th>Priority</th>
                                                    <th>Effective</th>
                                                    <th>Approval stages</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                        <div class="small text-muted mt-2">
                                            <p class="mb-1"><strong>How policies work.</strong> When a request is submitted, the system picks the active policy whose criteria all match
                                                (legal entity, state, branch office, expense type, category, request total and the submission date). If several match, the one with the
                                                <em>lowest priority number</em> wins. Two policies that could match the same request with the same priority cannot both be active.</p>
                                            <p class="mb-1">If no policy matches, the request cannot be submitted; the requester is asked to contact Finance. Keep a broad catch-all policy
                                                (any entity, any type, any amount) with a high priority number so every request has a route.</p>
                                            <p class="mb-0">New policies start as <em>drafts</em>. Publishing makes a draft active from its effective date; active versions never change. To change
                                                an active policy, create a new version, edit it and publish it: the old version ends the day before. Each submitted request keeps a copy of
                                                the policy version and stages it was given. Nobody approves their own request.</p>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="fp-tab-delegations" role="tabpanel">
                                        <div class="text-right mb-2">
                                            <button type="button" class="btn btn-info btn-sm" id="fp-delegation-add"><i class="fa-solid fa-plus mr-1"></i>New delegation</button>
                                        </div>
                                        <table id="fp-delegation-table" class="table table-bordered table-hover table-striped w-100">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Approver away</th>
                                                    <th>Approves on their behalf</th>
                                                    <th>From</th>
                                                    <th>To</th>
                                                    <th>Reason</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                        <p class="small text-muted mt-2 mb-0">While a delegation is active, the delegate can act on approval stages assigned to the approver who is away.
                                            Delegations last at most 90 days, cannot overlap for the same approver and cannot be chained (a delegate cannot pass them on).</p>
                                    </div>

                                    <div class="tab-pane fade" id="fp-tab-test" role="tabpanel">
                                        <p class="small text-muted">See which policy and approval stages a request would get, without creating one.</p>
                                        <form id="fp-test-form" novalidate>
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label for="fp-test-office">Branch office <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="OfficeID" id="fp-test-office"></select>
                                                    <div class="invalid-feedback" data-for="OfficeID"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-test-type">Expense type <span class="text-danger">*</span></label>
                                                    <select class="form-control" name="RequestType" id="fp-test-type"></select>
                                                    <div class="invalid-feedback" data-for="RequestType"></div>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="fp-test-categories">Categories of the lines <span class="text-danger">*</span></label>
                                                    <select class="form-control" id="fp-test-categories" multiple></select>
                                                    <div class="text-danger small" data-for="CategoryIDs"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-test-amount">Request total (INR) <span class="text-danger">*</span></label>
                                                    <input type="text" inputmode="decimal" class="form-control" name="Amount" id="fp-test-amount" maxlength="16" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="Amount"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-test-date">Submission date</label>
                                                    <input type="date" class="form-control" name="Date" id="fp-test-date">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm" id="fp-test-run">Find route</button>
                                        </form>
                                        <div id="fp-test-result" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-policy-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-policy-modal-title">New policy</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-policy-form" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="ID" id="fp-pol-id" value="0">
                                        <input type="hidden" name="RowVersion" id="fp-pol-version" value="0">
                                        <div id="fp-pol-readonly-note" class="alert alert-light border small" style="display:none"></div>
                                        <fieldset id="fp-pol-fields">
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-code">Policy code <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control text-uppercase" name="PolicyCode" id="fp-pol-code" maxlength="30" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="PolicyCode"></div>
                                                </div>
                                                <div class="form-group col-md-5">
                                                    <label for="fp-pol-name">Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="PolicyName" id="fp-pol-name" maxlength="150" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="PolicyName"></div>
                                                </div>
                                                <div class="form-group col-md-4">
                                                    <label for="fp-pol-description">Description</label>
                                                    <input type="text" class="form-control" name="Description" id="fp-pol-description" maxlength="500" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="Description"></div>
                                                </div>
                                            </div>
                                            <h6 class="mt-2">Which requests it covers</h6>
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-company">Legal entity</label>
                                                    <select class="form-control" name="CompanyID" id="fp-pol-company"></select>
                                                    <div class="invalid-feedback" data-for="CompanyID"></div>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-state">State</label>
                                                    <select class="form-control" name="StateID" id="fp-pol-state"></select>
                                                    <div class="invalid-feedback" data-for="StateID"></div>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-office">Branch office</label>
                                                    <select class="form-control" name="OfficeID" id="fp-pol-office"></select>
                                                    <div class="invalid-feedback" data-for="OfficeID"></div>
                                                </div>
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-type">Expense type</label>
                                                    <select class="form-control" name="RequestType" id="fp-pol-type"></select>
                                                    <div class="invalid-feedback" data-for="RequestType"></div>
                                                </div>
                                            </div>
                                            <div class="form-row">
                                                <div class="form-group col-md-3">
                                                    <label for="fp-pol-category">Category</label>
                                                    <select class="form-control" name="CategoryID" id="fp-pol-category"></select>
                                                    <div class="invalid-feedback" data-for="CategoryID"></div>
                                                    <small class="form-text text-muted">Matches only when every line is in this category or its sub-categories.</small>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-pol-min">Total from (INR)</label>
                                                    <input type="text" inputmode="decimal" class="form-control" name="MinAmount" id="fp-pol-min" maxlength="16" autocomplete="off" placeholder="0">
                                                    <div class="invalid-feedback" data-for="MinAmount"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-pol-max">Total up to (INR)</label>
                                                    <input type="text" inputmode="decimal" class="form-control" name="MaxAmount" id="fp-pol-max" maxlength="16" autocomplete="off" placeholder="No limit">
                                                    <div class="invalid-feedback" data-for="MaxAmount"></div>
                                                </div>
                                                <div class="form-group col-md-1">
                                                    <label for="fp-pol-priority">Priority <span class="text-danger">*</span></label>
                                                    <input type="text" inputmode="numeric" class="form-control" name="Priority" id="fp-pol-priority" maxlength="3" autocomplete="off">
                                                    <div class="invalid-feedback" data-for="Priority"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-pol-from">Effective from <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control" name="EffectiveFrom" id="fp-pol-from">
                                                    <div class="invalid-feedback" data-for="EffectiveFrom"></div>
                                                </div>
                                                <div class="form-group col-md-2">
                                                    <label for="fp-pol-to">Effective to</label>
                                                    <input type="date" class="form-control" name="EffectiveTo" id="fp-pol-to">
                                                    <div class="invalid-feedback" data-for="EffectiveTo"></div>
                                                </div>
                                            </div>
                                            <h6 class="mt-2">Approval stages, in order</h6>
                                            <div class="text-danger small mb-2" data-for="stages"></div>
                                            <div id="fp-stage-list"></div>
                                            <button type="button" class="btn btn-outline-primary btn-sm fp-edit-only" id="fp-stage-add"><i class="fa-solid fa-plus mr-1"></i>Add stage</button>
                                            <p class="small text-muted mt-2 mb-0">A <em>role</em> stage can be approved by anyone holding that role whose access covers the request's office.
                                                A <em>named employee</em> must have approval rights. Optional stages, and stages that apply only from an amount, are skipped when they do not apply.
                                                At least one mandatory stage must apply to every amount.</p>
                                        </fieldset>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary fp-edit-only" id="fp-policy-save">Save draft</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <template id="fp-stage-template">
                        <div class="fp-stage-row">
                            <div class="form-row">
                                <div class="form-group col-md-1">
                                    <label>Stage</label>
                                    <div class="form-control-plaintext font-weight-bold fp-stage-no"></div>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Stage name *</label>
                                    <input type="text" class="form-control form-control-sm" data-field="StageName" maxlength="100" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Approver</label>
                                    <select class="form-control form-control-sm" data-field="ApproverType">
                                        <option value="role">A role</option>
                                        <option value="employee">A named employee</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 fp-when-role">
                                    <label>Role *</label>
                                    <select class="form-control form-control-sm fp-role-select" data-field="ApproverRole"></select>
                                </div>
                                <div class="form-group col-md-3 fp-when-employee">
                                    <label>Employee *</label>
                                    <select class="form-control form-control-sm fp-employee-select" data-field="ApproverEmployeeID"></select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label>Mandatory</label>
                                    <select class="form-control form-control-sm" data-field="IsMandatory">
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Applies from (INR)</label>
                                    <input type="text" inputmode="decimal" class="form-control form-control-sm" data-field="ApplyFromAmount" maxlength="16" placeholder="Any amount" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-2 offset-md-1">
                                    <label>Escalate after (hours)</label>
                                    <input type="text" inputmode="numeric" class="form-control form-control-sm" data-field="EscalateAfterHours" maxlength="3" placeholder="No escalation" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2 fp-when-escalate">
                                    <label>Escalate to</label>
                                    <select class="form-control form-control-sm" data-field="EscalateToType">
                                        <option value="role">A role</option>
                                        <option value="employee">A named employee</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 fp-when-escalate fp-when-esc-role">
                                    <label>Role *</label>
                                    <select class="form-control form-control-sm fp-role-select" data-field="EscalateToRole"></select>
                                </div>
                                <div class="form-group col-md-3 fp-when-escalate fp-when-esc-employee">
                                    <label>Employee *</label>
                                    <select class="form-control form-control-sm fp-employee-select" data-field="EscalateToEmployeeID"></select>
                                </div>
                                <div class="form-group col text-right fp-edit-only">
                                    <label class="d-block">&nbsp;</label>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" data-stage-act="up" title="Move up"><i class="fa-solid fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" data-stage-act="down" title="Move down"><i class="fa-solid fa-arrow-down"></i></button>
                                    <button type="button" class="btn btn-xs btn-outline-danger" data-stage-act="remove" title="Remove stage"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <div class="text-danger fp-stage-errors mb-2"></div>
                        </div>
                    </template>

                    <div class="modal fade" id="fp-delegation-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title">New delegation</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-delegation-form" novalidate>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="fp-del-delegator">Approver who is away <span class="text-danger">*</span></label>
                                            <select class="form-control" name="DelegatorEmployeeID" id="fp-del-delegator"></select>
                                            <div class="text-danger small" data-for="DelegatorEmployeeID"></div>
                                        </div>
                                        <div class="form-group">
                                            <label for="fp-del-delegate">Approves on their behalf <span class="text-danger">*</span></label>
                                            <select class="form-control" name="DelegateEmployeeID" id="fp-del-delegate"></select>
                                            <div class="text-danger small" data-for="DelegateEmployeeID"></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-6">
                                                <label for="fp-del-from">From <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="FromDate" id="fp-del-from">
                                                <div class="invalid-feedback" data-for="FromDate"></div>
                                            </div>
                                            <div class="form-group col-6">
                                                <label for="fp-del-to">To <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="ToDate" id="fp-del-to">
                                                <div class="invalid-feedback" data-for="ToDate"></div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="fp-del-reason">Reason <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="Reason" id="fp-del-reason" rows="2" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Reason"></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="fp-delegation-save">Save</button>
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
    <script src="../js/modules/fp_approval_policies.js"></script>
</body>
</html>
