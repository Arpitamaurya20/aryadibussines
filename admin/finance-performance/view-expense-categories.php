<?php
session_start();
include('../controllers/common_controllers.php');
require_once('controller/fp_access.php');
setTimeZone();

$conn = _connectodb();
$fpActor = fp_require_page($conn, 'configure', true);
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
    <title>Expense Categories - Finance &amp; Performance</title>
    <?php include('../includes/common_head_content.php'); ?>
    <meta name="fp-csrf-token" content="<?php echo fp_h(fp_csrf_token()); ?>">
    <link rel="stylesheet" media="screen, print" href="../css/datagrid/datatables/datatables.bundle.css">
    <style>
        .modal_header { background-color: #003f88; color: #fff; }
        .modal_header button { opacity: 1; color: #fff; }
        #fp-category-table td { vertical-align: middle; font-size: 12.5px; }
        #fp-category-table .fp-child-name { padding-left: 18px; }
        #fp-category-table .fp-child-name:before { content: "\2514\00a0"; color: #adb5bd; }
        .fp-rule { display: inline-block; font-size: 11px; padding: 1px 6px; border-radius: 3px; margin: 1px 2px 1px 0; }
        .fp-rule-on { background: #e3f6ea; color: #1e7e34; }
        .fp-rule-off { background: #f1f1f1; color: #9a9a9a; text-decoration: line-through; }
        .fp-history td { font-size: 12px; vertical-align: top; }
        .fp-history .fp-change { white-space: nowrap; }
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
                        <li class="breadcrumb-item active">Expense Categories</li>
                    </ol>

                    <div class="panel">
                        <div class="panel-hdr">
                            <h2>Expense Categories</h2>
                            <div class="panel-toolbar">
                                <button type="button" class="btn btn-info btn-sm mr-3" id="fp-category-add"><i class="fa-solid fa-plus mr-1"></i>Add category</button>
                            </div>
                        </div>
                        <div class="panel-container show">
                            <div class="panel-content">
                                <div class="form-row mb-2">
                                    <div class="col-md-3">
                                        <label class="small mb-1" for="fp-filter-status">Status</label>
                                        <select class="form-control form-control-sm" id="fp-filter-status">
                                            <option value="all">All</option>
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small mb-1" for="fp-filter-company">Company</label>
                                        <select class="form-control form-control-sm" id="fp-filter-company">
                                            <option value="all">All</option>
                                        </select>
                                    </div>
                                </div>
                                <table id="fp-category-table" class="table table-bordered table-hover table-striped w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Category</th>
                                            <th>Company</th>
                                            <th>Rules</th>
                                            <th>Used in</th>
                                            <th>Status</th>
                                            <th>Last changed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <p class="small text-muted mb-0 mt-2">
                                    Deactivated categories stay on existing records but cannot be chosen for new ones.
                                    A category can only be deleted while nothing uses it; otherwise deactivate it. Every change is recorded in the history.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="fp-category-modal" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header modal_header">
                                    <h5 class="modal-title" id="fp-category-modal-title">Add category</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                </div>
                                <form id="fp-category-form" novalidate>
                                    <div class="modal-body">
                                        <input type="hidden" name="ID" id="fp-cat-id" value="0">
                                        <input type="hidden" name="RowVersion" id="fp-cat-version" value="0">
                                        <div class="form-row">
                                            <div class="form-group col-md-4">
                                                <label for="fp-cat-code">Category code <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control text-uppercase" name="CategoryCode" id="fp-cat-code" maxlength="30" autocomplete="off">
                                                <div class="invalid-feedback" data-for="CategoryCode"></div>
                                                <small class="form-text text-muted" id="fp-cat-code-note">Short unique code, for example TRAVEL.</small>
                                            </div>
                                            <div class="form-group col-md-8">
                                                <label for="fp-cat-name">Category name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="CategoryName" id="fp-cat-name" maxlength="150" autocomplete="off">
                                                <div class="invalid-feedback" data-for="CategoryName"></div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="fp-cat-description">Description</label>
                                            <textarea class="form-control" name="Description" id="fp-cat-description" rows="2" maxlength="500"></textarea>
                                            <div class="invalid-feedback" data-for="Description"></div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group col-md-5">
                                                <label for="fp-cat-parent">Parent category</label>
                                                <select class="form-control" name="ParentCategoryID" id="fp-cat-parent"></select>
                                                <div class="invalid-feedback" data-for="ParentCategoryID"></div>
                                                <small class="form-text text-muted">Optional. Sub-categories are one level deep.</small>
                                            </div>
                                            <div class="form-group col-md-5">
                                                <label for="fp-cat-company">Company scope</label>
                                                <select class="form-control" name="CompanyID" id="fp-cat-company"></select>
                                                <div class="invalid-feedback" data-for="CompanyID"></div>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="fp-cat-sort">Sort order</label>
                                                <input type="number" class="form-control" name="SortOrder" id="fp-cat-sort" min="0" max="9999" step="1" value="0">
                                                <div class="invalid-feedback" data-for="SortOrder"></div>
                                            </div>
                                        </div>
                                        <label class="mb-1">Rules</label>
                                        <div class="form-row">
                                            <div class="col-md-6">
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" name="ReceiptRequired" id="fp-cat-receipt" value="1">
                                                    <label class="custom-control-label" for="fp-cat-receipt">Receipt required</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" name="RecurringAllowed" id="fp-cat-recurring" value="1">
                                                    <label class="custom-control-label" for="fp-cat-recurring">Recurring expenses allowed</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" name="AdvanceAllowed" id="fp-cat-advance" value="1">
                                                    <label class="custom-control-label" for="fp-cat-advance">Advance allowed</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mb-2">
                                                    <input type="checkbox" class="custom-control-input" name="ReimbursementAllowed" id="fp-cat-reimbursement" value="1">
                                                    <label class="custom-control-label" for="fp-cat-reimbursement">Reimbursement allowed</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-danger small" data-for="AdvanceAllowed"></div>
                                        <div class="form-row mt-2">
                                            <div class="form-group col-md-6 mb-0">
                                                <label for="fp-cat-quotation">Quotation required above (INR)</label>
                                                <input type="text" inputmode="decimal" class="form-control" name="QuotationRequiredAbove" id="fp-cat-quotation" maxlength="14" autocomplete="off" placeholder="No quotation rule">
                                                <div class="invalid-feedback" data-for="QuotationRequiredAbove"></div>
                                                <small class="form-text text-muted">Lines above this amount must have a quotation attached before submitting.</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary" id="fp-category-save">Save</button>
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
                                        <thead class="thead-light"><tr><th style="width:150px">When</th><th style="width:120px">By</th><th style="width:100px">Action</th><th>Changes</th><th style="width:220px">Reason</th></tr></thead>
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
    <script src="../js/modules/fp_expense_categories.js"></script>
</body>
</html>
